<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Store\StoreClientInterface;

class SeeAllLinkBuilder implements SeeAllLinkBuilderInterface
{
    public const string KEY_TOTAL_RESULTS = 'totalResults';

    public const string KEY_QUERY_STRING = 'queryString';

    public const string KEY_CATEGORY_URL = 'categoryUrl';

    /**
     * @uses \SprykerShop\Yves\CatalogPage\Controller\CatalogController::executeFulltextSearchAction()
     */
    protected const string QUERY_PARAMETER_SEARCH_STRING = 'q';

    /**
     * @var list<string>
     */
    protected const array PAGINATION_REQUEST_PARAMETERS = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PAGE,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_ITEMS_PER_PAGE,
    ];

    public function __construct(
        protected CategoryStorageClientInterface $categoryStorageClient,
        protected LocaleClientInterface $localeClient,
        protected StoreClientInterface $storeClient,
        protected int $maxDisplayedProducts
    ) {
    }

    /**
     * @param array<string, mixed> $catalogSearchResultEntry
     *
     * @return array<string, mixed>|null
     */
    public function buildSeeAll(array $catalogSearchResultEntry, int $displayedProductCount): ?array
    {
        $result = $catalogSearchResultEntry[CatalogSearchResultRegistry::ENTRY_KEY_RESULT] ?? null;
        $totalResults = is_array($result) ? ($result[static::KEY_TOTAL_RESULTS] ?? null) : null;

        if (!is_int($totalResults) || $totalResults <= $displayedProductCount || $totalResults <= $this->maxDisplayedProducts) {
            return null;
        }

        $query = (string)($catalogSearchResultEntry[CatalogSearchResultRegistry::ENTRY_KEY_QUERY] ?? '');
        $requestParameters = $catalogSearchResultEntry[CatalogSearchResultRegistry::ENTRY_KEY_REQUEST_PARAMETERS] ?? [];
        $queryParameters = array_diff_key(
            is_array($requestParameters) ? $requestParameters : [],
            array_flip(static::PAGINATION_REQUEST_PARAMETERS),
        );
        $category = $queryParameters[CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY] ?? null;

        if ($category === null) {
            return $this->createSeeAll($totalResults, [static::QUERY_PARAMETER_SEARCH_STRING => $query] + $queryParameters);
        }

        $categoryUrl = is_numeric($category) ? $this->findCategoryNodeUrl((int)$category) : null;

        if ($categoryUrl === null) {
            return null;
        }

        unset($queryParameters[CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY]);

        if ($query !== '') {
            $queryParameters = [static::QUERY_PARAMETER_SEARCH_STRING => $query] + $queryParameters;
        }

        return $this->createSeeAll($totalResults, $queryParameters) + [static::KEY_CATEGORY_URL => $categoryUrl];
    }

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @return array<string, mixed>
     */
    protected function createSeeAll(int $totalResults, array $queryParameters): array
    {
        return [
            static::KEY_TOTAL_RESULTS => $totalResults,
            static::KEY_QUERY_STRING => http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986),
        ];
    }

    protected function findCategoryNodeUrl(int $idCategoryNode): ?string
    {
        $categoryNodeStorageTransfers = $this->categoryStorageClient->getCategoryNodeByIds(
            [$idCategoryNode],
            $this->localeClient->getCurrentLocale(),
            $this->storeClient->getCurrentStore()->getNameOrFail(),
        );

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            if ($categoryNodeStorageTransfer->getNodeId() === $idCategoryNode && (string)$categoryNodeStorageTransfer->getUrl() !== '') {
                return (string)$categoryNodeStorageTransfer->getUrl();
            }
        }

        return null;
    }
}
