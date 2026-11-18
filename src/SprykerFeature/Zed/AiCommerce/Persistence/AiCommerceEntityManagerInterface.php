<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;

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
}
