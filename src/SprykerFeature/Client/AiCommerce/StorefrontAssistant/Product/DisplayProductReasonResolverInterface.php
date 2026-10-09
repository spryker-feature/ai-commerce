<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

interface DisplayProductReasonResolverInterface
{
    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<int|string, mixed> $reasons
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolveReasons(array $products, array $reasons): array;
}
