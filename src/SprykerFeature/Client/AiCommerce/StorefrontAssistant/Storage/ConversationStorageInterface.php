<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage;

use Generated\Shared\Transfer\StorefrontAssistantConversationTransfer;

interface ConversationStorageInterface
{
    public function findConversation(
        string $customerReference,
        string $conversationReference
    ): ?StorefrontAssistantConversationTransfer;

    public function saveConversation(
        string $customerReference,
        string $conversationReference,
        StorefrontAssistantConversationTransfer $storefrontAssistantConversationTransfer
    ): void;

    /**
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationTransfer>
     */
    public function getConversations(string $customerReference, int $limit): array;

    public function hasConversation(string $customerReference, string $conversationReference): bool;

    public function deleteConversation(string $customerReference, string $conversationReference): bool;
}
