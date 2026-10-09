<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

class CatalogSearchZeroResultAnalyzer implements CatalogSearchZeroResultAnalyzerInterface
{
    public const string EMPTIED_BY_KEY_ARGUMENT = 'argument';

    protected const string EMPTIED_BY_KEY_REQUESTED = 'requested';

    protected const string EMPTIED_BY_KEY_AVAILABLE_VALUES = 'availableValues';

    protected const string EMPTIED_BY_KEY_AVAILABLE_RANGE = 'availableRange';

    protected const string REFINEMENT_KEY_NAME = 'name';

    protected const string REFINEMENT_KEY_VALUES = 'values';

    protected const string REFINEMENT_KEY_FILTER_ARGUMENT = 'filterArgument';

    protected const string REFINEMENT_VALUE_KEY_VALUE = 'value';

    protected const string PRICE_ARGUMENT_SEPARATOR = ', ';

    protected const int MAX_AVAILABLE_VALUES = 10;

    /**
     * @var array<string, int>
     */
    protected const array MAX_AVAILABLE_VALUES_BY_REQUEST_PARAMETER = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_MERCHANT_NAME => 5,
    ];

    protected const int PRIORITY_DEFAULT = 0;

    /**
     * @var array<int, string>
     */
    protected const array NON_CONSTRAINT_REQUEST_PARAMETERS = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_ITEMS_PER_PAGE,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PAGE,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_SORT,
    ];

    /**
     * @var array<string, int>
     */
    protected const array REQUEST_PARAMETER_PRIORITY = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_MERCHANT_NAME => 1,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_LABEL => 2,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PRODUCT_CLASS => 3,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PRICE => 4,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING => 5,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY => 6,
    ];

    /**
     * @var array<int, string>
     */
    protected const array PRODUCT_TYPE_REQUEST_PARAMETERS = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY,
    ];

    public function __construct(protected CatalogSearchArgumentResolverInterface $catalogSearchArgumentResolver)
    {
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function hasConstraints(array $requestParameters): bool
    {
        return $this->filterConstraints($requestParameters) !== [];
    }

    /**
     * @param array<int, array<string, mixed>> $availableRefinements
     * @param array<string, mixed> $appliedFilters
     * @param array<string, float|string>|null $priceRange
     * @param array<string, int>|null $ratingRange
     *
     * @return array<int, array<string, mixed>>
     */
    public function analyze(
        array $availableRefinements,
        array $appliedFilters,
        ?array $priceRange,
        ?array $ratingRange
    ): array {
        $constraints = $this->filterConstraints($appliedFilters);
        uksort(
            $constraints,
            static fn ($a, $b): int => (static::REQUEST_PARAMETER_PRIORITY[(string)$a] ?? static::PRIORITY_DEFAULT)
                <=> (static::REQUEST_PARAMETER_PRIORITY[(string)$b] ?? static::PRIORITY_DEFAULT),
        );

        $refinementsByName = $this->indexRefinementsByName($availableRefinements);
        $primaryEntries = [];
        $secondaryEntries = [];
        $allEntries = [];

        foreach ($constraints as $parameterName => $requested) {
            $parameterName = (string)$parameterName;
            $entry = match ($parameterName) {
                CatalogSearchArgumentResolver::REQUEST_PARAMETER_PRICE => $this->createPriceEntry($requested, $priceRange),
                CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING => $this->createRatingEntry($requested, $ratingRange),
                default => $this->createTermEntry($parameterName, $requested, $refinementsByName[$parameterName] ?? null),
            };
            $allEntries[] = $entry;

            if (!$this->isEmptiedBy($parameterName, $entry)) {
                continue;
            }

            if (in_array($parameterName, static::PRODUCT_TYPE_REQUEST_PARAMETERS, true) || $this->isUnratedMatch($parameterName, $ratingRange)) {
                $secondaryEntries[] = $entry;

                continue;
            }

            $primaryEntries[] = $entry;
        }

        if ($primaryEntries !== []) {
            return $primaryEntries;
        }

        return $secondaryEntries !== [] ? $secondaryEntries : $allEntries;
    }

    /**
     * @param array<string, mixed> $entry
     */
    protected function isEmptiedBy(string $parameterName, array $entry): bool
    {
        if ($parameterName === CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING) {
            return true;
        }

        return isset($entry[static::EMPTIED_BY_KEY_AVAILABLE_VALUES]) || isset($entry[static::EMPTIED_BY_KEY_AVAILABLE_RANGE]);
    }

    /**
     * @param array<string, int>|null $ratingRange
     */
    protected function isUnratedMatch(string $parameterName, ?array $ratingRange): bool
    {
        return $parameterName === CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING && $ratingRange === null;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    protected function filterConstraints(array $parameters): array
    {
        return array_diff_key($parameters, array_flip(static::NON_CONSTRAINT_REQUEST_PARAMETERS));
    }

    /**
     * @param array<int, array<string, mixed>> $availableRefinements
     *
     * @return array<string, array<string, mixed>>
     */
    protected function indexRefinementsByName(array $availableRefinements): array
    {
        $refinementsByName = [];

        foreach ($availableRefinements as $refinement) {
            $name = $refinement[static::REFINEMENT_KEY_NAME] ?? null;

            if (is_string($name)) {
                $refinementsByName[$name] = $refinement;
            }
        }

        return $refinementsByName;
    }

    /**
     * @param mixed $requested
     * @param array<string, mixed>|null $refinement
     *
     * @return array<string, mixed>
     */
    protected function createTermEntry(string $parameterName, $requested, ?array $refinement): array
    {
        $entry = [
            static::EMPTIED_BY_KEY_ARGUMENT => $refinement[static::REFINEMENT_KEY_FILTER_ARGUMENT]
                ?? CatalogSearchArgumentResolver::REQUEST_PARAMETER_TO_FILTER_ARGUMENT[$parameterName]
                ?? $this->catalogSearchArgumentResolver->formatFilterArgument($parameterName),
            static::EMPTIED_BY_KEY_REQUESTED => $requested,
        ];

        $availableValues = $this->resolveAvailableValues(
            $requested,
            $refinement[static::REFINEMENT_KEY_VALUES] ?? [],
            static::MAX_AVAILABLE_VALUES_BY_REQUEST_PARAMETER[$parameterName] ?? static::MAX_AVAILABLE_VALUES,
        );

        if ($availableValues !== []) {
            $entry[static::EMPTIED_BY_KEY_AVAILABLE_VALUES] = $availableValues;
        }

        return $entry;
    }

    /**
     * @param mixed $requested
     * @param mixed $refinementValues
     *
     * @return array<int, array<string, mixed>>
     */
    protected function resolveAvailableValues($requested, $refinementValues, int $maxAvailableValues): array
    {
        if (!is_array($refinementValues)) {
            return [];
        }

        $requestedValues = array_map(
            static fn ($value): string => mb_strtolower(trim((string)$value)),
            array_filter(is_array($requested) ? $requested : [$requested], 'is_scalar'),
        );
        $availableValues = [];

        foreach ($refinementValues as $refinementValue) {
            $value = $refinementValue[static::REFINEMENT_VALUE_KEY_VALUE] ?? null;

            if (!is_scalar($value) || in_array(mb_strtolower((string)$value), $requestedValues, true)) {
                continue;
            }

            $availableValues[] = $refinementValue;

            if (count($availableValues) === $maxAvailableValues) {
                break;
            }
        }

        return $availableValues;
    }

    /**
     * @param mixed $requested
     * @param array<string, float|string>|null $priceRange
     *
     * @return array<string, mixed>
     */
    protected function createPriceEntry($requested, ?array $priceRange): array
    {
        $requested = is_array($requested) ? $requested : [];
        $entry = [
            static::EMPTIED_BY_KEY_ARGUMENT => $this->resolvePriceArgument($requested, $priceRange),
            static::EMPTIED_BY_KEY_REQUESTED => $requested,
        ];

        if ($priceRange !== null) {
            $entry[static::EMPTIED_BY_KEY_AVAILABLE_RANGE] = $priceRange;
        }

        return $entry;
    }

    /**
     * @param array<string, mixed> $requested
     * @param array<string, float|string>|null $priceRange
     */
    protected function resolvePriceArgument(array $requested, ?array $priceRange): string
    {
        $priceMin = $requested[CatalogSearchArgumentResolver::RANGE_KEY_MIN] ?? null;
        $priceMax = $requested[CatalogSearchArgumentResolver::RANGE_KEY_MAX] ?? null;

        if ($priceRange !== null) {
            if ($priceMax !== null && $priceMax < $priceRange[CatalogSearchArgumentResolver::RANGE_KEY_MIN]) {
                return CatalogSearchArgumentResolver::PARAMETER_PRICE_MAX;
            }

            if ($priceMin !== null && $priceMin > $priceRange[CatalogSearchArgumentResolver::RANGE_KEY_MAX]) {
                return CatalogSearchArgumentResolver::PARAMETER_PRICE_MIN;
            }
        }

        $arguments = [];

        if ($priceMin !== null) {
            $arguments[] = CatalogSearchArgumentResolver::PARAMETER_PRICE_MIN;
        }

        if ($priceMax !== null) {
            $arguments[] = CatalogSearchArgumentResolver::PARAMETER_PRICE_MAX;
        }

        return implode(static::PRICE_ARGUMENT_SEPARATOR, $arguments);
    }

    /**
     * @param mixed $requested
     * @param array<string, int>|null $ratingRange
     *
     * @return array<string, mixed>
     */
    protected function createRatingEntry($requested, ?array $ratingRange): array
    {
        $entry = [
            static::EMPTIED_BY_KEY_ARGUMENT => CatalogSearchArgumentResolver::PARAMETER_RATING_MIN,
            static::EMPTIED_BY_KEY_REQUESTED => $requested,
        ];

        if ($ratingRange !== null) {
            $entry[static::EMPTIED_BY_KEY_AVAILABLE_RANGE] = $this->resolveAvailableRatingRange((int)$requested, $ratingRange);
        }

        return $entry;
    }

    /**
     * @param array<string, int> $ratingRange
     *
     * @return array<string, int>
     */
    protected function resolveAvailableRatingRange(int $requestedRatingMin, array $ratingRange): array
    {
        $ratingMax = min(
            (int)$ratingRange[CatalogSearchArgumentResolver::RANGE_KEY_MAX],
            max(CatalogSearchArgumentResolver::RATING_MIN, $requestedRatingMin - 1),
        );

        return [
            CatalogSearchArgumentResolver::RANGE_KEY_MIN => min((int)$ratingRange[CatalogSearchArgumentResolver::RANGE_KEY_MIN], $ratingMax),
            CatalogSearchArgumentResolver::RANGE_KEY_MAX => $ratingMax,
        ];
    }
}
