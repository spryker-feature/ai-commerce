<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment;

use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttachmentExtension\Dependency\Plugin\ProductAttachmentCollectionPostSavePluginInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class KnowledgeSelectionProductAttachmentCollectionPostSavePlugin extends AbstractPlugin implements ProductAttachmentCollectionPostSavePluginInterface
{
    /**
     * {@inheritDoc}
     * - Requires `ProductAttachmentTransfer.idProductAttachment` for each attachment with `ProductAttachmentTransfer.isAssistantKnowledge` set.
     * - Skips the product attachments with `ProductAttachmentTransfer.isAssistantKnowledge` equal to null.
     * - Creates the knowledge selection for a product attachment with true when the selection does not exist.
     * - Deletes the knowledge selection of a product attachment with false when the selection exists.
     * - Does not write an unchanged selection.
     *
     * @api
     */
    public function postSave(ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer): ProductAttachmentCollectionTransfer
    {
        return $this->getBusinessFactory()
            ->createProductAttachmentKnowledgeSelectionWriter()
            ->saveProductAttachmentKnowledgeSelections($productAttachmentCollectionTransfer);
    }
}
