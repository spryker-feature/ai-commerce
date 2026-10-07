<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Resolver;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatResponseTransfer;

interface StorefrontAssistantValidationErrorResolverInterface
{
    public function resolveValidationError(
        StorefrontAssistantChatResponseTransfer $storefrontAssistantChatResponseTransfer
    ): ErrorTransfer;
}
