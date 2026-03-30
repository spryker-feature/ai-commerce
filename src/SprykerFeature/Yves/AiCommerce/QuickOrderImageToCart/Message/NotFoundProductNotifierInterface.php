<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Message;

interface NotFoundProductNotifierInterface
{
    /**
     * @param array<string> $productNames
     */
    public function addErrorNotifications(array $productNames): void;
}
