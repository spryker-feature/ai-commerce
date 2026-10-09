<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use Spryker\Client\Catalog\CatalogClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryNodeNameReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ShownProductRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class CatalogSearchReader implements CatalogSearchReaderInterface
{
    protected const int QUERY_ONLY_ITEMS_PER_PAGE = 12;

    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::VALID_ITEMS_PER_PAGE_OPTIONS
     */
    protected const int SHOWN_PRODUCT_EXCLUSION_FETCH_LIMIT_MAX = 36;

    /**
     * @uses \Spryker\Client\Catalog\Plugin\Elasticsearch\ResultFormatter\RawCatalogSearchResultFormatterPlugin::NAME
     */
    protected const string RESULT_FORMATTER_KEY_PRODUCTS = 'products';

    protected const string PRODUCT_KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const string RESULT_KEY_TOTAL_RESULTS = 'totalResults';

    protected const string RESULT_KEY_HAS_MORE_RESULTS = 'hasMoreResults';

    protected const string RESULT_KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string RESULT_KEY_SHOWN_PRODUCTS_LEFT_OUT = 'shownProductsLeftOut';

    protected const string RESULT_KEY_ALL_MATCHING_PRODUCTS_SHOWN = 'allMatchingProductsShown';

    protected const string NEXT_ACTION_ALL_MATCHING_PRODUCTS_SHOWN = 'Every product matching this search has already been shown in this conversation. Display no products: tell the customer that all matching products have been shown and offer exactly one way to widen the search, such as dropping a filter or a wider category.';

    public function __construct(
        protected CatalogClientInterface $catalogClient,
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected CatalogSearchArgumentResolverInterface $catalogSearchArgumentResolver,
        protected CatalogSearchResultMapperInterface $catalogSearchResultMapper,
        protected SearchFilterConfigReaderInterface $searchFilterConfigReader,
        protected CatalogSearchZeroResultAnalyzerInterface $catalogSearchZeroResultAnalyzer,
        protected ShownProductRegistryInterface $shownProductRegistry,
        protected CatalogSearchResultRegistryInterface $catalogSearchResultRegistry,
        protected CategoryNodeNameReaderInterface $categoryNodeNameReader,
        protected CatalogSearchResultExpanderInterface $catalogSearchResultExpander
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function searchProducts(array $arguments): array
    {
        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $query = $this->catalogSearchArgumentResolver->resolveQuery($arguments);
        $facetConfigTransfers = $this->searchFilterConfigReader->getFilterableFacetConfigs();
        $ignoredArguments = [];
        $limit = $this->catalogSearchArgumentResolver->resolveLimit($arguments, $ignoredArguments);
        $shownProductAbstractIds = $this->catalogSearchArgumentResolver->isShownProductExclusionRequested($arguments)
            ? $this->shownProductRegistry->getShownProductAbstractIds()
            : [];

        if ($shownProductAbstractIds !== []) {
            return $this->searchProductsExcludingShown($arguments, $query, $limit, $ignoredArguments, $facetConfigTransfers, $shownProductAbstractIds);
        }

        $page = $this->catalogSearchArgumentResolver->resolvePage($arguments);
        $requestParameters = $this->catalogSearchArgumentResolver->resolveRequestParameters(
            $arguments,
            $ignoredArguments,
            $page,
            $limit,
            $facetConfigTransfers,
        );

        $searchResults = $this->catalogClient->catalogSearch($query, $requestParameters);

        $result = $this->catalogSearchResultMapper->mapSearchResults(
            $searchResults,
            $requestParameters,
            $ignoredArguments,
            $page,
            $limit,
            $facetConfigTransfers,
            $this->isEmptiedByConstraints($query, $searchResults, $requestParameters),
            $this->getCategoryNodeNames($searchResults),
        );
        $result = $this->catalogSearchResultExpander->expandWithProductData($result);
        $this->catalogSearchResultRegistry->registerSearchResult($query, $requestParameters, $result);

        return $result;
    }

    /**
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     * @param list<int> $shownProductAbstractIds
     *
     * @return array<string, mixed>
     */
    protected function searchProductsExcludingShown(
        array $arguments,
        string $query,
        int $limit,
        array $ignoredArguments,
        array $facetConfigTransfers,
        array $shownProductAbstractIds
    ): array {
        $fetchLimit = min($limit + count($shownProductAbstractIds), static::SHOWN_PRODUCT_EXCLUSION_FETCH_LIMIT_MAX);
        $requestParameters = $this->catalogSearchArgumentResolver->resolveRequestParameters(
            $arguments,
            $ignoredArguments,
            1,
            $fetchLimit,
            $facetConfigTransfers,
        );

        $searchResults = $this->catalogClient->catalogSearch($query, $requestParameters);
        $fetchedProducts = $searchResults[static::RESULT_FORMATTER_KEY_PRODUCTS] ?? [];
        $fetchedProducts = is_array($fetchedProducts) ? array_slice(array_values($fetchedProducts), 0, $fetchLimit) : [];
        $unshownProducts = array_values(array_filter(
            $fetchedProducts,
            fn (mixed $product): bool => !$this->isShownProduct($product, $shownProductAbstractIds),
        ));
        $searchResults[static::RESULT_FORMATTER_KEY_PRODUCTS] = $unshownProducts;

        $result = $this->catalogSearchResultMapper->mapSearchResults(
            $searchResults,
            $requestParameters,
            $ignoredArguments,
            1,
            $limit,
            $facetConfigTransfers,
            $this->isEmptiedByConstraints($query, $searchResults, $requestParameters),
            $this->getCategoryNodeNames($searchResults),
        );
        $result = $this->catalogSearchResultExpander->expandWithProductData($result);

        $result = $this->expandWithShownProductExclusion(
            $result,
            count($fetchedProducts) - count($unshownProducts),
            count($unshownProducts) > $limit,
            count($fetchedProducts),
        );
        $this->catalogSearchResultRegistry->registerSearchResult($query, $requestParameters, $result);

        return $result;
    }

    /**
     * @param array<string, mixed> $searchResults
     *
     * @return array<int, string>
     */
    protected function getCategoryNodeNames(array $searchResults): array
    {
        return $this->categoryNodeNameReader->getCategoryNodeNamesByIds(
            $this->catalogSearchResultMapper->extractCategoryNodeIds($searchResults),
        );
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    protected function expandWithShownProductExclusion(
        array $result,
        int $shownProductsLeftOut,
        bool $hasMoreUnshownFetchedProducts,
        int $fetchedProductCount
    ): array {
        $totalResults = $result[static::RESULT_KEY_TOTAL_RESULTS] ?? null;

        if ($totalResults === null) {
            return $result;
        }

        $hasMoreResults = $hasMoreUnshownFetchedProducts || $totalResults > $fetchedProductCount;
        $result[static::RESULT_KEY_HAS_MORE_RESULTS] = $hasMoreResults;

        if ($shownProductsLeftOut > 0) {
            $result[static::RESULT_KEY_SHOWN_PRODUCTS_LEFT_OUT] = $shownProductsLeftOut;
        }

        if ($totalResults > 0 && !$hasMoreResults && $result[static::RESULT_KEY_PRODUCTS] === []) {
            $result[static::RESULT_KEY_ALL_MATCHING_PRODUCTS_SHOWN] = true;
            $result[static::RESULT_KEY_SUGGESTED_NEXT_ACTION] = static::NEXT_ACTION_ALL_MATCHING_PRODUCTS_SHOWN;
        }

        return $result;
    }

    /**
     * @param list<int> $shownProductAbstractIds
     */
    protected function isShownProduct(mixed $product, array $shownProductAbstractIds): bool
    {
        $idProductAbstract = is_array($product) ? ($product[static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT] ?? null) : null;

        if (!is_numeric($idProductAbstract)) {
            return false;
        }

        return in_array((int)$idProductAbstract, $shownProductAbstractIds, true);
    }

    /**
     * @param array<string, mixed> $searchResults
     * @param array<string, mixed> $requestParameters
     */
    protected function isEmptiedByConstraints(string $query, array $searchResults, array $requestParameters): bool
    {
        if (
            $this->catalogSearchResultMapper->resolveTotalResults($searchResults) !== 0
            || !$this->catalogSearchZeroResultAnalyzer->hasConstraints($requestParameters)
        ) {
            return false;
        }

        if ($query === '') {
            return true;
        }

        $queryOnlySearchResults = $this->catalogClient->catalogSearch($query, [
            CatalogSearchArgumentResolver::REQUEST_PARAMETER_ITEMS_PER_PAGE => static::QUERY_ONLY_ITEMS_PER_PAGE,
        ]);

        return (int)$this->catalogSearchResultMapper->resolveTotalResults($queryOnlySearchResults) > 0;
    }
}
