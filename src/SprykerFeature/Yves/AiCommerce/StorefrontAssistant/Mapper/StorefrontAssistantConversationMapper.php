<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationTransfer;

class StorefrontAssistantConversationMapper implements StorefrontAssistantConversationMapperInterface
{
    protected const string RESPONSE_KEY_CONVERSATIONS = 'conversations';

    protected const string CONVERSATION_KEY_REFERENCE = 'conversationReference';

    protected const string CONVERSATION_KEY_NAME = 'name';

    protected const string CONVERSATION_KEY_UPDATED_AT = 'updatedAt';

    protected const string CONVERSATION_KEY_MESSAGES = 'messages';

    protected const string MESSAGE_KEY_ROLE = 'role';

    protected const string MESSAGE_KEY_CONTENT = 'content';

    protected const string MESSAGE_KEY_ATTACHMENTS = 'attachments';

    protected const string ATTACHMENT_KEY_NAME = 'name';

    protected const string MESSAGE_KEY_TOOL_INVOCATIONS = 'toolInvocations';

    protected const string TOOL_INVOCATION_KEY_NAME = 'name';

    protected const string TOOL_INVOCATION_KEY_RESULT = 'result';

    /**
     * @return array<string, mixed>
     */
    public function mapStorefrontAssistantConversationCollectionToArray(
        StorefrontAssistantConversationCollectionTransfer $storefrontAssistantConversationCollectionTransfer
    ): array {
        $conversations = [];

        foreach ($storefrontAssistantConversationCollectionTransfer->getStorefrontAssistantConversations() as $storefrontAssistantConversationTransfer) {
            $conversations[] = $this->mapConversationToArray($storefrontAssistantConversationTransfer);
        }

        return [static::RESPONSE_KEY_CONVERSATIONS => $conversations];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapStorefrontAssistantConversationMessageCollectionToArray(
        StorefrontAssistantConversationMessageCollectionTransfer $storefrontAssistantConversationMessageCollectionTransfer
    ): array {
        $messages = [];

        foreach ($storefrontAssistantConversationMessageCollectionTransfer->getMessages() as $storefrontAssistantConversationMessageTransfer) {
            $messages[] = $this->mapConversationMessageToArray($storefrontAssistantConversationMessageTransfer);
        }

        return [
            static::CONVERSATION_KEY_REFERENCE => (string)$storefrontAssistantConversationMessageCollectionTransfer->getConversationReference(),
            static::CONVERSATION_KEY_MESSAGES => $messages,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapConversationToArray(StorefrontAssistantConversationTransfer $storefrontAssistantConversationTransfer): array
    {
        return [
            static::CONVERSATION_KEY_REFERENCE => (string)$storefrontAssistantConversationTransfer->getConversationReference(),
            static::CONVERSATION_KEY_NAME => (string)$storefrontAssistantConversationTransfer->getName(),
            static::CONVERSATION_KEY_UPDATED_AT => (int)$storefrontAssistantConversationTransfer->getUpdatedAt(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapConversationMessageToArray(
        StorefrontAssistantConversationMessageTransfer $storefrontAssistantConversationMessageTransfer
    ): array {
        $attachments = [];

        foreach ($storefrontAssistantConversationMessageTransfer->getAttachments() as $attachmentTransfer) {
            $attachments[] = [static::ATTACHMENT_KEY_NAME => (string)$attachmentTransfer->getFilename()];
        }

        $toolInvocations = [];

        foreach ($storefrontAssistantConversationMessageTransfer->getToolInvocations() as $toolInvocationTransfer) {
            $toolInvocations[] = [
                static::TOOL_INVOCATION_KEY_NAME => (string)$toolInvocationTransfer->getName(),
                static::TOOL_INVOCATION_KEY_RESULT => (string)$toolInvocationTransfer->getResult(),
            ];
        }

        return [
            static::MESSAGE_KEY_ROLE => (string)$storefrontAssistantConversationMessageTransfer->getRole(),
            static::MESSAGE_KEY_CONTENT => (string)$storefrontAssistantConversationMessageTransfer->getContent(),
            static::MESSAGE_KEY_ATTACHMENTS => $attachments,
            static::MESSAGE_KEY_TOOL_INVOCATIONS => $toolInvocations,
        ];
    }
}
