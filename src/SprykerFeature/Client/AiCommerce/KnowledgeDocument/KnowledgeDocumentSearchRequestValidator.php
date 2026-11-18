<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgeDocumentSearchRequestTransfer;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;

class KnowledgeDocumentSearchRequestValidator implements KnowledgeDocumentSearchRequestValidatorInterface
{
    protected const string MESSAGE_EMPTY_QUERY = 'Pass the customer question, rewritten as a search phrase, as the query.';

    protected const string MESSAGE_TOO_MANY_IDENTIFIERS = 'Pass at most %d values in each identifier list.';

    protected const string MESSAGE_TOO_MANY_SCOPE_REFERENCES = 'Pass at most %d products in total, counted over all product identifier lists together, for example abstract SKUs, concrete SKUs and product abstract IDs.';

    protected const string MESSAGE_QUERY_TOO_LONG = 'Pass a query of at most %d characters.';

    protected const string MESSAGE_DOCUMENT_NAME_TOO_LONG = 'Pass a document name of at most %d characters.';

    public function __construct(protected AiCommerceConfig $aiCommerceConfig)
    {
    }

    /**
     * @return list<string>
     */
    public function validate(KnowledgeDocumentSearchRequestTransfer $knowledgeDocumentSearchRequestTransfer): array
    {
        $messages = [];

        if (trim((string)$knowledgeDocumentSearchRequestTransfer->getQuery()) === '') {
            $messages[] = static::MESSAGE_EMPTY_QUERY;
        }

        $maxQueryLength = $this->aiCommerceConfig->getKnowledgeDocumentMaxQueryLength();

        if (mb_strlen(trim((string)$knowledgeDocumentSearchRequestTransfer->getQuery())) > $maxQueryLength) {
            $messages[] = sprintf(static::MESSAGE_QUERY_TOO_LONG, $maxQueryLength);
        }

        $maxDocumentNameLength = $this->aiCommerceConfig->getKnowledgeDocumentMaxDocumentNameLength();

        if (mb_strlen(trim((string)$knowledgeDocumentSearchRequestTransfer->getDocumentName())) > $maxDocumentNameLength) {
            $messages[] = sprintf(static::MESSAGE_DOCUMENT_NAME_TOO_LONG, $maxDocumentNameLength);
        }

        if ($this->hasTooManyIdentifiers($knowledgeDocumentSearchRequestTransfer)) {
            $messages[] = sprintf(static::MESSAGE_TOO_MANY_IDENTIFIERS, $this->aiCommerceConfig->getKnowledgeDocumentMaxIdentifiers());
        }

        if ($this->hasTooManyScopeReferences($knowledgeDocumentSearchRequestTransfer)) {
            $messages[] = sprintf(static::MESSAGE_TOO_MANY_SCOPE_REFERENCES, $this->aiCommerceConfig->getKnowledgeDocumentMaxIdentifiers());
        }

        return $messages;
    }

    protected function hasTooManyIdentifiers(
        KnowledgeDocumentSearchRequestTransfer $knowledgeDocumentSearchRequestTransfer
    ): bool {
        $maxIdentifiers = $this->aiCommerceConfig->getKnowledgeDocumentMaxIdentifiers();

        foreach ($this->getIdentifierLists($knowledgeDocumentSearchRequestTransfer) as $identifiers) {
            if (count($identifiers) > $maxIdentifiers) {
                return true;
            }
        }

        return false;
    }

    protected function hasTooManyScopeReferences(
        KnowledgeDocumentSearchRequestTransfer $knowledgeDocumentSearchRequestTransfer
    ): bool {
        $knowledgeDocumentScopeRequestTransfer = $knowledgeDocumentSearchRequestTransfer->getKnowledgeDocumentScopeRequest();

        if ($knowledgeDocumentScopeRequestTransfer === null) {
            return false;
        }

        $scopeReferenceCount = 0;

        foreach ($knowledgeDocumentScopeRequestTransfer->getScopeReferences() as $scopeReferences) {
            $scopeReferenceCount += count((array)$scopeReferences);
        }

        return $scopeReferenceCount > $this->aiCommerceConfig->getKnowledgeDocumentMaxIdentifiers();
    }

    /**
     * @return list<array<int|string>>
     */
    protected function getIdentifierLists(
        KnowledgeDocumentSearchRequestTransfer $knowledgeDocumentSearchRequestTransfer
    ): array {
        $identifierLists = [$knowledgeDocumentSearchRequestTransfer->getSourceTypes()];
        $knowledgeDocumentScopeRequestTransfer = $knowledgeDocumentSearchRequestTransfer->getKnowledgeDocumentScopeRequest();

        if ($knowledgeDocumentScopeRequestTransfer === null) {
            return $identifierLists;
        }

        $identifierLists[] = $knowledgeDocumentScopeRequestTransfer->getSourceIdentifiers();

        return $identifierLists;
    }
}
