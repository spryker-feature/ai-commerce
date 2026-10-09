<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison;

class ComparisonRegistry implements ComparisonRegistryInterface
{
    protected const string KEY_SEPARATOR = ',';

    /**
     * Maximum number of comparisons kept for one turn, so a long agent turn cannot grow the process memory without a limit.
     */
    protected const int MAX_COMPARISONS = 10;

    /**
     * Static: each tool plugin creates its own factory, so the turn state is shared per process; capped by MAX_COMPARISONS, cleared by StorefrontAssistantChatStreamer at turn start and end.
     *
     * @var array<string, true>
     */
    protected static array $comparisonKeys = [];

    /**
     * @param list<int> $productAbstractIds
     */
    public function hasComparison(array $productAbstractIds): bool
    {
        return isset(static::$comparisonKeys[$this->createComparisonKey($productAbstractIds)]);
    }

    /**
     * Registers the comparison and keeps only the latest MAX_COMPARISONS comparisons.
     *
     * @param list<int> $productAbstractIds
     */
    public function registerComparison(array $productAbstractIds): void
    {
        static::$comparisonKeys[$this->createComparisonKey($productAbstractIds)] = true;
        static::$comparisonKeys = array_slice(static::$comparisonKeys, -static::MAX_COMPARISONS, null, true);
    }

    public function hasAnyComparison(): bool
    {
        return static::$comparisonKeys !== [];
    }

    public function clear(): void
    {
        static::$comparisonKeys = [];
    }

    /**
     * @param list<int> $productAbstractIds
     */
    protected function createComparisonKey(array $productAbstractIds): string
    {
        sort($productAbstractIds);

        return implode(static::KEY_SEPARATOR, $productAbstractIds);
    }
}
