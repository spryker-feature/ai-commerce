<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator;

use SprykerFeature\Client\AiCommerce\AiCommerceConfig;

class ConversationReferenceValidator implements ConversationReferenceValidatorInterface
{
    public function __construct(protected AiCommerceConfig $aiCommerceConfig)
    {
    }

    public function isValid(string $conversationReference): bool
    {
        if ($conversationReference === '') {
            return false;
        }

        if (strlen($conversationReference) > $this->aiCommerceConfig->getStorefrontAssistantMaxConversationReferenceLength()) {
            return false;
        }

        return preg_match($this->aiCommerceConfig->getStorefrontAssistantConversationReferencePattern(), $conversationReference) === 1;
    }
}
