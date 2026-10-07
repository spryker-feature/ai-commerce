<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Session;

interface StorefrontAssistantStreamSessionBinderInterface
{
    /**
     * @param array<string, mixed> $sessionAttributes
     */
    public function bindSessionAttributes(array $sessionAttributes): void;
}
