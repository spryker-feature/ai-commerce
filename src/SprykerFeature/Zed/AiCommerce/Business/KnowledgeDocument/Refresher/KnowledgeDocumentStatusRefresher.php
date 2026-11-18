<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Refresher;

use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentStatusRefreshResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class KnowledgeDocumentStatusRefresher implements KnowledgeDocumentStatusRefresherInterface
{
    use LoggerTrait;
    use TransactionTrait;

    protected const string LOG_MESSAGE_REFRESH_FAILURE = 'Knowledge document status refresh failed: %s';

    protected const string LOG_MESSAGE_INDEX_FAILURE = 'The knowledge store failed to index the knowledge document for source type "%s", source identifier "%s": %s';

    protected const string LOG_CONTEXT_SOURCE_TYPE = 'sourceType';

    protected const string LOG_CONTEXT_SOURCE_IDENTIFIER = 'sourceIdentifier';

    protected const string ERROR_MESSAGE_SEPARATOR = '; ';

    protected const int ERROR_MESSAGE_MAX_LENGTH = 1024;

    public function __construct(
        protected KnowledgeStorePluginInterface $knowledgeStorePlugin,
        protected AiCommerceRepositoryInterface $aiCommerceRepository,
        protected AiCommerceEntityManagerInterface $aiCommerceEntityManager,
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function refreshStatuses(): KnowledgeDocumentStatusRefreshResponseTransfer
    {
        $knowledgeDocumentStatusRefreshResponseTransfer = (new KnowledgeDocumentStatusRefreshResponseTransfer())
            ->setIsSuccessful(true)
            ->setCheckedDocumentCount(0)
            ->setIndexedDocumentCount(0)
            ->setFailedDocumentCount(0);
        $pageSize = max(1, $this->aiCommerceConfig->getKnowledgeDocumentStatusRefreshPageSize());
        $offset = 0;

        do {
            $knowledgeDocumentTransfers = $this->getProcessingDocuments($pageSize, $offset)->getKnowledgeDocuments();

            if ($knowledgeDocumentTransfers->count() === 0) {
                return $knowledgeDocumentStatusRefreshResponseTransfer;
            }

            $knowledgeDocumentCollectionResponseTransfer = $this->knowledgeStorePlugin->refreshDocumentStatuses(
                (new KnowledgeDocumentCollectionTransfer())->setKnowledgeDocuments($knowledgeDocumentTransfers),
            );

            if (!$knowledgeDocumentCollectionResponseTransfer->getIsSuccessful()) {
                return $this->addRefreshFailure($knowledgeDocumentStatusRefreshResponseTransfer, $knowledgeDocumentCollectionResponseTransfer);
            }

            $knowledgeDocumentStatusRefreshResponseTransfer->setCheckedDocumentCount(
                $knowledgeDocumentStatusRefreshResponseTransfer->getCheckedDocumentCountOrFail() + $knowledgeDocumentTransfers->count(),
            );
            $changedDocumentCount = $this->getTransactionHandler()->handleTransaction(
                fn (): int => $this->saveChangedStatuses($knowledgeDocumentCollectionResponseTransfer, $knowledgeDocumentStatusRefreshResponseTransfer),
            );
            $stillProcessingDocumentCount = $knowledgeDocumentTransfers->count() - $changedDocumentCount;
            $offset += $stillProcessingDocumentCount;
        } while ($knowledgeDocumentTransfers->count() === $pageSize);

        return $knowledgeDocumentStatusRefreshResponseTransfer;
    }

    protected function getProcessingDocuments(int $pageSize, int $offset): KnowledgeDocumentCollectionTransfer
    {
        return $this->aiCommerceRepository->getKnowledgeDocumentCollection(
            (new KnowledgeDocumentCriteriaTransfer())
                ->setKnowledgeDocumentConditions(
                    (new KnowledgeDocumentConditionsTransfer())
                        ->addStatus(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING),
                )
                ->setPagination((new PaginationTransfer())->setLimit($pageSize)->setOffset($offset)),
        );
    }

    protected function saveChangedStatuses(
        KnowledgeDocumentCollectionResponseTransfer $knowledgeDocumentCollectionResponseTransfer,
        KnowledgeDocumentStatusRefreshResponseTransfer $knowledgeDocumentStatusRefreshResponseTransfer,
    ): int {
        $changedDocumentCount = 0;

        foreach ($knowledgeDocumentCollectionResponseTransfer->getKnowledgeDocuments() as $knowledgeDocumentTransfer) {
            if ($knowledgeDocumentTransfer->getStatus() === SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED) {
                $this->saveIndexedDocument($knowledgeDocumentTransfer);
                $knowledgeDocumentStatusRefreshResponseTransfer->setIndexedDocumentCount(
                    $knowledgeDocumentStatusRefreshResponseTransfer->getIndexedDocumentCountOrFail() + 1,
                );
                $changedDocumentCount++;

                continue;
            }

            if ($knowledgeDocumentTransfer->getStatus() === SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED) {
                $this->saveFailedDocument($knowledgeDocumentTransfer);
                $knowledgeDocumentStatusRefreshResponseTransfer->setFailedDocumentCount(
                    $knowledgeDocumentStatusRefreshResponseTransfer->getFailedDocumentCountOrFail() + 1,
                );
                $changedDocumentCount++;
            }
        }

        return $changedDocumentCount;
    }

    protected function saveIndexedDocument(KnowledgeDocumentTransfer $knowledgeDocumentTransfer): void
    {
        $this->aiCommerceEntityManager->saveKnowledgeDocument(
            $knowledgeDocumentTransfer
                ->setAttemptCount(0)
                ->setErrorMessage(null),
        );
    }

    protected function saveFailedDocument(KnowledgeDocumentTransfer $knowledgeDocumentTransfer): void
    {
        $errorMessage = mb_substr((string)$knowledgeDocumentTransfer->getErrorMessage(), 0, static::ERROR_MESSAGE_MAX_LENGTH);

        $this->getLogger()->error(
            sprintf(static::LOG_MESSAGE_INDEX_FAILURE, $knowledgeDocumentTransfer->getSourceTypeOrFail(), $knowledgeDocumentTransfer->getSourceIdentifierOrFail(), $errorMessage),
            [
                static::LOG_CONTEXT_SOURCE_TYPE => $knowledgeDocumentTransfer->getSourceTypeOrFail(),
                static::LOG_CONTEXT_SOURCE_IDENTIFIER => $knowledgeDocumentTransfer->getSourceIdentifierOrFail(),
            ],
        );

        $this->aiCommerceEntityManager->saveKnowledgeDocument(
            $knowledgeDocumentTransfer
                ->setErrorMessage($errorMessage)
                ->setContentHash(null),
        );
    }

    protected function addRefreshFailure(
        KnowledgeDocumentStatusRefreshResponseTransfer $knowledgeDocumentStatusRefreshResponseTransfer,
        KnowledgeDocumentCollectionResponseTransfer $knowledgeDocumentCollectionResponseTransfer,
    ): KnowledgeDocumentStatusRefreshResponseTransfer {
        $messages = [];

        foreach ($knowledgeDocumentCollectionResponseTransfer->getErrors() as $errorTransfer) {
            $knowledgeDocumentStatusRefreshResponseTransfer->addError($errorTransfer);
            $messages[] = (string)$errorTransfer->getMessage();
        }

        $this->getLogger()->error(sprintf(static::LOG_MESSAGE_REFRESH_FAILURE, implode(static::ERROR_MESSAGE_SEPARATOR, $messages)));

        return $knowledgeDocumentStatusRefreshResponseTransfer->setIsSuccessful(false);
    }
}
