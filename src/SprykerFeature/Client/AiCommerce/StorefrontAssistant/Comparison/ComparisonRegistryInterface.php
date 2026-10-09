<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison;

interface ComparisonRegistryInterface
{
    /**
     * @param list<int> $productAbstractIds
     */
    public function hasComparison(array $productAbstractIds): bool;

    /**
     * @param list<int> $productAbstractIds
     */
    public function registerComparison(array $productAbstractIds): void;

    public function hasAnyComparison(): bool;

    public function clear(): void;
}
