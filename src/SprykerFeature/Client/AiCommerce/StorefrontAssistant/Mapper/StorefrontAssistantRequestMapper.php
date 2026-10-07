<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;

class StorefrontAssistantRequestMapper implements StorefrontAssistantRequestMapperInterface
{
    protected const string PAYLOAD_KEY_MESSAGE = 'message';

    protected const string PAYLOAD_KEY_CONVERSATION_REFERENCE = 'conversationReference';

    protected const string PAYLOAD_KEY_LOCALE_NAME = 'localeName';

    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::MESSAGE_TYPE_USER
     */
    protected const string PROMPT_MESSAGE_TYPE_USER = 'user';

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToStorefrontAssistantChatRequest(
        array $payload,
        string $customerReference
    ): StorefrontAssistantChatRequestTransfer {
        return (new StorefrontAssistantChatRequestTransfer())
            ->setMessage($this->extractString($payload, static::PAYLOAD_KEY_MESSAGE))
            ->setConversationReference($this->extractString($payload, static::PAYLOAD_KEY_CONVERSATION_REFERENCE))
            ->setLocaleName($this->extractString($payload, static::PAYLOAD_KEY_LOCALE_NAME))
            ->setCustomerReference($customerReference);
    }

    /**
     * @param array<string> $toolNames
     */
    public function mapStorefrontAssistantChatRequestToPromptRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer,
        string $aiConfigurationName,
        string $scopedConversationReference,
        array $toolNames = [],
        ?string $systemPrompt = null
    ): PromptRequestTransfer {
        $promptMessageTransfer = (new PromptMessageTransfer())
            ->setType(static::PROMPT_MESSAGE_TYPE_USER)
            ->setContent((string)$storefrontAssistantChatRequestTransfer->getMessage())
            ->setAttachments($storefrontAssistantChatRequestTransfer->getAttachments());

        return (new PromptRequestTransfer())
            ->setPromptMessage($promptMessageTransfer)
            ->setAiConfigurationName($aiConfigurationName)
            ->setToolNames($toolNames)
            ->setConversationReference($scopedConversationReference)
            ->setSystemPrompt($systemPrompt);
    }

    /**
     * @param array<mixed> $payload
     */
    protected function extractString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string)$value);
    }
}
