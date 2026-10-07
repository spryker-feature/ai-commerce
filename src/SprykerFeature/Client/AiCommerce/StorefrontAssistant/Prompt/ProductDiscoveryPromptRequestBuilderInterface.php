<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Prompt;

use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;

interface ProductDiscoveryPromptRequestBuilderInterface
{
    public function buildPromptRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): PromptRequestTransfer;
}
