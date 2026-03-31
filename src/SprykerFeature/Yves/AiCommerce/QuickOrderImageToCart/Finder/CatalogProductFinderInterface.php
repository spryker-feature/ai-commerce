<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Finder;

interface CatalogProductFinderInterface
{
    /**
     * @param array<string> $productNames
     *
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer|null>
     */
    public function findProductsByNames(array $productNames): array;
}
