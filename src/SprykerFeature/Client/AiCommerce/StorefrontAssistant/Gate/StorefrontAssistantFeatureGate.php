<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Gate;

use SprykerFeature\Client\AiCommerce\AiCommerceConfig;

class StorefrontAssistantFeatureGate implements StorefrontAssistantFeatureGateInterface
{
    public function __construct(protected AiCommerceConfig $aiCommerceConfig)
    {
    }

    public function isEnabled(): bool
    {
        return $this->aiCommerceConfig->isStorefrontAssistantEnabled();
    }

    public function isAgentEnabled(string $enabledConfigurationKey): bool
    {
        return $this->aiCommerceConfig->isStorefrontAssistantAgentEnabled($enabledConfigurationKey);
    }
}
