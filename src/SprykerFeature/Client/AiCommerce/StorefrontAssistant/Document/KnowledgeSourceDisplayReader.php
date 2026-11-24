<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Document;

use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentScopeTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\KnowledgeDocument\KnowledgeDocumentScopeResolverInterface;
use SprykerFeature\Client\AiCommerce\KnowledgeDocument\KnowledgeDocumentUrlSanitizerInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class KnowledgeSourceDisplayReader implements KnowledgeSourceDisplayReaderInterface
{
    public const string RESULT_KEY_SOURCES = 'sources';

    public const string RESULT_KEY_MESSAGES = 'messages';

    public const string RESULT_KEY_URL = 'url';

    protected const string MESSAGE_ATTACHMENT_IDS_REQUIRED = 'Pass the sourceIdentifier values of the document excerpts that your answer used as attachment_ids. No source is shown.';

    protected const string MESSAGE_PRODUCTS_REQUIRED = 'Pass the products of the document search as abstract_skus, concrete_skus or product_abstract_ids. Without a product, no source is shown.';

    protected const string MESSAGE_TOO_MANY_PRODUCTS = 'Pass at most %d products in total over abstract_skus, concrete_skus and product_abstract_ids. No source is shown.';

    protected const string MESSAGE_UNKNOWN_ATTACHMENT_IDS = '%d of the attachment_ids are not documents of the given products and are not shown. Pass only sourceIdentifier values from product_knowledge_search results of these products.';

    public function __construct(
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected DocumentToolArgumentMapperInterface $documentToolArgumentMapper,
        protected KnowledgeDocumentScopeResolverInterface $knowledgeDocumentScopeResolver,
        protected KnowledgeDocumentUrlSanitizerInterface $knowledgeDocumentUrlSanitizer,
        protected LocaleClientInterface $localeClient,
        protected StoreClientInterface $storeClient,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function getKnowledgeSources(array $arguments): array
    {
        $knowledgeDocumentScopeRequestTransfer = $this->documentToolArgumentMapper->mapArgumentsToKnowledgeDocumentScopeRequest(
            $this->toolArgumentNormalizer->normalizeArguments($arguments),
        );
        $validationMessages = $this->validateScopeRequest($knowledgeDocumentScopeRequestTransfer);

        if ($validationMessages !== []) {
            return $this->createResult([], $validationMessages);
        }

        $knowledgeDocumentScopeTransfer = $this->knowledgeDocumentScopeResolver->resolveDocumentScope(
            $knowledgeDocumentScopeRequestTransfer
                ->setLocaleName($this->localeClient->getCurrentLocale())
                ->setStoreName($this->storeClient->getCurrentStore()->getNameOrFail()),
        );

        return $this->createSourcesResult($knowledgeDocumentScopeTransfer, $knowledgeDocumentScopeRequestTransfer->getSourceIdentifiers());
    }

    /**
     * @return list<string>
     */
    protected function validateScopeRequest(KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer): array
    {
        if ($knowledgeDocumentScopeRequestTransfer->getSourceIdentifiers() === []) {
            return [static::MESSAGE_ATTACHMENT_IDS_REQUIRED];
        }

        $productCount = $this->countScopeReferences($knowledgeDocumentScopeRequestTransfer);

        if ($productCount === 0) {
            return [static::MESSAGE_PRODUCTS_REQUIRED];
        }

        $maxProducts = $this->aiCommerceConfig->getKnowledgeDocumentMaxIdentifiers();

        if ($productCount > $maxProducts) {
            return [sprintf(static::MESSAGE_TOO_MANY_PRODUCTS, $maxProducts)];
        }

        return [];
    }

    protected function countScopeReferences(KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer): int
    {
        $scopeReferenceCount = 0;

        foreach ($knowledgeDocumentScopeRequestTransfer->getScopeReferences() as $scopeReferences) {
            $scopeReferenceCount += count((array)$scopeReferences);
        }

        return $scopeReferenceCount;
    }

    /**
     * @param array<string> $sourceIdentifiers
     *
     * @return array<string, mixed>
     */
    protected function createSourcesResult(KnowledgeDocumentScopeTransfer $knowledgeDocumentScopeTransfer, array $sourceIdentifiers): array
    {
        $knowledgeDocumentSourceTransfers = $this->collectKnowledgeDocumentSources($knowledgeDocumentScopeTransfer, $sourceIdentifiers);
        $messages = $knowledgeDocumentScopeTransfer->getMessages();
        $foundSourceIdentifiers = array_unique(array_map(
            static fn (KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): string => (string)$knowledgeDocumentSourceTransfer->getSourceIdentifier(),
            $knowledgeDocumentSourceTransfers,
        ));
        $unknownSourceIdentifierCount = count(array_diff($sourceIdentifiers, $foundSourceIdentifiers));

        if ($unknownSourceIdentifierCount > 0) {
            $messages[] = sprintf(static::MESSAGE_UNKNOWN_ATTACHMENT_IDS, $unknownSourceIdentifierCount);
        }

        $knowledgeDocumentSourceTransfers = array_slice(
            $knowledgeDocumentSourceTransfers,
            0,
            max(1, $this->aiCommerceConfig->getKnowledgeSourceDisplayMaxSources()),
        );

        return $this->createResult(
            array_map($this->createSourceRow(...), $knowledgeDocumentSourceTransfers),
            $messages,
        );
    }

    /**
     * @param array<string> $sourceIdentifiers
     *
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer>
     */
    protected function collectKnowledgeDocumentSources(KnowledgeDocumentScopeTransfer $knowledgeDocumentScopeTransfer, array $sourceIdentifiers): array
    {
        $knowledgeDocumentSourceTransfersBySourceIdentifier = [];

        foreach ($knowledgeDocumentScopeTransfer->getKnowledgeDocumentSources() as $knowledgeDocumentSourceTransfer) {
            $sourceIdentifier = (string)$knowledgeDocumentSourceTransfer->getSourceIdentifier();
            $knowledgeDocumentSourceTransfersBySourceIdentifier[$sourceIdentifier][$this->createDocumentKey($knowledgeDocumentSourceTransfer)] = $knowledgeDocumentSourceTransfer;
        }

        $knowledgeDocumentSourceTransfers = [];

        foreach ($sourceIdentifiers as $sourceIdentifier) {
            $knowledgeDocumentSourceTransfers += $knowledgeDocumentSourceTransfersBySourceIdentifier[$sourceIdentifier] ?? [];
        }

        return array_values($knowledgeDocumentSourceTransfers);
    }

    protected function createDocumentKey(KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): string
    {
        return sprintf(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT,
            $knowledgeDocumentSourceTransfer->getSourceType(),
            $knowledgeDocumentSourceTransfer->getSourceIdentifier(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function createSourceRow(KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): array
    {
        $sourceRow = [
            DocumentSearchReader::RESULT_KEY_DOCUMENT_NAME => (string)$knowledgeDocumentSourceTransfer->getDocumentName(),
            DocumentSearchReader::RESULT_KEY_SOURCE_TYPE => (string)$knowledgeDocumentSourceTransfer->getSourceType(),
            DocumentSearchReader::RESULT_KEY_SOURCE_IDENTIFIER => (string)$knowledgeDocumentSourceTransfer->getSourceIdentifier(),
        ];
        $url = $this->knowledgeDocumentUrlSanitizer->sanitizeUrl($knowledgeDocumentSourceTransfer->getUrl());

        if ($url === null) {
            return $sourceRow;
        }

        $sourceRow[static::RESULT_KEY_URL] = $url;

        return $sourceRow;
    }

    /**
     * @param list<array<string, string>> $sources
     * @param array<string> $messages
     *
     * @return array<string, mixed>
     */
    protected function createResult(array $sources, array $messages): array
    {
        return [
            static::RESULT_KEY_SOURCES => $sources,
            static::RESULT_KEY_MESSAGES => array_values(array_unique($messages)),
        ];
    }
}
