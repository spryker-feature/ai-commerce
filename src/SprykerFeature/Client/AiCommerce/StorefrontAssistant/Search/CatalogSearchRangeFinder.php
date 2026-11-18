<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;

class CatalogSearchRangeFinder implements CatalogSearchRangeFinderInterface
{
    protected const string FACET_KEY_NAME = 'name';

    protected const string FACET_KEY_MIN = 'min';

    protected const string FACET_KEY_MAX = 'max';

    protected const string FACET_KEY_CONFIG = 'config';

    protected const string FACET_CONFIG_KEY_PARAMETER_NAME = 'parameter_name';

    protected const string FACET_CONFIG_KEY_PARAMETER_NAME_CAMEL_CASE = 'parameterName';

    protected const string RANGE_KEY_MIN_FORMATTED = 'minFormatted';

    protected const string RANGE_KEY_MAX_FORMATTED = 'maxFormatted';

    protected const int PRICE_RANGE_FRACTION_DIGITS = 2;

    public function __construct(
        protected SearchResultDataExtractorInterface $searchResultDataExtractor,
        protected StorefrontAssistantPriceFormatterInterface $storefrontAssistantPriceFormatter
    ) {
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
}
