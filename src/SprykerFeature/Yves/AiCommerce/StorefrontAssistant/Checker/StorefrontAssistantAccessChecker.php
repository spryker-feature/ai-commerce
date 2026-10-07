<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Checker;

use Spryker\Client\Customer\CustomerClientInterface;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class StorefrontAssistantAccessChecker implements StorefrontAssistantAccessCheckerInterface
{
    public function __construct(
        protected AiCommerceConfig $aiCommerceConfig,
        protected CustomerClientInterface $customerClient
    ) {
    }

    public function isStorefrontAssistantEnabled(): bool
    {
        return $this->aiCommerceConfig->isStorefrontAssistantEnabled();
    }

    public function findCustomerReference(): ?string
    {
        return $this->customerClient->getCustomer()?->getCustomerReference();
    }
}
