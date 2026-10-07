<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Checker;

interface StorefrontAssistantAccessCheckerInterface
{
    public function isStorefrontAssistantEnabled(): bool;

    public function findCustomerReference(): ?string;
}
