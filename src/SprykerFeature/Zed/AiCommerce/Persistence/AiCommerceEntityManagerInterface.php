<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionConditionsTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionTransfer;

interface AiCommerceEntityManagerInterface
{
    public function createBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): BackofficeAssistantConversationTransfer;

    public function updateBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): void;

    public function deleteBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): void;

    public function saveKnowledgeDocument(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
    ): KnowledgeDocumentTransfer;

    /**
     * @param list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     */
    public function saveKnowledgeDocumentCollection(string $sourceType, array $knowledgeDocumentTransfers): void;

    public function deleteKnowledgeDocument(KnowledgeDocumentTransfer $knowledgeDocumentTransfer): void;

    /**
     * Specification:
     * - Creates the knowledge selection of the product attachment `ProductAttachmentKnowledgeSelection.idProductAttachment`.
     * - Triggers the `Entity.spy_product_attachment_knowledge_selection.create` event.
     */
    public function createProductAttachmentKnowledgeSelection(
        ProductAttachmentKnowledgeSelectionTransfer $productAttachmentKnowledgeSelectionTransfer,
    ): ProductAttachmentKnowledgeSelectionTransfer;

    /**
     * Specification:
     * - Deletes the knowledge selections of the product attachments `ProductAttachmentKnowledgeSelectionConditions.productAttachmentIds`.
     * - Does nothing when no product attachment ID is provided.
     * - Triggers the `Entity.spy_product_attachment_knowledge_selection.delete` event for each deleted selection.
     */
    public function deleteProductAttachmentKnowledgeSelections(
        ProductAttachmentKnowledgeSelectionConditionsTransfer $productAttachmentKnowledgeSelectionConditionsTransfer,
    ): void;
}
