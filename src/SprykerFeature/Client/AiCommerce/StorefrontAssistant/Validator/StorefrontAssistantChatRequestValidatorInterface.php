<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator;

use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;

interface StorefrontAssistantChatRequestValidatorInterface
{
    public function validate(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): StorefrontAssistantChatRequestValidationResult;
}
