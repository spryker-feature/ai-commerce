<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttachment\Dependency\ProductAttachmentEvents;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Dependency\AiCommerceEvents;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class ProductAttachmentKnowledgeDocumentSourcePlugin extends AbstractPlugin implements KnowledgeDocumentSourcePluginInterface
{
    /**
     * {@inheritDoc}
     * - Returns `product_attachment`.
     *
     * @api
     */
    public function getSourceType(): string
    {
        return SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT;
    }

    /**
     * {@inheritDoc}
     * - Returns the create, update and delete events of `spy_product_attachment`, and the product abstract attachment publish event of the data import.
     * - Returns the create and delete events of `spy_product_attachment_knowledge_selection`, and the knowledge selection publish event of the data import.
     *
     * @api
     *
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_CREATE,
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_UPDATE,
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_DELETE,
            ProductAttachmentEvents::PRODUCT_ABSTRACT_ATTACHMENT_PUBLISH,
            AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CREATE,
            AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_DELETE,
            AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH,
        ];
    }

    /**
     * {@inheritDoc}
     * - Returns the product attachment IDs of the events as strings.
     * - Takes the ID from `foreignKeys['spy_product_attachment_knowledge_selection.fk_product_attachment']` for the knowledge selection entity events, and from `id` for all other events.
     * - Skips an event without the product attachment ID.
     *
     * @api
     *
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return list<string>
     */
    public function extractSourceIdentifiers(array $eventEntityTransfers, string $eventName): array
    {
        return $this->getBusinessFactory()
            ->createProductAttachmentDocumentSourceReader()
            ->extractSourceIdentifiers($eventEntityTransfers, $eventName);
    }

    /**
     * {@inheritDoc}
     * - Returns only the product attachments that are selected as assistant knowledge, in 1 query.
     * - Sorts the sources by the product attachment ID ascending.
     * - Sets the attachment label as document name, the attachment locale name (null for all locales) and the attachment URL.
     *
     * @api
     */
    public function getDocumentSourceCollection(
        KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer,
    ): KnowledgeDocumentSourceCollectionTransfer {
        return $this->getBusinessFactory()
            ->createProductAttachmentDocumentSourceReader()
            ->getDocumentSourceCollection($knowledgeDocumentSourceCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     * - Downloads the attachment URL only from a host that resolves to public addresses.
     * - Follows no redirect and stops above `AiCommerceConfig::getKnowledgeDocumentMaxFileSize()` bytes.
     * - Takes the MIME type from the `Content-Type` header, or from the file extension.
     *
     * @api
     */
    public function readDocumentContent(
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer,
    ): KnowledgeDocumentContentTransfer {
        return $this->getBusinessFactory()
            ->createUrlDocumentContentReader()
            ->readDocumentContent($knowledgeDocumentSourceTransfer);
    }
}
