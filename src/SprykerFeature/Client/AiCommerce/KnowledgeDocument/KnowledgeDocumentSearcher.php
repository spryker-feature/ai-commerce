<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use ArrayObject;
use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentScopeTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSearchRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSearchResponseTransfer;
use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeSearchConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeSearchCriteriaTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\Dependency\KnowledgeSearch\KnowledgeSearchPluginInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class KnowledgeDocumentSearcher implements KnowledgeDocumentSearcherInterface
{
    use LoggerTrait;

    protected const string MESSAGE_NO_DOCUMENTS = 'No shop documents are available for this request. Do not search again with another query.';

    protected const string MESSAGE_NO_MATCHING_EXCERPTS = 'No excerpt matches this query. Search again with a more specific or a more general phrase.';

    protected const string MESSAGE_SEARCH_NOT_AVAILABLE = 'The document search is not available at the moment. Do not search again: tell the customer that the documents cannot be read now, and answer from the product data only when it covers the question.';

    protected const string LOG_MESSAGE_SEARCH_FAILED = 'Knowledge document search failed: %s';

    protected const string ERROR_MESSAGE_SEPARATOR = '; ';

    public function __construct(
        protected KnowledgeDocumentSearchRequestValidatorInterface $knowledgeDocumentSearchRequestValidator,
        protected KnowledgeDocumentScopeResolverInterface $knowledgeDocumentScopeResolver,
        protected KnowledgeDocumentSearchResultBuilderInterface $knowledgeDocumentSearchResultBuilder,
        protected KnowledgeDocumentPassageFilterInterface $knowledgeDocumentPassageFilter,
        protected KnowledgeSearchPluginInterface $knowledgeSearchPlugin,
        protected LocaleClientInterface $localeClient,
        protected StoreClientInterface $storeClient,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    public function searchDocuments(
        KnowledgeDocumentSearchRequestTransfer $knowledgeDocumentSearchRequestTransfer
    ): KnowledgeDocumentSearchResponseTransfer {
        $validationMessages = $this->knowledgeDocumentSearchRequestValidator->validate($knowledgeDocumentSearchRequestTransfer);

        if ($validationMessages !== []) {
            return $this->createResponse(false, $validationMessages);
        }

        $knowledgeDocumentScopeRequestTransfer = $this->expandScopeRequest(
            $knowledgeDocumentSearchRequestTransfer->getKnowledgeDocumentScopeRequest() ?? new KnowledgeDocumentScopeRequestTransfer(),
        );
        $knowledgeDocumentScopeTransfer = $this->knowledgeDocumentScopeResolver->resolveDocumentScope($knowledgeDocumentScopeRequestTransfer);

        if ($knowledgeDocumentScopeTransfer->getDocumentKeys() === []) {
            return $this->createResponse(true, [...$knowledgeDocumentScopeTransfer->getMessages(), static::MESSAGE_NO_DOCUMENTS]);
        }

        $limit = $this->resolveLimit($knowledgeDocumentSearchRequestTransfer->getLimit());
        $knowledgePassageCollectionTransfer = $this->knowledgeSearchPlugin->searchDocuments(
            $this->createKnowledgeSearchCriteria(
                $knowledgeDocumentSearchRequestTransfer,
                $knowledgeDocumentScopeTransfer,
                $knowledgeDocumentScopeRequestTransfer->getLocaleNameOrFail(),
                $limit * max(1, $this->aiCommerceConfig->getKnowledgeDocumentCandidateMultiplier()),
            ),
        );

        return $this->createSearchResponse($knowledgePassageCollectionTransfer, $knowledgeDocumentScopeTransfer, $limit);
    }

    protected function createSearchResponse(
        KnowledgePassageCollectionTransfer $knowledgePassageCollectionTransfer,
        KnowledgeDocumentScopeTransfer $knowledgeDocumentScopeTransfer,
        int $limit
    ): KnowledgeDocumentSearchResponseTransfer {
        if (!$knowledgePassageCollectionTransfer->getIsSuccessful()) {
            $this->logSearchErrors($knowledgePassageCollectionTransfer);

            return $this->createResponse(false, [...$knowledgeDocumentScopeTransfer->getMessages(), static::MESSAGE_SEARCH_NOT_AVAILABLE]);
        }

        $knowledgePassageCollectionTransfer = $this->knowledgeDocumentPassageFilter->filterKnowledgePassages(
            $knowledgePassageCollectionTransfer,
            $knowledgeDocumentScopeTransfer->getDocumentKeys(),
            $limit,
        );

        if ($knowledgePassageCollectionTransfer->getKnowledgePassages()->count() === 0) {
            return $this->createResponse(true, [...$knowledgeDocumentScopeTransfer->getMessages(), static::MESSAGE_NO_MATCHING_EXCERPTS]);
        }

        return $this->createResponse(true, $knowledgeDocumentScopeTransfer->getMessages())
            ->setKnowledgeDocumentSearchResults(new ArrayObject(
                $this->knowledgeDocumentSearchResultBuilder->buildSearchResults(
                    $knowledgePassageCollectionTransfer,
                    $this->aiCommerceConfig->getKnowledgeDocumentMaxCharacters(),
                ),
            ));
    }

    protected function logSearchErrors(KnowledgePassageCollectionTransfer $knowledgePassageCollectionTransfer): void
    {
        $errorMessages = [];

        foreach ($knowledgePassageCollectionTransfer->getErrors() as $errorTransfer) {
            $errorMessages[] = (string)$errorTransfer->getMessage();
        }

        $this->getLogger()->error(sprintf(static::LOG_MESSAGE_SEARCH_FAILED, implode(static::ERROR_MESSAGE_SEPARATOR, $errorMessages)));
    }

    protected function expandScopeRequest(
        KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer
    ): KnowledgeDocumentScopeRequestTransfer {
        if ((string)$knowledgeDocumentScopeRequestTransfer->getLocaleName() === '') {
            $knowledgeDocumentScopeRequestTransfer->setLocaleName($this->localeClient->getCurrentLocale());
        }

        if ((string)$knowledgeDocumentScopeRequestTransfer->getStoreName() === '') {
            $knowledgeDocumentScopeRequestTransfer->setStoreName($this->storeClient->getCurrentStore()->getNameOrFail());
        }

        return $knowledgeDocumentScopeRequestTransfer;
    }

    protected function createKnowledgeSearchCriteria(
        KnowledgeDocumentSearchRequestTransfer $knowledgeDocumentSearchRequestTransfer,
        KnowledgeDocumentScopeTransfer $knowledgeDocumentScopeTransfer,
        string $localeName,
        int $numberOfCandidates
    ): KnowledgeSearchCriteriaTransfer {
        $documentName = trim((string)$knowledgeDocumentSearchRequestTransfer->getDocumentName());

        $knowledgeSearchConditionsTransfer = (new KnowledgeSearchConditionsTransfer())
            ->setQueryText(trim((string)$knowledgeDocumentSearchRequestTransfer->getQuery()))
            ->setDocumentKeys($knowledgeDocumentScopeTransfer->getDocumentKeys())
            ->setSourceTypes($knowledgeDocumentSearchRequestTransfer->getSourceTypes())
            ->setLocales([$localeName, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_LOCALE_ALL])
            ->setDocumentName($documentName !== '' ? $documentName : null);

        return (new KnowledgeSearchCriteriaTransfer())
            ->setKnowledgeSearchConditions($knowledgeSearchConditionsTransfer)
            ->setPagination((new PaginationTransfer())->setLimit($numberOfCandidates));
    }

    protected function resolveLimit(?int $requestedLimit): int
    {
        $maxResults = max(1, $this->aiCommerceConfig->getKnowledgeDocumentMaxResults());

        return max(1, min($requestedLimit ?? $maxResults, $maxResults));
    }

    /**
     * @param array<string> $messages
     */
    protected function createResponse(bool $isSuccessful, array $messages): KnowledgeDocumentSearchResponseTransfer
    {
        return (new KnowledgeDocumentSearchResponseTransfer())
            ->setIsSuccessful($isSuccessful)
            ->setMessages($messages);
    }
}
