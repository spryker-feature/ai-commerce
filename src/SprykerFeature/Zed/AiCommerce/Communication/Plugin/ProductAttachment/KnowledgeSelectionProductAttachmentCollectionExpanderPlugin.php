<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment;

use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttachmentExtension\Dependency\Plugin\ProductAttachmentCollectionExpanderPluginInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class KnowledgeSelectionProductAttachmentCollectionExpanderPlugin extends AbstractPlugin implements ProductAttachmentCollectionExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Reads the knowledge selections of all product attachments with an ID in 1 query.
     * - Sets `ProductAttachmentTransfer.isAssistantKnowledge` to true for a selected attachment and to false for a not selected attachment.
     * - Does not change a product attachment without an ID.
     *
     * @api
     */
    public function expand(ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer): ProductAttachmentCollectionTransfer
    {
        return $this->getBusinessFactory()
            ->createProductAttachmentKnowledgeSelectionExpander()
            ->expandProductAttachmentCollection($productAttachmentCollectionTransfer);
    }
}
