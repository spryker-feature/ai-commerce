<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reindexer;

use ArrayObject;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentReindexRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentReindexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\KnowledgeDocumentSourcePluginResolverInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Sender\KnowledgeDocumentIndexMessageSenderInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentPendingWriterInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class KnowledgeDocumentReindexer implements KnowledgeDocumentReindexerInterface
{
    protected const string ERROR_MESSAGE_UNKNOWN_SOURCE_TYPE = 'Unknown knowledge document source type "%s".';

    protected const string ERROR_MESSAGE_KNOWLEDGE_STORE_NOT_CONFIGURED = 'The knowledge store is not configured for the source type "%s". No document is queued. Run the knowledge-document:setup command for details.';

    public function __construct(
        protected KnowledgeDocumentSourcePluginResolverInterface $knowledgeDocumentSourcePluginResolver,
        protected KnowledgeDocumentPendingWriterInterface $knowledgeDocumentPendingWriter,
        protected KnowledgeDocumentIndexMessageSenderInterface $knowledgeDocumentIndexMessageSender,
        protected AiCommerceConfig $aiCommerceConfig,
        protected AiCommerceRepositoryInterface $aiCommerceRepository,
        protected KnowledgeStorePluginInterface $knowledgeStorePlugin,
    ) {
    }

    public function reindex(
        KnowledgeDocumentReindexRequestTransfer $knowledgeDocumentReindexRequestTransfer,
    ): KnowledgeDocumentReindexResponseTransfer {
        $knowledgeDocumentReindexResponseTransfer = (new KnowledgeDocumentReindexResponseTransfer())
            ->setQueuedDocumentCount(0)
            ->setQueuedDeletedSourceDocumentCount(0);

        $requestedSourceTypes = array_values(array_unique($knowledgeDocumentReindexRequestTransfer->getSourceTypes()));

        foreach ($this->getUnknownSourceTypes($requestedSourceTypes) as $unknownSourceType) {
            $knowledgeDocumentReindexResponseTransfer->addError(
                (new ErrorTransfer())->setMessage(sprintf(static::ERROR_MESSAGE_UNKNOWN_SOURCE_TYPE, $unknownSourceType)),
            );
        }

        if ($knowledgeDocumentReindexResponseTransfer->getErrors()->count() > 0) {
            return $knowledgeDocumentReindexResponseTransfer->setIsSuccessful(false);
        }

        $knowledgeDocumentSourcePlugins = $this->getSourcePlugins($requestedSourceTypes);

        $unconfiguredSourceTypes = $this->getUnconfiguredSourceTypes($knowledgeDocumentSourcePlugins);

        if ($unconfiguredSourceTypes !== []) {
            foreach ($unconfiguredSourceTypes as $unconfiguredSourceType) {
                $knowledgeDocumentReindexResponseTransfer->addError(
                    (new ErrorTransfer())->setMessage(sprintf(static::ERROR_MESSAGE_KNOWLEDGE_STORE_NOT_CONFIGURED, $unconfiguredSourceType)),
                );
            }

            return $knowledgeDocumentReindexResponseTransfer->setIsSuccessful(false);
        }

        $isForced = (bool)$knowledgeDocumentReindexRequestTransfer->getIsForced();

        foreach ($knowledgeDocumentSourcePlugins as $knowledgeDocumentSourcePlugin) {
            $queuedDocumentCount = $this->reindexSource($knowledgeDocumentSourcePlugin, $isForced);

            $knowledgeDocumentReindexResponseTransfer
                ->setQueuedDocumentCount($knowledgeDocumentReindexResponseTransfer->getQueuedDocumentCountOrFail() + $queuedDocumentCount)
                ->addQueuedDocumentCountBySourceType($knowledgeDocumentSourcePlugin->getSourceType(), $queuedDocumentCount);

            $knowledgeDocumentReindexResponseTransfer->setQueuedDeletedSourceDocumentCount(
                $knowledgeDocumentReindexResponseTransfer->getQueuedDeletedSourceDocumentCountOrFail()
                + $this->queueDocumentsOfDeletedSources($knowledgeDocumentSourcePlugin),
            );
        }

        return $knowledgeDocumentReindexResponseTransfer->setIsSuccessful(true);
    }

    protected function reindexSource(
        KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin,
        bool $isForced,
    ): int {
        $pageSize = max(1, $this->aiCommerceConfig->getKnowledgeDocumentReindexPageSize());
        $offset = 0;
        $queuedDocumentCount = 0;

        do {
            $knowledgeDocumentSourceCriteriaTransfer = (new KnowledgeDocumentSourceCriteriaTransfer())
                ->setPagination((new PaginationTransfer())->setLimit($pageSize)->setOffset($offset));

            $knowledgeDocumentSourceTransfers = $knowledgeDocumentSourcePlugin
                ->getDocumentSourceCollection($knowledgeDocumentSourceCriteriaTransfer)
                ->getKnowledgeDocumentSources();

            $sourceIdentifiers = [];

            foreach ($knowledgeDocumentSourceTransfers as $knowledgeDocumentSourceTransfer) {
                $sourceIdentifiers[] = $knowledgeDocumentSourceTransfer->getSourceIdentifierOrFail();
            }

            $knowledgeDocumentTransfers = $this->knowledgeDocumentPendingWriter->writePendingDocuments(
                $knowledgeDocumentSourcePlugin->getSourceType(),
                $sourceIdentifiers,
                isContentHashCleared: $isForced,
            );

            $queuedDocumentCount += $this->knowledgeDocumentIndexMessageSender->sendIndexMessages($knowledgeDocumentTransfers);
            $offset += $pageSize;
        } while ($knowledgeDocumentSourceTransfers->count() === $pageSize);

        return $queuedDocumentCount;
    }

    protected function queueDocumentsOfDeletedSources(KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin): int
    {
        $pageSize = max(1, $this->aiCommerceConfig->getKnowledgeDocumentReindexPageSize());
        $offset = 0;
        $queuedDocumentCount = 0;

        do {
            $knowledgeDocumentTransfers = $this->aiCommerceRepository->getKnowledgeDocumentCollection(
                $this->createKnowledgeDocumentCriteriaTransfer($knowledgeDocumentSourcePlugin->getSourceType(), $pageSize, $offset),
            )->getKnowledgeDocuments();

            $deletedSourceIdentifiers = $this->getDeletedSourceIdentifiers(
                $knowledgeDocumentSourcePlugin,
                $this->extractSourceIdentifiers($knowledgeDocumentTransfers),
            );

            $queuedDocumentCount += $this->knowledgeDocumentIndexMessageSender->sendIndexMessages(
                $this->knowledgeDocumentPendingWriter->writePendingDocuments(
                    $knowledgeDocumentSourcePlugin->getSourceType(),
                    $deletedSourceIdentifiers,
                    isContentHashCleared: false,
                ),
            );
            $offset += $pageSize;
        } while ($knowledgeDocumentTransfers->count() === $pageSize);

        return $queuedDocumentCount;
    }

    protected function createKnowledgeDocumentCriteriaTransfer(
        string $sourceType,
        int $pageSize,
        int $offset,
    ): KnowledgeDocumentCriteriaTransfer {
        return (new KnowledgeDocumentCriteriaTransfer())
            ->setKnowledgeDocumentConditions((new KnowledgeDocumentConditionsTransfer())->addSourceType($sourceType))
            ->setPagination((new PaginationTransfer())->setLimit($pageSize)->setOffset($offset));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     *
     * @return list<string>
     */
    protected function extractSourceIdentifiers(ArrayObject $knowledgeDocumentTransfers): array
    {
        $sourceIdentifiers = [];

        foreach ($knowledgeDocumentTransfers as $knowledgeDocumentTransfer) {
            $sourceIdentifiers[] = $knowledgeDocumentTransfer->getSourceIdentifierOrFail();
        }

        return $sourceIdentifiers;
    }

    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return list<string>
     */
    protected function getDeletedSourceIdentifiers(
        KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin,
        array $sourceIdentifiers,
    ): array {
        if ($sourceIdentifiers === []) {
            return [];
        }

        $knowledgeDocumentSourceTransfers = $knowledgeDocumentSourcePlugin->getDocumentSourceCollection(
            (new KnowledgeDocumentSourceCriteriaTransfer())->setKnowledgeDocumentSourceConditions(
                (new KnowledgeDocumentSourceConditionsTransfer())->setSourceIdentifiers($sourceIdentifiers),
            ),
        )->getKnowledgeDocumentSources();

        $existingSourceIdentifiers = [];

        foreach ($knowledgeDocumentSourceTransfers as $knowledgeDocumentSourceTransfer) {
            $existingSourceIdentifiers[] = $knowledgeDocumentSourceTransfer->getSourceIdentifierOrFail();
        }

        return array_values(array_diff($sourceIdentifiers, $existingSourceIdentifiers));
    }

    /**
     * @param list<string> $requestedSourceTypes
     *
     * @return list<string>
     */
    protected function getUnknownSourceTypes(array $requestedSourceTypes): array
    {
        $unknownSourceTypes = [];

        foreach ($requestedSourceTypes as $requestedSourceType) {
            if ($this->knowledgeDocumentSourcePluginResolver->findSourcePlugin($requestedSourceType) !== null) {
                continue;
            }

            $unknownSourceTypes[] = $requestedSourceType;
        }

        return $unknownSourceTypes;
    }

    /**
     * @param list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface> $knowledgeDocumentSourcePlugins
     *
     * @return list<string>
     */
    protected function getUnconfiguredSourceTypes(array $knowledgeDocumentSourcePlugins): array
    {
        $unconfiguredSourceTypes = [];

        foreach ($knowledgeDocumentSourcePlugins as $knowledgeDocumentSourcePlugin) {
            if ($this->knowledgeStorePlugin->isConfigured($knowledgeDocumentSourcePlugin->getSourceType())) {
                continue;
            }

            $unconfiguredSourceTypes[] = $knowledgeDocumentSourcePlugin->getSourceType();
        }

        return $unconfiguredSourceTypes;
    }

    /**
     * @param list<string> $requestedSourceTypes
     *
     * @return list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface>
     */
    protected function getSourcePlugins(array $requestedSourceTypes): array
    {
        $knowledgeDocumentSourcePlugins = $this->knowledgeDocumentSourcePluginResolver->getSourcePlugins();

        if ($requestedSourceTypes === []) {
            return $knowledgeDocumentSourcePlugins;
        }

        return array_values(array_filter(
            $knowledgeDocumentSourcePlugins,
            fn (KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin): bool => in_array(
                $knowledgeDocumentSourcePlugin->getSourceType(),
                $requestedSourceTypes,
                true,
            ),
        ));
    }
}
