<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

use Generated\Shared\Transfer\ConversationHistoryTransfer;

interface ConversationMessageExtractorInterface
{
    /**
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer>
     */
    public function extractConversationMessagesFromConversationHistory(
        ConversationHistoryTransfer $conversationHistoryTransfer
    ): array;

    /**
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer>
     */
    public function extractConversationMessagesFromOriginalMessages(string $originalMessages): array;
}
