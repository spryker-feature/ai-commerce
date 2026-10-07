<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Resolver;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatResponseTransfer;

class StorefrontAssistantValidationErrorResolver implements StorefrontAssistantValidationErrorResolverInterface
{
    protected const string GLOSSARY_KEY_ERROR_GENERIC = 'ai_commerce.storefront_assistant.error.generic';

    public function resolveValidationError(
        StorefrontAssistantChatResponseTransfer $storefrontAssistantChatResponseTransfer
    ): ErrorTransfer {
        foreach ($storefrontAssistantChatResponseTransfer->getErrors() as $errorTransfer) {
            if ((string)$errorTransfer->getMessage() !== '') {
                return $errorTransfer;
            }
        }

        return (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_ERROR_GENERIC);
    }
}
