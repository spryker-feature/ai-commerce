<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

use ArrayObject;
use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer;
use Generated\Shared\Transfer\ToolInvocationTransfer;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantMessageRole;

class ConversationMessageExtractor implements ConversationMessageExtractorInterface
{
    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::MESSAGE_TYPE_TOOL_CALL
     */
    protected const string MESSAGE_TYPE_TOOL_CALL = 'tool_call';

    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::MESSAGE_TYPE_TOOL_RESULT
     */
    protected const string MESSAGE_TYPE_TOOL_RESULT = 'tool_result';

    protected const string KEY_ROLE = 'role';

    protected const string KEY_TYPE = 'type';

    protected const string KEY_CONTENT = 'content';

    protected const string KEY_TOOLS = 'tools';

    protected const string KEY_NAME = 'name';

    protected const string KEY_RESULT = 'result';

    protected const string MESSAGE_TYPE_VENDOR_TOOL_RESULT = 'tool_call_result';

    public function extractConversationMessagesFromOriginalMessages(string $originalMessages): array
    {
        $decodedMessages = json_decode($originalMessages, true);

        if (!is_array($decodedMessages)) {
            return [];
        }

        $storefrontAssistantConversationMessageTransfers = [];

        foreach ($decodedMessages as $decodedMessage) {
            if (!is_array($decodedMessage)) {
                continue;
            }

            $storefrontAssistantConversationMessageTransfer = $this->mapMessage($decodedMessage);

            if ($storefrontAssistantConversationMessageTransfer === null) {
                continue;
            }

            $storefrontAssistantConversationMessageTransfers[] = $storefrontAssistantConversationMessageTransfer;
        }

        return $storefrontAssistantConversationMessageTransfers;
    }

    public function extractConversationMessagesFromConversationHistory(
        ConversationHistoryTransfer $conversationHistoryTransfer
    ): array {
        if ($conversationHistoryTransfer->getMessages()->count() > 0) {
            return $this->mapPromptMessages($conversationHistoryTransfer->getMessages()->getArrayCopy());
        }

        return $this->extractConversationMessagesFromOriginalMessages(
            (string)$conversationHistoryTransfer->getOriginalMessages(),
        );
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\PromptMessageTransfer> $promptMessageTransfers
     *
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer>
     */
    protected function mapPromptMessages(array $promptMessageTransfers): array
    {
        $storefrontAssistantConversationMessageTransfers = [];

        foreach ($promptMessageTransfers as $promptMessageTransfer) {
            $storefrontAssistantConversationMessageTransfer = $this->mapPromptMessage($promptMessageTransfer);

            if ($storefrontAssistantConversationMessageTransfer === null) {
                continue;
            }

            $storefrontAssistantConversationMessageTransfers[] = $storefrontAssistantConversationMessageTransfer;
        }

        return $storefrontAssistantConversationMessageTransfers;
    }

    protected function mapPromptMessage(
        PromptMessageTransfer $promptMessageTransfer
    ): ?StorefrontAssistantConversationMessageTransfer {
        $type = (string)$promptMessageTransfer->getType();

        if ($type === static::MESSAGE_TYPE_TOOL_CALL) {
            return null;
        }

        if ($type === static::MESSAGE_TYPE_TOOL_RESULT || $type === static::MESSAGE_TYPE_VENDOR_TOOL_RESULT) {
            return $this->mapPromptToolResultMessage($promptMessageTransfer);
        }

        $content = trim((string)$promptMessageTransfer->getContent());

        if ($content === '') {
            return null;
        }

        return (new StorefrontAssistantConversationMessageTransfer())
            ->setRole($type === StorefrontAssistantMessageRole::User->value ? StorefrontAssistantMessageRole::User->value : StorefrontAssistantMessageRole::Assistant->value)
            ->setContent($content)
            ->setIsTruncated(false);
    }

    protected function mapPromptToolResultMessage(
        PromptMessageTransfer $promptMessageTransfer
    ): ?StorefrontAssistantConversationMessageTransfer {
        $toolInvocationTransfers = [];

        foreach ($promptMessageTransfer->getToolInvocations() as $toolInvocationTransfer) {
            if (trim((string)$toolInvocationTransfer->getResult()) === '') {
                continue;
            }

            $toolInvocationTransfers[] = $toolInvocationTransfer;
        }

        if ($toolInvocationTransfers === []) {
            return null;
        }

        return (new StorefrontAssistantConversationMessageTransfer())
            ->setRole(StorefrontAssistantMessageRole::ToolResult->value)
            ->setContent(trim((string)$promptMessageTransfer->getContent()))
            ->setIsTruncated(false)
            ->setToolInvocations(new ArrayObject($toolInvocationTransfers));
    }

    /**
     * @param array<mixed> $decodedMessage
     */
    protected function mapMessage(array $decodedMessage): ?StorefrontAssistantConversationMessageTransfer
    {
        $rawType = (string)($decodedMessage[static::KEY_TYPE] ?? '');

        if ($rawType === static::MESSAGE_TYPE_TOOL_CALL) {
            return null;
        }

        if ($rawType === static::MESSAGE_TYPE_VENDOR_TOOL_RESULT) {
            return $this->mapToolResultMessage($decodedMessage);
        }

        return $this->mapTextMessage($decodedMessage);
    }

    /**
     * @param array<mixed> $decodedMessage
     */
    protected function mapTextMessage(array $decodedMessage): ?StorefrontAssistantConversationMessageTransfer
    {
        $content = $this->extractContent($decodedMessage);

        if ($content === '') {
            return null;
        }

        return (new StorefrontAssistantConversationMessageTransfer())
            ->setRole($this->resolveRole($decodedMessage))
            ->setContent($content)
            ->setIsTruncated(false);
    }

    /**
     * @param array<mixed> $decodedMessage
     */
    protected function mapToolResultMessage(array $decodedMessage): ?StorefrontAssistantConversationMessageTransfer
    {
        $toolInvocationTransfers = $this->mapToolInvocations($decodedMessage);

        if ($toolInvocationTransfers === []) {
            return null;
        }

        return (new StorefrontAssistantConversationMessageTransfer())
            ->setRole(StorefrontAssistantMessageRole::ToolResult->value)
            ->setContent($this->extractContent($decodedMessage))
            ->setIsTruncated(false)
            ->setToolInvocations(new ArrayObject($toolInvocationTransfers));
    }

    /**
     * @param array<mixed> $decodedMessage
     *
     * @return array<int, \Generated\Shared\Transfer\ToolInvocationTransfer>
     */
    protected function mapToolInvocations(array $decodedMessage): array
    {
        $tools = $decodedMessage[static::KEY_TOOLS] ?? [];

        if (!is_array($tools)) {
            return [];
        }

        $toolInvocationTransfers = [];

        foreach ($tools as $tool) {
            if (!is_array($tool)) {
                continue;
            }

            $result = $tool[static::KEY_RESULT] ?? null;

            if (!is_string($result) || $result === '') {
                continue;
            }

            $toolInvocationTransfers[] = (new ToolInvocationTransfer())
                ->setName((string)($tool[static::KEY_NAME] ?? ''))
                ->setResult($result)
                ->setIsSuccessful(true);
        }

        return $toolInvocationTransfers;
    }

    /**
     * @param array<mixed> $decodedMessage
     */
    protected function extractContent(array $decodedMessage): string
    {
        $content = $decodedMessage[static::KEY_CONTENT] ?? null;

        if (is_string($content)) {
            return trim($content);
        }

        if (!is_array($content)) {
            return '';
        }

        $contentParts = [];

        foreach ($content as $contentBlock) {
            if (is_string($contentBlock)) {
                $contentParts[] = $contentBlock;

                continue;
            }

            if (!is_array($contentBlock)) {
                continue;
            }

            $blockContent = $contentBlock[static::KEY_CONTENT] ?? null;

            if (is_string($blockContent) && $blockContent !== '') {
                $contentParts[] = $blockContent;
            }
        }

        return trim(implode('', $contentParts));
    }

    /**
     * @param array<mixed> $decodedMessage
     */
    protected function resolveRole(array $decodedMessage): string
    {
        $rawRole = (string)($decodedMessage[static::KEY_ROLE] ?? '');

        if ($rawRole === StorefrontAssistantMessageRole::User->value) {
            return StorefrontAssistantMessageRole::User->value;
        }

        return StorefrontAssistantMessageRole::Assistant->value;
    }
}
