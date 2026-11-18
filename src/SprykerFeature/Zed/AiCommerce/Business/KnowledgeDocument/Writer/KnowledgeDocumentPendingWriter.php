<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer;

use Generated\Shared\Transfer\KnowledgeDocumentConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentIndexer;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class KnowledgeDocumentPendingWriter implements KnowledgeDocumentPendingWriterInterface
{
    use TransactionTrait;

    public function __construct(
        protected AiCommerceRepositoryInterface $aiCommerceRepository,
        protected AiCommerceEntityManagerInterface $aiCommerceEntityManager,
    ) {
    }

    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer>
     */
    public function writePendingDocuments(string $sourceType, array $sourceIdentifiers, bool $isContentHashCleared): array
    {
        $sourceIdentifiers = array_values(array_unique($sourceIdentifiers));

        if ($sourceIdentifiers === []) {
            return [];
        }

        return $this->getTransactionHandler()->handleTransaction(function () use ($sourceType, $sourceIdentifiers, $isContentHashCleared): array {
            return $this->executeWritePendingDocumentsTransaction($sourceType, $sourceIdentifiers, $isContentHashCleared);
        });
    }

    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer>
     */
    protected function executeWritePendingDocumentsTransaction(string $sourceType, array $sourceIdentifiers, bool $isContentHashCleared): array
    {
        $knowledgeDocumentTransfersBySourceIdentifier = $this->getKnowledgeDocumentTransfersBySourceIdentifier($sourceType, $sourceIdentifiers);
        $pendingKnowledgeDocumentTransfers = [];

        foreach ($sourceIdentifiers as $sourceIdentifier) {
            $knowledgeDocumentTransfer = $knowledgeDocumentTransfersBySourceIdentifier[$sourceIdentifier]
                ?? (new KnowledgeDocumentTransfer())->setSourceType($sourceType)->setSourceIdentifier($sourceIdentifier);

            $knowledgeDocumentTransfer
                ->setStatus(KnowledgeDocumentIndexer::STATUS_PENDING)
                ->setAttemptCount(0)
                ->setErrorMessage(null);

            if ($isContentHashCleared) {
                $knowledgeDocumentTransfer->setContentHash(null);
            }

            $pendingKnowledgeDocumentTransfers[] = $knowledgeDocumentTransfer;
        }

        $this->aiCommerceEntityManager->saveKnowledgeDocumentCollection($sourceType, $pendingKnowledgeDocumentTransfers);

        return $pendingKnowledgeDocumentTransfers;
    }

    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return array<string, \Generated\Shared\Transfer\KnowledgeDocumentTransfer>
     */
    protected function getKnowledgeDocumentTransfersBySourceIdentifier(string $sourceType, array $sourceIdentifiers): array
    {
        $knowledgeDocumentCriteriaTransfer = (new KnowledgeDocumentCriteriaTransfer())
            ->setKnowledgeDocumentConditions(
                (new KnowledgeDocumentConditionsTransfer())
                    ->addSourceType($sourceType)
                    ->setSourceIdentifiers($sourceIdentifiers),
            );

        $knowledgeDocumentTransfers = [];

        foreach ($this->aiCommerceRepository->getKnowledgeDocumentCollection($knowledgeDocumentCriteriaTransfer)->getKnowledgeDocuments() as $knowledgeDocumentTransfer) {
            $knowledgeDocumentTransfers[$knowledgeDocumentTransfer->getSourceIdentifierOrFail()] = $knowledgeDocumentTransfer;
        }

        return $knowledgeDocumentTransfers;
    }
}
