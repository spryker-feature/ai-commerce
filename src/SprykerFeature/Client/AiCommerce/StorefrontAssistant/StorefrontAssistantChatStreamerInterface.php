<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant;

use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatResponseTransfer;

interface StorefrontAssistantChatStreamerInterface
{
    public function executeStorefrontAssistantChat(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): void;

    public function validateStorefrontAssistantChatRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): StorefrontAssistantChatResponseTransfer;
}
