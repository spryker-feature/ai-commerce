<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

class CatalogSearchArgumentResolver implements CatalogSearchArgumentResolverInterface
{
    public const string PARAMETER_QUERY = 'query';

    public const string PARAMETER_CATEGORY = 'category';

    public const string PARAMETER_PRICE_MIN = 'priceMin';

    public const string PARAMETER_PRICE_MAX = 'priceMax';

    public const string PARAMETER_RATING_MIN = 'ratingMin';

    public const string PARAMETER_LABEL = 'label';

    public const string PARAMETER_PRODUCT_CLASS = 'productClass';

    public const string PARAMETER_MERCHANT = 'merchant';

    public const string PARAMETER_SORT = 'sort';

    public const string PARAMETER_PAGE = 'page';

    public const string PARAMETER_FILTERS = 'filters';

    public const string PARAMETER_LIMIT = 'limit';

    public const string PARAMETER_EXCLUDE_SHOWN = 'excludeShown';

    public const string SORT_RATING = 'rating';

    public const string SORT_NAME_ASC = 'name_asc';

    public const string SORT_NAME_DESC = 'name_desc';

    public const string SORT_PRICE_ASC = 'price_asc';

    public const string SORT_PRICE_DESC = 'price_desc';

    public const string SORT_POPULARITY = 'popularity';

    public const array SUPPORTED_SORT_PARAMS = [
        self::SORT_RATING,
        self::SORT_NAME_ASC,
        self::SORT_NAME_DESC,
        self::SORT_PRICE_ASC,
        self::SORT_PRICE_DESC,
        self::SORT_POPULARITY,
    ];

    /**
     * @uses \Spryker\Client\Catalog\Plugin\ConfigTransferBuilder\CategoryFacetConfigTransferBuilderPlugin::PARAMETER_NAME
     */
    public const string REQUEST_PARAMETER_CATEGORY = 'category';

    /**
     * @uses \Spryker\Client\CatalogPriceProductConnector\Plugin\ConfigTransferBuilder\PriceFacetConfigTransferBuilderPlugin::PARAMETER_NAME
     */
    public const string REQUEST_PARAMETER_PRICE = 'price';

    /**
     * @uses \Spryker\Client\ProductReview\Plugin\RatingFacetConfigTransferBuilderPlugin::PARAMETER_NAME
     */
    public const string REQUEST_PARAMETER_RATING = 'rating';

    /**
     * @uses \Spryker\Client\ProductLabelStorage\Plugin\ProductLabelFacetConfigTransferBuilderPlugin::PARAMETER_NAME
     */
    public const string REQUEST_PARAMETER_LABEL = 'label';

    /**
     * @uses \SprykerFeature\Client\SelfServicePortal\Plugin\Catalog\ProductClassFacetConfigTransferBuilderPlugin::PARAMETER_NAME
     */
    public const string REQUEST_PARAMETER_PRODUCT_CLASS = 'product-class-names';

    /**
     * @uses \Spryker\Client\MerchantProductOfferSearch\Plugin\Search\MerchantNameSearchConfigExpanderPlugin::PARAMETER_NAME
     */
    public const string REQUEST_PARAMETER_MERCHANT_NAME = 'merchant_name';

    public const string REQUEST_PARAMETER_SORT = 'sort';

    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::PARAMETER_NAME_PAGE
     */
    public const string REQUEST_PARAMETER_PAGE = 'page';

    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::PARAMETER_NAME_ITEMS_PER_PAGE
     */
    public const string REQUEST_PARAMETER_ITEMS_PER_PAGE = 'ipp';

    /**
     * Maps a facet name as the search returns it to the argument that filters by it, so a refinement
     * the agent reads can be passed straight back instead of guessed at.
     *
     * @var array<string, string>
     */
    public const array REQUEST_PARAMETER_TO_FILTER_ARGUMENT = [
        self::REQUEST_PARAMETER_CATEGORY => self::PARAMETER_CATEGORY,
        self::REQUEST_PARAMETER_LABEL => self::PARAMETER_LABEL,
        self::REQUEST_PARAMETER_PRODUCT_CLASS => self::PARAMETER_PRODUCT_CLASS,
        self::REQUEST_PARAMETER_MERCHANT_NAME => self::PARAMETER_MERCHANT,
        self::REQUEST_PARAMETER_RATING => self::PARAMETER_RATING_MIN,
    ];

    public const string RANGE_KEY_MIN = 'min';

    public const string RANGE_KEY_MAX = 'max';

    public const int DEFAULT_LIMIT = 5;

    /**
     * The upper bound is the search page size: a window wider than one search page would need a
     * second round trip to fill, and the payload already competes with the conversation history.
     */
    public const int LIMIT_MAX = 12;

    public const int LIMIT_MIN = 1;

    public const int RATING_MIN = 1;

    public const int RATING_MAX = 5;

    /**
     * @uses \Spryker\Shared\Money\Converter\DecimalToIntegerConverter::PRICE_PRECISION
     */
    public const int PRICE_PRECISION = 100;

    protected const string IGNORED_ARGUMENT_KEY_ARGUMENT = 'argument';

    protected const string IGNORED_ARGUMENT_KEY_REASON = 'reason';

    protected const string FILTER_ARGUMENT_FORMAT = '%s.%s';

    public function __construct(
        protected CatalogSearchPageResolverInterface $catalogSearchPageResolver,
        protected ProductLabelNameResolverInterface $productLabelNameResolver
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int|string, mixed> $arguments
     */
    public function resolveQuery(array $arguments): string
    {
        return $this->resolveStringArgument($arguments, static::PARAMETER_QUERY);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int|string, mixed> $arguments
     */
    public function resolvePage(array $arguments): int
    {
        return max(1, $this->resolveIntArgument($arguments, static::PARAMETER_PAGE) ?? 1);
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function isShownProductExclusionRequested(array $arguments): bool
    {
        $value = $arguments[static::PARAMETER_EXCLUDE_SHOWN] ?? null;

        if (!is_scalar($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param-out array<int, array<string, string>> $ignoredArguments
     *
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     */
    public function resolveLimit(array $arguments, array &$ignoredArguments): int
    {
        $limit = $this->resolveIntArgument($arguments, static::PARAMETER_LIMIT);

        if ($limit === null) {
            return static::DEFAULT_LIMIT;
        }

        if ($limit < static::LIMIT_MIN || $limit > static::LIMIT_MAX) {
            $ignoredArguments[] = $this->formatIgnoredArgument(
                static::PARAMETER_LIMIT,
                sprintf('expects a number between %d and %d', static::LIMIT_MIN, static::LIMIT_MAX),
            );

            return static::DEFAULT_LIMIT;
        }

        return $limit;
    }

    /**
     * @param-out array<int, array<string, string>> $ignoredArguments
     *
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     *
     * @return array<string, mixed>
     */
    public function resolveRequestParameters(
        array $arguments,
        array &$ignoredArguments,
        int $page,
        int $limit,
        array $facetConfigTransfers
    ): array {
        $requestParameters = [];

        $category = $this->resolveCategory($arguments, $ignoredArguments);

        if ($category !== null) {
            $requestParameters[static::REQUEST_PARAMETER_CATEGORY] = $category;
        }

        $labels = $this->productLabelNameResolver->resolveLabelNames(
            $this->resolveStringListArgument($arguments, static::PARAMETER_LABEL),
        );

        if ($labels !== []) {
            $requestParameters[static::REQUEST_PARAMETER_LABEL] = $labels;
        }

        $productClasses = $this->resolveStringListArgument($arguments, static::PARAMETER_PRODUCT_CLASS);

        if ($productClasses !== []) {
            $requestParameters[static::REQUEST_PARAMETER_PRODUCT_CLASS] = $productClasses;
        }

        $merchantNames = $this->resolveMerchantNames($arguments, $ignoredArguments, $facetConfigTransfers);

        if ($merchantNames !== null) {
            $requestParameters[static::REQUEST_PARAMETER_MERCHANT_NAME] = $merchantNames;
        }

        $priceRange = $this->resolvePriceRange($arguments, $ignoredArguments);

        if ($priceRange !== null) {
            $requestParameters[static::REQUEST_PARAMETER_PRICE] = $priceRange;
        }

        $ratingMin = $this->resolveRatingMin($arguments, $ignoredArguments);

        if ($ratingMin !== null) {
            $requestParameters[static::REQUEST_PARAMETER_RATING] = [static::RANGE_KEY_MIN => $ratingMin];
        }

        $sort = $this->resolveSort($arguments, $ignoredArguments);

        if ($sort !== null) {
            $requestParameters[static::REQUEST_PARAMETER_SORT] = $sort;
        }

        $requestParameters[static::REQUEST_PARAMETER_ITEMS_PER_PAGE] = $this->catalogSearchPageResolver->resolveItemsPerPage($page, $limit);

        if ($page > 1) {
            $requestParameters[static::REQUEST_PARAMETER_PAGE] = $this->catalogSearchPageResolver->resolveSearchPage($page, $limit);
        }

        return $requestParameters + $this->resolveConfiguredFilters($arguments, $ignoredArguments, $facetConfigTransfers);
    }

    public function formatFilterArgument(string $parameterName): string
    {
        return sprintf(static::FILTER_ARGUMENT_FORMAT, static::PARAMETER_FILTERS, $parameterName);
    }

    /**
     * @param-out array<int, array<string, string>> $ignoredArguments
     *
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     *
     * @return array<string, mixed>
     */
    protected function resolveConfiguredFilters(
        array $arguments,
        array &$ignoredArguments,
        array $facetConfigTransfers
    ): array {
        $filters = $arguments[static::PARAMETER_FILTERS] ?? null;

        if (!is_array($filters) || $filters === []) {
            return [];
        }

        $requestParameters = [];

        foreach ($filters as $parameterName => $value) {
            $facetConfigTransfer = $facetConfigTransfers[$parameterName] ?? null;

            if ($facetConfigTransfer === null) {
                $ignoredArguments[] = $this->formatIgnoredArgument(
                    $this->formatFilterArgument((string)$parameterName),
                    'is not an attribute this shop filters by; use one the `availableRefinements` name',
                );

                continue;
            }

            $filterValue = $this->resolveFilterValue($value, (bool)$facetConfigTransfer->getIsMultiValued());

            if ($filterValue === null) {
                $ignoredArguments[] = $this->formatIgnoredArgument(
                    $this->formatFilterArgument((string)$parameterName),
                    'carried no usable value; pass a value from that refinement',
                );

                continue;
            }

            $requestParameters[(string)$parameterName] = $filterValue;
        }

        return $requestParameters;
    }

    /**
     * @param mixed $value
     *
     * @return array<int, string>|string|null
     */
    protected function resolveFilterValue($value, bool $isMultiValued)
    {
        if (is_scalar($value)) {
            $value = trim((string)$value);

            if ($value === '') {
                return null;
            }

            return $isMultiValued ? [$value] : $value;
        }

        if (!is_array($value)) {
            return null;
        }

        $filterValues = [];

        foreach ($value as $singleValue) {
            if (!is_scalar($singleValue)) {
                continue;
            }

            $singleValue = trim((string)$singleValue);

            if ($singleValue !== '') {
                $filterValues[] = $singleValue;
            }
        }

        if ($filterValues === []) {
            return null;
        }

        return $isMultiValued ? $filterValues : $filterValues[0];
    }

    /**
     * @return array<string, string>
     */
    protected function formatIgnoredArgument(string $argument, string $reason): array
    {
        return [
            static::IGNORED_ARGUMENT_KEY_ARGUMENT => $argument,
            static::IGNORED_ARGUMENT_KEY_REASON => $reason,
        ];
    }

    /**
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     *
     * @return array<int, string>|string|null
     */
    protected function resolveMerchantNames(array $arguments, array &$ignoredArguments, array $facetConfigTransfers)
    {
        $merchantNames = $this->resolveStringListArgument($arguments, static::PARAMETER_MERCHANT);

        if ($merchantNames === []) {
            return null;
        }

        $facetConfigTransfer = $facetConfigTransfers[static::REQUEST_PARAMETER_MERCHANT_NAME] ?? null;

        if ($facetConfigTransfer === null) {
            $ignoredArguments[] = $this->formatIgnoredArgument(
                static::PARAMETER_MERCHANT,
                'this shop does not filter by seller; search without it',
            );

            return null;
        }

        return $this->resolveFilterValue($merchantNames, (bool)$facetConfigTransfer->getIsMultiValued());
    }

    /**
     * @uses \Spryker\Client\ProductReview\Plugin\ProductRatingValueTransformer::transformFromDisplay()
     *
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     */
    protected function resolveRatingMin(array $arguments, array &$ignoredArguments): ?int
    {
        $ratingMin = $this->resolveIntArgument($arguments, static::PARAMETER_RATING_MIN);

        if ($ratingMin === null) {
            return null;
        }

        if ($ratingMin < static::RATING_MIN || $ratingMin > static::RATING_MAX) {
            $ignoredArguments[] = $this->formatIgnoredArgument(
                static::PARAMETER_RATING_MIN,
                sprintf('expects a whole number of stars between %d and %d', static::RATING_MIN, static::RATING_MAX),
            );

            return null;
        }

        return $ratingMin;
    }

    /**
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     */
    protected function resolveCategory(array $arguments, array &$ignoredArguments): ?int
    {
        $category = $this->resolveStringArgument($arguments, static::PARAMETER_CATEGORY);

        if ($category === '') {
            return null;
        }

        if (!ctype_digit($category)) {
            $ignoredArguments[] = $this->formatIgnoredArgument(
                static::PARAMETER_CATEGORY,
                'expects a numeric category id, not a category name; take the id from the `value` of a `category` refinement',
            );

            return null;
        }

        return (int)$category;
    }

    /**
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     */
    protected function resolveSort(array $arguments, array &$ignoredArguments): ?string
    {
        $sort = $this->resolveStringArgument($arguments, static::PARAMETER_SORT);

        if ($sort === '') {
            return null;
        }

        if (!in_array($sort, static::SUPPORTED_SORT_PARAMS, true)) {
            $ignoredArguments[] = $this->formatIgnoredArgument(
                static::PARAMETER_SORT,
                sprintf('expects one of: %s', implode(', ', static::SUPPORTED_SORT_PARAMS)),
            );

            return null;
        }

        return $sort;
    }

    /**
     * @param array<int|string, mixed> $arguments
     * @param array<int, array<string, string>> $ignoredArguments
     *
     * @return array<string, float>|null
     */
    protected function resolvePriceRange(array $arguments, array &$ignoredArguments): ?array
    {
        $priceMin = $this->resolveFloatArgument($arguments, static::PARAMETER_PRICE_MIN);
        $priceMax = $this->resolveFloatArgument($arguments, static::PARAMETER_PRICE_MAX);

        if ($priceMin !== null && $priceMin < 0) {
            $ignoredArguments[] = $this->formatIgnoredArgument(static::PARAMETER_PRICE_MIN, 'cannot be negative');
            $priceMin = null;
        }

        if ($priceMax !== null && $priceMax < 0) {
            $ignoredArguments[] = $this->formatIgnoredArgument(static::PARAMETER_PRICE_MAX, 'cannot be negative');
            $priceMax = null;
        }

        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            $ignoredArguments[] = $this->formatIgnoredArgument(
                static::PARAMETER_PRICE_MIN,
                'is greater than priceMax, so the range would match nothing',
            );
            $ignoredArguments[] = $this->formatIgnoredArgument(
                static::PARAMETER_PRICE_MAX,
                'is lower than priceMin, so the range would match nothing',
            );

            return null;
        }

        $priceRange = [];

        if ($priceMin !== null) {
            $priceRange[static::RANGE_KEY_MIN] = $priceMin;
        }

        if ($priceMax !== null) {
            $priceRange[static::RANGE_KEY_MAX] = $priceMax;
        }

        return $priceRange === [] ? null : $priceRange;
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function resolveStringArgument(array $arguments, string $name): string
    {
        $value = $arguments[$name] ?? null;

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string)$value);
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<int, string>
     */
    protected function resolveStringListArgument(array $arguments, string $name): array
    {
        $value = $arguments[$name] ?? null;

        if (is_scalar($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $values = [];

        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $item = trim((string)$item);

            if ($item !== '') {
                $values[] = $item;
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function resolveIntArgument(array $arguments, string $name): ?int
    {
        $value = $arguments[$name] ?? null;

        if ($value === null || is_bool($value) || !is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function resolveFloatArgument(array $arguments, string $name): ?float
    {
        $value = $arguments[$name] ?? null;

        if ($value === null || is_bool($value) || !is_numeric($value)) {
            return null;
        }

        $value = (float)$value;

        return is_finite($value) ? $value : null;
    }
}
