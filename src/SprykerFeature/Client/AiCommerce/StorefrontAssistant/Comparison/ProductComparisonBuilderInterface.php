<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison;

interface ProductComparisonBuilderInterface
{
    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function compareProducts(array $arguments): array;
}
