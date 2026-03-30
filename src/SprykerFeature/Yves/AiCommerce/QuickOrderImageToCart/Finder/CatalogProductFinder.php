<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Finder;

use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Client\Catalog\CatalogClientInterface;

class CatalogProductFinder implements CatalogProductFinderInterface
{
    protected const string KEY_PRODUCT_SUGGESTION_BY_TYPE = 'suggestionByType';

    protected const string KEY_PRODUCT_CONCRETE = 'product_concrete';

    protected const int KEY_FIRST_PRODUCT = 0;

    public function __construct(protected CatalogClientInterface $catalogClient)
    {
    }

    /**
     * @param array<string> $productNames
     *
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer|null>
     */
    public function findProductsByNames(array $productNames): array
    {
        $searchStrings = [];

        foreach (array_unique(array_filter($productNames)) as $productName) {
            $searchStrings[$productName] = $productName;
        }

        $multiSearchResults = $this->catalogClient->catalogSuggestMultiSearch($searchStrings);
        $results = [];

        foreach ($productNames as $productName) {
            $searchResult = $multiSearchResults[$productName] ?? [];
            $results[$productName] = $this->extractItemTransferFromSearchResult($searchResult);
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $searchResult
     */
    protected function extractItemTransferFromSearchResult(array $searchResult): ?ItemTransfer
    {
        $catalogProductConcreteData = $searchResult[static::KEY_PRODUCT_SUGGESTION_BY_TYPE][static::KEY_PRODUCT_CONCRETE][static::KEY_FIRST_PRODUCT] ?? null;

        if (!$catalogProductConcreteData) {
            return null;
        }

        return (new ItemTransfer())->fromArray($catalogProductConcreteData, true);
    }
}
