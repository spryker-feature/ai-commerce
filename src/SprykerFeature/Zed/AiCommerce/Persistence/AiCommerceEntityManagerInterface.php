<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;

interface AiCommerceEntityManagerInterface
{
    /**
     * Specification:
     * - Persists a new conversation record linked to a backoffice user.
     * - Returns the transfer with the generated primary key set.
     */
    public function createBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): BackofficeAssistantConversationTransfer;

    /**
     * Specification:
     * - Updates conversation fields identified by conversationReference.
     */
    public function updateBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): void;

    /**
     * Specification:
     * - Deletes the conversation record identified by conversationReference.
     */
    public function deleteBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): void;
}
