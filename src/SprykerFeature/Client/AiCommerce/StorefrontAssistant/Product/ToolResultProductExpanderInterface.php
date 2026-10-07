<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

interface ToolResultProductExpanderInterface
{
    /**
     * @param array<int, mixed> $products
     *
     * @return array<int, array<string, mixed>>
     */
    public function expandProducts(array $products): array;

    /**
     * @param array<int, mixed> $products
     *
     * @return array<int, array<string, mixed>>
     */
    public function expandProductsForAgent(array $products): array;
}
