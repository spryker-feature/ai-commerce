<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface RefinementChipBuilderInterface
{
    /**
     * @param array<string, mixed> $searchResult
     *
     * @return list<array<string, mixed>>
     */
    public function buildRefinementChips(array $searchResult, int $limit): array;

    /**
     * @param array<string, mixed> $searchResult
     *
     * @return list<array<string, string>>
     */
    public function buildActiveFilters(array $searchResult): array;
}
