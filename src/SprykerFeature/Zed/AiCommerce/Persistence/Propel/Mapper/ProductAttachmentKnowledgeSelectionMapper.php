<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionTransfer;
use Generated\Shared\Transfer\ProductAttachmentTransfer;
use Orm\Zed\AiCommerce\Persistence\SpyProductAttachmentKnowledgeSelection;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachment;

class ProductAttachmentKnowledgeSelectionMapper
{
    public function mapProductAttachmentKnowledgeSelectionTransferToEntity(
        ProductAttachmentKnowledgeSelectionTransfer $productAttachmentKnowledgeSelectionTransfer,
        SpyProductAttachmentKnowledgeSelection $productAttachmentKnowledgeSelectionEntity,
    ): SpyProductAttachmentKnowledgeSelection {
        return $productAttachmentKnowledgeSelectionEntity
            ->fromArray($productAttachmentKnowledgeSelectionTransfer->modifiedToArray())
            ->setFkProductAttachment($productAttachmentKnowledgeSelectionTransfer->getIdProductAttachmentOrFail());
    }

    public function mapProductAttachmentKnowledgeSelectionEntityToTransfer(
        SpyProductAttachmentKnowledgeSelection $productAttachmentKnowledgeSelectionEntity,
        ProductAttachmentKnowledgeSelectionTransfer $productAttachmentKnowledgeSelectionTransfer,
    ): ProductAttachmentKnowledgeSelectionTransfer {
        return $productAttachmentKnowledgeSelectionTransfer
            ->fromArray($productAttachmentKnowledgeSelectionEntity->toArray(), true)
            ->setIdProductAttachment($productAttachmentKnowledgeSelectionEntity->getFkProductAttachment());
    }

    /**
     * @module ProductAttachment
     */
    public function mapProductAttachmentEntityToProductAttachmentTransfer(
        SpyProductAttachment $productAttachmentEntity,
        ProductAttachmentTransfer $productAttachmentTransfer,
    ): ProductAttachmentTransfer {
        return $productAttachmentTransfer->fromArray($productAttachmentEntity->toArray(), true);
    }
}
