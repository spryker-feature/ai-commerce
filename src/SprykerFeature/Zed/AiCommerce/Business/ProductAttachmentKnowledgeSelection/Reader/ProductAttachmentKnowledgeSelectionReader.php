<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Reader;

use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionConditionsTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionCriteriaTransfer;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class ProductAttachmentKnowledgeSelectionReader implements ProductAttachmentKnowledgeSelectionReaderInterface
{
    public function __construct(protected AiCommerceRepositoryInterface $aiCommerceRepository)
    {
    }

    /**
     * @param list<int> $productAttachmentIds
     *
     * @return array<int, int>
     */
    public function getSelectedProductAttachmentIds(array $productAttachmentIds): array
    {
        if ($productAttachmentIds === []) {
            return [];
        }

        $productAttachmentKnowledgeSelectionCollectionTransfer = $this->aiCommerceRepository->getProductAttachmentKnowledgeSelectionCollection(
            (new ProductAttachmentKnowledgeSelectionCriteriaTransfer())->setProductAttachmentKnowledgeSelectionConditions(
                (new ProductAttachmentKnowledgeSelectionConditionsTransfer())->setProductAttachmentIds($productAttachmentIds),
            ),
        );

        $selectedProductAttachmentIds = [];

        foreach ($productAttachmentKnowledgeSelectionCollectionTransfer->getProductAttachmentKnowledgeSelections() as $productAttachmentKnowledgeSelectionTransfer) {
            $idProductAttachment = $productAttachmentKnowledgeSelectionTransfer->getIdProductAttachmentOrFail();
            $selectedProductAttachmentIds[$idProductAttachment] = $idProductAttachment;
        }

        return $selectedProductAttachmentIds;
    }
}
