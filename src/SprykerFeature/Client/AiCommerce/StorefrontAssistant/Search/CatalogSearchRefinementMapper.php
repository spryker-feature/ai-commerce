<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;

class CatalogSearchRefinementMapper implements CatalogSearchRefinementMapperInterface
{
    protected const string FACET_KEY_NAME = 'name';

    protected const string FACET_KEY_VALUES = 'values';

    protected const string FACET_KEY_MIN = 'min';

    protected const string FACET_KEY_MAX = 'max';

    protected const string FACET_KEY_CONFIG = 'config';

    protected const string FACET_CONFIG_KEY_PARAMETER_NAME = 'parameter_name';

    protected const string FACET_CONFIG_KEY_PARAMETER_NAME_CAMEL_CASE = 'parameterName';

    protected const string RANGE_KEY_MIN_FORMATTED = 'minFormatted';

    protected const string RANGE_KEY_MAX_FORMATTED = 'maxFormatted';

    protected const int PRICE_RANGE_FRACTION_DIGITS = 2;

    protected const string FACET_VALUE_KEY_VALUE = 'value';

    protected const string FACET_VALUE_KEY_DOC_COUNT = 'doc_count';

    protected const string FACET_VALUE_KEY_DOC_COUNT_CAMEL_CASE = 'docCount';

    protected const string REFINEMENT_VALUE_KEY_VALUE = 'value';

    protected const string REFINEMENT_VALUE_KEY_MATCH_COUNT = 'matchCount';

    protected const string REFINEMENT_VALUE_KEY_LABEL = 'label';

    protected const string REFINEMENT_KEY_FILTER_ARGUMENT = 'filterArgument';

    protected const string REFINEMENT_KEY_VALUE_TYPE = 'valueType';

    protected const string VALUE_TYPE_CATEGORY_ID = 'categoryId';

    protected const int MAX_REFINEMENT_VALUES = 10;

    /**
     * @var list<string>
     */
    protected const array CONFIGURED_FACET_NAMES = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_MERCHANT_NAME,
    ];

    public function __construct(
        protected CatalogSearchArgumentResolverInterface $catalogSearchArgumentResolver,
        protected SearchResultDataExtractorInterface $searchResultDataExtractor,
        protected StorefrontAssistantPriceFormatterInterface $storefrontAssistantPriceFormatter
    ) {
    }

    /**
     * @param mixed $facets
     *
     * @return list<int>
     */
    public function extractCategoryNodeIds($facets): array
    {
        if (!is_array($facets)) {
            return [];
        }

        foreach ($facets as $facet) {
            $categoryRefinementValues = $this->findCategoryRefinementValues($facet);

            if ($categoryRefinementValues !== null) {
                return array_values(array_map(
                    static fn (array $refinementValue): int => (int)$refinementValue[static::REFINEMENT_VALUE_KEY_VALUE],
                    $categoryRefinementValues,
                ));
            }
        }

        return [];
    }

    /**
     * @param mixed $facets
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     * @param array<int, string> $categoryNodeNames
     *
     * @return array<int, array<string, mixed>>
     */
    public function mapAvailableRefinements($facets, array $facetConfigTransfers, array $categoryNodeNames = []): array
    {
        if (!is_array($facets)) {
            return [];
        }

        $availableRefinements = [];

        foreach ($facets as $facet) {
            $refinement = $this->mapRefinement($facet, $facetConfigTransfers, $categoryNodeNames);

            if ($refinement !== null) {
                $availableRefinements[] = $refinement;
            }
        }

        return $availableRefinements;
    }

    /**
     * @param mixed $facets
     *
     * @return array<string, float|string>|null
     */
    public function findPriceRange($facets): ?array
    {
        $range = $this->findRange($facets, CatalogSearchArgumentResolver::REQUEST_PARAMETER_PRICE);

        if ($range === null) {
            return null;
        }

        [$min, $max] = array_map(static fn (float $amount): int => (int)round($amount), $range);

        return [
            CatalogSearchArgumentResolver::RANGE_KEY_MIN => round($min / CatalogSearchArgumentResolver::PRICE_PRECISION, static::PRICE_RANGE_FRACTION_DIGITS),
            CatalogSearchArgumentResolver::RANGE_KEY_MAX => round($max / CatalogSearchArgumentResolver::PRICE_PRECISION, static::PRICE_RANGE_FRACTION_DIGITS),
            static::RANGE_KEY_MIN_FORMATTED => $this->storefrontAssistantPriceFormatter->formatAmount($min),
            static::RANGE_KEY_MAX_FORMATTED => $this->storefrontAssistantPriceFormatter->formatAmount($max),
        ];
    }

    /**
     * @param mixed $facets
     *
     * @return array<string, int>|null
     */
    public function findRatingRange($facets): ?array
    {
        $range = $this->findRange($facets, CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING);

        if ($range === null) {
            return null;
        }

        return [
            CatalogSearchArgumentResolver::RANGE_KEY_MIN => (int)round($range[0]),
            CatalogSearchArgumentResolver::RANGE_KEY_MAX => (int)round($range[1]),
        ];
    }

    /**
     * @param mixed $facets
     *
     * @return array{0: float, 1: float}|null
     */
    protected function findRange($facets, string $parameterName): ?array
    {
        if (!is_array($facets)) {
            return null;
        }

        foreach ($facets as $facet) {
            $facetData = $this->searchResultDataExtractor->extractData($facet);

            if ($facetData === null || $this->resolveFacetParameterName($facetData) !== $parameterName) {
                continue;
            }

            $min = $facetData[static::FACET_KEY_MIN] ?? null;
            $max = $facetData[static::FACET_KEY_MAX] ?? null;

            if (!is_numeric($min) || !is_numeric($max) || (float)$max <= 0) {
                return null;
            }

            return [(float)$min, (float)$max];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $facetData
     */
    protected function resolveFacetParameterName(array $facetData): ?string
    {
        $facetConfigData = $this->searchResultDataExtractor->extractData($facetData[static::FACET_KEY_CONFIG] ?? null);
        $parameterName = $facetConfigData[static::FACET_CONFIG_KEY_PARAMETER_NAME]
            ?? $facetConfigData[static::FACET_CONFIG_KEY_PARAMETER_NAME_CAMEL_CASE]
            ?? $facetData[static::FACET_KEY_NAME]
            ?? null;

        return is_string($parameterName) ? $parameterName : null;
    }

    /**
     * @param mixed $facet
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function findCategoryRefinementValues($facet): ?array
    {
        $facetData = $this->searchResultDataExtractor->extractData($facet);

        if ($facetData === null || ($facetData[static::FACET_KEY_NAME] ?? null) !== CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY) {
            return null;
        }

        $values = $facetData[static::FACET_KEY_VALUES] ?? null;

        return is_array($values) ? $this->mapRefinementValues($values) : [];
    }

    /**
     * @param mixed $facet
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     * @param array<int, string> $categoryNodeNames
     *
     * @return array<string, mixed>|null
     */
    protected function mapRefinement($facet, array $facetConfigTransfers, array $categoryNodeNames): ?array
    {
        $facetData = $this->searchResultDataExtractor->extractData($facet);

        if ($facetData === null) {
            return null;
        }

        $name = $facetData[static::FACET_KEY_NAME] ?? null;
        $values = $facetData[static::FACET_KEY_VALUES] ?? null;

        if (!is_string($name) || $name === '' || !is_array($values) || $values === []) {
            return null;
        }

        $refinementValues = $this->mapRefinementValues($values);

        if ($refinementValues === []) {
            return null;
        }

        if ($name === CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY) {
            $refinementValues = $this->expandCategoryRefinementValuesWithLabels($refinementValues, $categoryNodeNames);
        }

        $filterArgument = $this->resolveFilterArgument($name, $facetConfigTransfers);

        if ($filterArgument === null) {
            return null;
        }

        $refinement = [
            static::FACET_KEY_NAME => $name,
            static::FACET_KEY_VALUES => $refinementValues,
            static::REFINEMENT_KEY_FILTER_ARGUMENT => $filterArgument,
        ];

        if ($name === CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY) {
            $refinement[static::REFINEMENT_KEY_VALUE_TYPE] = static::VALUE_TYPE_CATEGORY_ID;
        }

        return $refinement;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     */
    protected function resolveFilterArgument(string $facetName, array $facetConfigTransfers): ?string
    {
        $filterArgument = CatalogSearchArgumentResolver::REQUEST_PARAMETER_TO_FILTER_ARGUMENT[$facetName] ?? null;

        if ($filterArgument !== null && !in_array($facetName, static::CONFIGURED_FACET_NAMES, true)) {
            return $filterArgument;
        }

        if (!array_key_exists($facetName, $facetConfigTransfers)) {
            return null;
        }

        return $filterArgument ?? $this->catalogSearchArgumentResolver->formatFilterArgument($facetName);
    }

    /**
     * @param array<int, array<string, mixed>> $refinementValues
     * @param array<int, string> $categoryNodeNames
     *
     * @return array<int, array<string, mixed>>
     */
    protected function expandCategoryRefinementValuesWithLabels(array $refinementValues, array $categoryNodeNames): array
    {
        foreach ($refinementValues as $key => $refinementValue) {
            $categoryNodeName = $categoryNodeNames[(int)$refinementValue[static::REFINEMENT_VALUE_KEY_VALUE]] ?? null;

            if ($categoryNodeName !== null) {
                $refinementValues[$key][static::REFINEMENT_VALUE_KEY_LABEL] = $categoryNodeName;
            }
        }

        return $refinementValues;
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapRefinementValues(array $values): array
    {
        $refinementValues = [];

        foreach (array_values($values) as $value) {
            $refinementValue = $this->extractRefinementValue($value);

            if ($refinementValue !== null) {
                $refinementValues[] = $refinementValue;
            }
        }

        usort(
            $refinementValues,
            static fn (array $a, array $b): int => ($b[static::REFINEMENT_VALUE_KEY_MATCH_COUNT] ?? 0) <=> ($a[static::REFINEMENT_VALUE_KEY_MATCH_COUNT] ?? 0),
        );

        return array_slice($refinementValues, 0, static::MAX_REFINEMENT_VALUES);
    }

    /**
     * @param mixed $value
     *
     * @return array<string, mixed>|null
     */
    protected function extractRefinementValue($value): ?array
    {
        if (is_scalar($value)) {
            return [static::REFINEMENT_VALUE_KEY_VALUE => (string)$value];
        }

        $valueData = $this->searchResultDataExtractor->extractData($value);

        if ($valueData === null) {
            return null;
        }

        $extractedValue = $valueData[static::FACET_VALUE_KEY_VALUE] ?? null;

        if (!is_scalar($extractedValue)) {
            return null;
        }

        $refinementValue = [static::REFINEMENT_VALUE_KEY_VALUE => (string)$extractedValue];
        $docCount = $valueData[static::FACET_VALUE_KEY_DOC_COUNT]
            ?? $valueData[static::FACET_VALUE_KEY_DOC_COUNT_CAMEL_CASE]
            ?? null;

        if (is_numeric($docCount)) {
            $refinementValue[static::REFINEMENT_VALUE_KEY_MATCH_COUNT] = (int)$docCount;
        }

        return $refinementValue;
    }
}
