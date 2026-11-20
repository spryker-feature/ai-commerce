<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Writer;

use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionConditionsTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Reader\ProductAttachmentKnowledgeSelectionReaderInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;

class ProductAttachmentKnowledgeSelectionWriter implements ProductAttachmentKnowledgeSelectionWriterInterface
{
    use TransactionTrait;

    public function __construct(
        protected ProductAttachmentKnowledgeSelectionReaderInterface $productAttachmentKnowledgeSelectionReader,
        protected AiCommerceEntityManagerInterface $aiCommerceEntityManager,
    ) {
    }

    public function saveProductAttachmentKnowledgeSelections(
        ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer,
    ): ProductAttachmentCollectionTransfer {
        $requestedSelectionStates = $this->getRequestedSelectionStatesIndexedByIdProductAttachment($productAttachmentCollectionTransfer);

        if ($requestedSelectionStates === []) {
            return $productAttachmentCollectionTransfer;
        }

        $this->getTransactionHandler()->handleTransaction(function () use ($requestedSelectionStates): void {
            $this->executeSaveProductAttachmentKnowledgeSelectionsTransaction($requestedSelectionStates);
        });

        return $productAttachmentCollectionTransfer;
    }

    /**
     * @param array<int, bool> $requestedSelectionStates
     */
    protected function executeSaveProductAttachmentKnowledgeSelectionsTransaction(array $requestedSelectionStates): void
    {
        $storedProductAttachmentIds = $this->productAttachmentKnowledgeSelectionReader->getSelectedProductAttachmentIds(array_keys($requestedSelectionStates));
        $productAttachmentIdsToDelete = [];

        foreach ($requestedSelectionStates as $idProductAttachment => $isAssistantKnowledge) {
            $isStored = isset($storedProductAttachmentIds[$idProductAttachment]);

            if ($isAssistantKnowledge && !$isStored) {
                $this->aiCommerceEntityManager->createProductAttachmentKnowledgeSelection(
                    (new ProductAttachmentKnowledgeSelectionTransfer())->setIdProductAttachment($idProductAttachment),
                );
            }

            if (!$isAssistantKnowledge && $isStored) {
                $productAttachmentIdsToDelete[] = $idProductAttachment;
            }
        }

        $this->aiCommerceEntityManager->deleteProductAttachmentKnowledgeSelections(
            (new ProductAttachmentKnowledgeSelectionConditionsTransfer())->setProductAttachmentIds($productAttachmentIdsToDelete),
        );
    }

    /**
     * @return array<int, bool>
     */
    protected function getRequestedSelectionStatesIndexedByIdProductAttachment(
        ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer,
    ): array {
        $requestedSelectionStates = [];

        foreach ($productAttachmentCollectionTransfer->getProductAttachments() as $productAttachmentTransfer) {
            $isAssistantKnowledge = $productAttachmentTransfer->getIsAssistantKnowledge();

            if ($isAssistantKnowledge === null) {
                continue;
            }

            $requestedSelectionStates[$productAttachmentTransfer->getIdProductAttachmentOrFail()] = $isAssistantKnowledge;
        }

        return $requestedSelectionStates;
    }
}
