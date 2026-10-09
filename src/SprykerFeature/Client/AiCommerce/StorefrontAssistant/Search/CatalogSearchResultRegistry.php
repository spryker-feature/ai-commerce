<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class CatalogSearchResultRegistry implements CatalogSearchResultRegistryInterface
{
    public const string ENTRY_KEY_QUERY = 'query';

    public const string ENTRY_KEY_REQUEST_PARAMETERS = 'requestParameters';

    public const string ENTRY_KEY_RESULT = 'result';

    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const int MAX_SEARCH_RESULTS = 10;

    /**
     * Static: each tool plugin creates its own factory, so the turn state is shared per process; capped by MAX_SEARCH_RESULTS, cleared by StorefrontAssistantChatStreamer at turn start and end.
     *
     * @var list<array<string, mixed>>
     */
    protected static array $searchResults = [];

    /**
     * @param array<string, mixed> $requestParameters
     * @param array<string, mixed> $result
     */
    public function registerSearchResult(string $query, array $requestParameters, array $result): void
    {
        static::$searchResults[] = [
            static::ENTRY_KEY_QUERY => $query,
            static::ENTRY_KEY_REQUEST_PARAMETERS => $requestParameters,
            static::ENTRY_KEY_RESULT => $result,
        ];

        static::$searchResults = array_slice(static::$searchResults, -static::MAX_SEARCH_RESULTS);
    }

    /**
     * @param array<int, int> $productAbstractIds
     *
     * @return array<string, mixed>|null
     */
    public function findSearchResultContainingProductAbstractIds(array $productAbstractIds): ?array
    {
        if ($productAbstractIds === []) {
            return null;
        }

        foreach (array_reverse(static::$searchResults) as $searchResult) {
            $searchedProductAbstractIds = $this->extractProductAbstractIds($searchResult[static::ENTRY_KEY_RESULT]);

            if (array_diff($productAbstractIds, $searchedProductAbstractIds) === []) {
                return $searchResult;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSearchResults(): array
    {
        return static::$searchResults;
    }

    public function clear(): void
    {
        static::$searchResults = [];
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return list<int>
     */
    protected function extractProductAbstractIds(array $result): array
    {
        $products = $result[static::RESULT_KEY_PRODUCTS] ?? null;

        if (!is_array($products)) {
            return [];
        }

        $productAbstractIds = [];

        foreach ($products as $product) {
            $idProductAbstract = is_array($product) ? ($product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? null) : null;

            if (is_numeric($idProductAbstract)) {
                $productAbstractIds[] = (int)$idProductAbstract;
            }
        }

        return $productAbstractIds;
    }
}
