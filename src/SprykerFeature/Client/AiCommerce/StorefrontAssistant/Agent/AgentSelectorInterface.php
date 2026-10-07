<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent;

use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantAgentPluginInterface;

interface AgentSelectorInterface
{
    public function selectAgent(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): ?StorefrontAssistantAgentPluginInterface;

    /**
     * @return array<int, array<string, string>>
     */
    public function getEnabledAgents(): array;
}
