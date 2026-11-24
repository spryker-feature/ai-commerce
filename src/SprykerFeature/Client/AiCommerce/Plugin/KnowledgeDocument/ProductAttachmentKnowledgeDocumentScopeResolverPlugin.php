<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentScopeTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use SprykerFeature\Client\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentScopeResolverPluginInterface;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class ProductAttachmentKnowledgeDocumentScopeResolverPlugin extends AbstractPlugin implements KnowledgeDocumentScopeResolverPluginInterface
{
    /**
     * {@inheritDoc}
     * - Returns true when `KnowledgeDocumentScopeRequest.scopeReferences` has values for `product_abstract_id`, `abstract_sku` or `concrete_sku`.
     *
     * @api
     */
    public function isApplicable(KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer): bool
    {
        return $this->getFactory()
            ->createProductAttachmentDocumentScopeResolver()
            ->isApplicable($knowledgeDocumentScopeRequestTransfer);
    }

    /**
     * {@inheritDoc}
     * - Requires `KnowledgeDocumentScopeRequest.localeName` and `storeName`.
     * - Resolves abstract and concrete SKUs to abstract products through the product storage `sku` mappings.
     * - Skips products that are restricted or not available in the store and locale, with a customer safe message.
     * - Reads the attachments of each product from the product attachment storage, for the locale and the default locale.
     * - Returns the document keys `product_attachment:{idProductAttachment}`.
     * - Returns a knowledge document source per document key, with the attachment label as document name and the attachment URL.
     * - Keeps only the attachments listed in `sourceIdentifiers` when the list is not empty. Never adds other attachments.
     *
     * @api
     */
    public function resolveDocumentScope(
        KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer
    ): KnowledgeDocumentScopeTransfer {
        return $this->getFactory()
            ->createProductAttachmentDocumentScopeResolver()
            ->resolveDocumentScope($knowledgeDocumentScopeRequestTransfer);
    }
}
