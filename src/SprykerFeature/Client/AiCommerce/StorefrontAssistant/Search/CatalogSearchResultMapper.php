<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class CatalogSearchResultMapper implements CatalogSearchResultMapperInterface
{
    /**
     * @uses \Spryker\Client\Catalog\Plugin\Elasticsearch\ResultFormatter\RawCatalogSearchResultFormatterPlugin::NAME
     */
    protected const string RESULT_FORMATTER_KEY_PRODUCTS = 'products';

    /**
     * @uses \Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter\FacetResultFormatterPlugin::NAME
     */
    protected const string RESULT_FORMATTER_KEY_FACETS = 'facets';

    /**
     * @uses \Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter\PaginatedResultFormatterPlugin::NAME
     */
    protected const string RESULT_FORMATTER_KEY_PAGINATION = 'pagination';

    /**
     * @uses \Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter\SpellingSuggestionResultFormatterPlugin::NAME
     */
    protected const string RESULT_FORMATTER_KEY_SPELLING_SUGGESTION = 'spellingSuggestion';

    public const string RESULT_KEY_PRODUCTS = 'products';

    protected const string RESULT_KEY_AVAILABLE_REFINEMENTS = 'availableRefinements';

    protected const string RESULT_KEY_TOTAL_RESULTS = 'totalResults';

    protected const string RESULT_KEY_IGNORED_ARGUMENTS = 'ignoredArguments';

    protected const string RESULT_KEY_APPLIED_FILTERS = 'appliedFilters';

    protected const string RESULT_KEY_PRICE_RANGE = 'priceRange';

    protected const string RESULT_KEY_DID_YOU_MEAN = 'didYouMean';

    protected const string RESULT_KEY_PAGE = 'page';

    protected const string RESULT_KEY_HAS_MORE_RESULTS = 'hasMoreResults';

    protected const string RESULT_KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string NEXT_ACTION_RETRY_WITH_DID_YOU_MEAN = 'No product matched. Search again with the `didYouMean` spelling before answering the customer.';

    protected const string NEXT_ACTION_RELAX_A_FILTER = 'No product matched. Drop the most restrictive entry of `appliedFilters`, or the longest word of the query, and search once more before telling the customer nothing was found.';

    protected const string NEXT_ACTION_CORRECT_IGNORED_ARGUMENTS = 'Some arguments were dropped. Read each `reason` in `ignoredArguments` and correct that argument instead of repeating the same call.';

    protected const string PAGINATION_KEY_NUM_FOUND = 'num_found';

    protected const string PAGINATION_KEY_NUM_FOUND_CAMEL_CASE = 'numFound';

    protected const string PRODUCT_KEY_NAME = 'abstract_name';

    protected const string PRODUCT_KEY_URL = 'url';

    protected const string PRODUCT_KEY_PRICE = 'price';

    protected const string PRODUCT_KEY_SKU = 'abstract_sku';

    protected const string PRODUCT_KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    public function __construct(
        protected CatalogSearchRefinementMapperInterface $catalogSearchRefinementMapper,
        protected CatalogSearchPageResolverInterface $catalogSearchPageResolver,
        protected SearchResultDataExtractorInterface $searchResultDataExtractor
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function mapEmptyQueryResult(): array
    {
        return [
            static::RESULT_KEY_PRODUCTS => [],
            static::RESULT_KEY_TOTAL_RESULTS => 0,
        ];
    }

    /**
     * @param array<string, mixed> $searchResults
     *
     * @return list<int>
     */
    public function extractCategoryNodeIds(array $searchResults): array
    {
        return $this->catalogSearchRefinementMapper->extractCategoryNodeIds(
            $searchResults[static::RESULT_FORMATTER_KEY_FACETS] ?? [],
        );
    }

    /**
     * @param array<string, mixed> $searchResults
     * @param array<string, mixed> $requestParameters
     * @param array<int, array<string, string>> $ignoredArguments
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     * @param array<int, string> $categoryNodeNames
     *
     * @return array<string, mixed>
     */
    public function mapSearchResults(
        array $searchResults,
        array $requestParameters,
        array $ignoredArguments,
        int $page,
        int $limit,
        array $facetConfigTransfers,
        array $categoryNodeNames = []
    ): array {
        $totalResults = $this->resolveTotalResults($searchResults);
        $facets = $searchResults[static::RESULT_FORMATTER_KEY_FACETS] ?? [];

        $result = [
            static::RESULT_KEY_PRODUCTS => $this->mapProducts(
                $searchResults[static::RESULT_FORMATTER_KEY_PRODUCTS] ?? [],
                $this->catalogSearchPageResolver->resolveOffsetWithinSearchPage($page, $limit),
                $limit,
            ),
            static::RESULT_KEY_TOTAL_RESULTS => $totalResults,
            static::RESULT_KEY_PAGE => $page,
            static::RESULT_KEY_AVAILABLE_REFINEMENTS => $this->catalogSearchRefinementMapper->mapAvailableRefinements($facets, $facetConfigTransfers, $categoryNodeNames),
            static::RESULT_KEY_APPLIED_FILTERS => $this->mapAppliedFilters($requestParameters),
        ];

        $priceRange = $this->catalogSearchRefinementMapper->findPriceRange($facets);

        if ($priceRange !== null) {
            $result[static::RESULT_KEY_PRICE_RANGE] = $priceRange;
        }

        if ($totalResults !== null) {
            $result[static::RESULT_KEY_HAS_MORE_RESULTS] = $totalResults > $page * $limit;
        }

        $suggestedNextAction = $this->resolveSuggestedNextAction($totalResults, $searchResults, $ignoredArguments);

        if ($suggestedNextAction !== null) {
            $result[static::RESULT_KEY_SUGGESTED_NEXT_ACTION] = $suggestedNextAction;
        }

        $didYouMean = $this->resolveSpellingSuggestion($searchResults);

        if ($didYouMean !== null) {
            $result[static::RESULT_KEY_DID_YOU_MEAN] = $didYouMean;
        }

        if ($ignoredArguments !== []) {
            $result[static::RESULT_KEY_IGNORED_ARGUMENTS] = $ignoredArguments;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $requestParameters
     *
     * @return array<string, mixed>
     */
    protected function mapAppliedFilters(array $requestParameters): array
    {
        $appliedFilters = $requestParameters;
        unset(
            $appliedFilters[CatalogSearchArgumentResolver::REQUEST_PARAMETER_ITEMS_PER_PAGE],
            $appliedFilters[CatalogSearchArgumentResolver::REQUEST_PARAMETER_PAGE],
        );

        $ratingMin = $appliedFilters[CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING][CatalogSearchArgumentResolver::RANGE_KEY_MIN] ?? null;

        if ($ratingMin !== null) {
            $appliedFilters[CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING] = $ratingMin;
        }

        return $appliedFilters;
    }

    /**
     * @param array<string, mixed> $searchResults
     * @param array<int, array<string, string>> $ignoredArguments
     */
    protected function resolveSuggestedNextAction(
        ?int $totalResults,
        array $searchResults,
        array $ignoredArguments
    ): ?string {
        if ($totalResults === null || $totalResults > 0) {
            return $ignoredArguments === [] ? null : static::NEXT_ACTION_CORRECT_IGNORED_ARGUMENTS;
        }

        if ($this->resolveSpellingSuggestion($searchResults) !== null) {
            return static::NEXT_ACTION_RETRY_WITH_DID_YOU_MEAN;
        }

        return static::NEXT_ACTION_RELAX_A_FILTER;
    }

    /**
     * @param array<string, mixed> $searchResults
     */
    protected function resolveSpellingSuggestion(array $searchResults): ?string
    {
        $spellingSuggestion = $searchResults[static::RESULT_FORMATTER_KEY_SPELLING_SUGGESTION] ?? null;

        if (!is_scalar($spellingSuggestion)) {
            return null;
        }

        $spellingSuggestion = trim((string)$spellingSuggestion);

        return $spellingSuggestion === '' ? null : $spellingSuggestion;
    }

    /**
     * @param array<string, mixed> $searchResults
     */
    protected function resolveTotalResults(array $searchResults): ?int
    {
        $paginationData = $this->searchResultDataExtractor->extractData(
            $searchResults[static::RESULT_FORMATTER_KEY_PAGINATION] ?? null,
        );

        if ($paginationData === null) {
            return null;
        }

        $numFound = $paginationData[static::PAGINATION_KEY_NUM_FOUND]
            ?? $paginationData[static::PAGINATION_KEY_NUM_FOUND_CAMEL_CASE]
            ?? null;

        return is_scalar($numFound) ? (int)$numFound : null;
    }

    /**
     * @param mixed $products
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapProducts($products, int $offsetWithinSearchPage, int $limit): array
    {
        if (!is_array($products)) {
            return [];
        }

        $mappedProducts = [];

        foreach (array_slice(array_values($products), $offsetWithinSearchPage, $limit) as $product) {
            if (!is_array($product)) {
                continue;
            }

            $mappedProducts[] = [
                StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT => $this->extractIdProductAbstract($product),
                StorefrontAssistantProductMapper::KEY_NAME => (string)($product[static::PRODUCT_KEY_NAME] ?? ''),
                StorefrontAssistantProductMapper::KEY_SKU => (string)($product[static::PRODUCT_KEY_SKU] ?? ''),
                StorefrontAssistantProductMapper::KEY_PRICE => $product[static::PRODUCT_KEY_PRICE] ?? null,
                StorefrontAssistantProductMapper::KEY_URL => (string)($product[static::PRODUCT_KEY_URL] ?? ''),
            ];
        }

        return $mappedProducts;
    }

    /**
     * @param array<mixed> $product
     */
    protected function extractIdProductAbstract(array $product): ?int
    {
        $idProductAbstract = $product[static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT] ?? null;

        if ($idProductAbstract === null || is_bool($idProductAbstract) || !is_numeric($idProductAbstract)) {
            return null;
        }

        return (int)$idProductAbstract;
    }
}
