<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Exception\StorefrontAssistantConversationScopeException;

class ConversationReferenceDeriver implements ConversationReferenceDeriverInterface
{
    protected const string HASH_ALGORITHM = 'sha256';

    protected const string REFERENCE_PREFIX = 'ba_';

    protected const string SEGMENT_SEPARATOR = "\0";

    protected const string ERROR_MESSAGE_BLANK_CUSTOMER_REFERENCE = 'A storefront assistant conversation cannot be addressed without a customer reference.';

    protected const string ERROR_MESSAGE_BLANK_CONVERSATION_REFERENCE = 'A storefront assistant conversation cannot be addressed without a conversation reference.';

    /**
     * @throws \SprykerFeature\Client\AiCommerce\StorefrontAssistant\Exception\StorefrontAssistantConversationScopeException
     */
    public function deriveConversationReference(string $customerReference, string $conversationReference): string
    {
        $customerReference = trim($customerReference);
        $conversationReference = trim($conversationReference);

        if ($customerReference === '') {
            throw new StorefrontAssistantConversationScopeException(static::ERROR_MESSAGE_BLANK_CUSTOMER_REFERENCE);
        }

        if ($conversationReference === '') {
            throw new StorefrontAssistantConversationScopeException(static::ERROR_MESSAGE_BLANK_CONVERSATION_REFERENCE);
        }

        $derivedReference = hash(
            static::HASH_ALGORITHM,
            $customerReference . static::SEGMENT_SEPARATOR . $conversationReference,
        );

        return static::REFERENCE_PREFIX . $derivedReference;
    }
}
