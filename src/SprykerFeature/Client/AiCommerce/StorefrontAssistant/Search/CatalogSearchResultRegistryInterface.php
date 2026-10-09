<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface CatalogSearchResultRegistryInterface
{
    /**
     * @param array<string, mixed> $requestParameters
     * @param array<string, mixed> $result
     */
    public function registerSearchResult(string $query, array $requestParameters, array $result): void;

    /**
     * @param array<int, int> $productAbstractIds
     *
     * @return array<string, mixed>|null
     */
    public function findSearchResultContainingProductAbstractIds(array $productAbstractIds): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function getSearchResults(): array;

    public function clear(): void;
}
