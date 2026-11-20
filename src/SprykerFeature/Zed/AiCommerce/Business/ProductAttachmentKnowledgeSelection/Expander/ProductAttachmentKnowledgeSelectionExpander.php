<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Expander;

use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Reader\ProductAttachmentKnowledgeSelectionReaderInterface;

class ProductAttachmentKnowledgeSelectionExpander implements ProductAttachmentKnowledgeSelectionExpanderInterface
{
    public function __construct(
        protected ProductAttachmentKnowledgeSelectionReaderInterface $productAttachmentKnowledgeSelectionReader,
    ) {
    }

    public function expandProductAttachmentCollection(
        ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer,
    ): ProductAttachmentCollectionTransfer {
        $productAttachmentIds = $this->extractProductAttachmentIds($productAttachmentCollectionTransfer);

        if ($productAttachmentIds === []) {
            return $productAttachmentCollectionTransfer;
        }

        $selectedProductAttachmentIds = $this->productAttachmentKnowledgeSelectionReader->getSelectedProductAttachmentIds($productAttachmentIds);

        foreach ($productAttachmentCollectionTransfer->getProductAttachments() as $productAttachmentTransfer) {
            $idProductAttachment = $productAttachmentTransfer->getIdProductAttachment();

            if ($idProductAttachment === null) {
                continue;
            }

            $productAttachmentTransfer->setIsAssistantKnowledge(isset($selectedProductAttachmentIds[$idProductAttachment]));
        }

        return $productAttachmentCollectionTransfer;
    }

    /**
     * @return list<int>
     */
    protected function extractProductAttachmentIds(ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer): array
    {
        $productAttachmentIds = [];

        foreach ($productAttachmentCollectionTransfer->getProductAttachments() as $productAttachmentTransfer) {
            $idProductAttachment = $productAttachmentTransfer->getIdProductAttachment();

            if ($idProductAttachment !== null) {
                $productAttachmentIds[$idProductAttachment] = $idProductAttachment;
            }
        }

        return array_values($productAttachmentIds);
    }
}
