<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Matcher;

interface ProductNameMatcherInterface
{
    public function isMatchingName(string $productName, string $queryName): bool;
}
