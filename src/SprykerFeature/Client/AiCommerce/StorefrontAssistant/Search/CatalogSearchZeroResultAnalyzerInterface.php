<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface CatalogSearchZeroResultAnalyzerInterface
{
    /**
     * @param array<string, mixed> $requestParameters
     */
    public function hasConstraints(array $requestParameters): bool;

    /**
     * @param array<int, array<string, mixed>> $availableRefinements
     * @param array<string, mixed> $appliedFilters
     * @param array<string, float|string>|null $priceRange
     * @param array<string, int>|null $ratingRange
     *
     * @return array<int, array<string, mixed>>
     */
    public function analyze(
        array $availableRefinements,
        array $appliedFilters,
        ?array $priceRange,
        ?array $ratingRange
    ): array;
}
