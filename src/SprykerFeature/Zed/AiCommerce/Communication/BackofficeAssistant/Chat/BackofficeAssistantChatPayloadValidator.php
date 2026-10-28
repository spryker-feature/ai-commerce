<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat;

class BackofficeAssistantChatPayloadValidator implements BackofficeAssistantChatPayloadValidatorInterface
{
    protected const string PAYLOAD_KEY_MESSAGE = 'message';

    protected const string PAYLOAD_KEY_CONVERSATION_REFERENCES = 'conversationReferences';

    public function isPromptPayloadValid(array $payload): bool
    {
        $message = $payload[static::PAYLOAD_KEY_MESSAGE] ?? null;

        return is_string($message) && trim($message) !== '';
    }

    public function isConversationDeletePayloadValid(array $payload): bool
    {
        $conversationReferences = $payload[static::PAYLOAD_KEY_CONVERSATION_REFERENCES] ?? null;

        if (!is_array($conversationReferences) || $conversationReferences === []) {
            return false;
        }

        foreach ($conversationReferences as $conversationReference) {
            if (!is_string($conversationReference) || $conversationReference === '') {
                return false;
            }
        }

        return true;
    }
}
