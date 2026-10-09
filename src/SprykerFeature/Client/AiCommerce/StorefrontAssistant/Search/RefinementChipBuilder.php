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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;

class RefinementChipBuilder implements RefinementChipBuilderInterface
{
    public const string KEY_LABEL = 'label';

    public const string KEY_FILTER_ARGUMENT = 'filterArgument';

    public const string KEY_VALUE = 'value';

    public const string KEY_MATCH_COUNT = 'matchCount';

    protected const string RESULT_KEY_TOTAL_RESULTS = 'totalResults';

    protected const string RESULT_KEY_AVAILABLE_REFINEMENTS = 'availableRefinements';

    protected const string RESULT_KEY_APPLIED_FILTERS = 'appliedFilters';

    protected const string REFINEMENT_KEY_NAME = 'name';

    protected const string REFINEMENT_KEY_VALUES = 'values';

    protected const int MAX_CHIPS_PER_REFINEMENT = 2;

    protected const string PRICE_RANGE_LABEL = '%s – %s';

    protected const string PRICE_MIN_LABEL = '≥ %s';

    protected const string PRICE_MAX_LABEL = '≤ %s';

    protected const string RATING_LABEL = '≥ %d★';

    protected const string FILTER_ARGUMENT_SEPARATOR = ', ';

    /**
     * @var list<string>
     */
    protected const array NON_FILTER_REQUEST_PARAMETERS = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_SORT,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PAGE,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_ITEMS_PER_PAGE,
    ];

    public function __construct(
        protected CatalogSearchArgumentResolverInterface $catalogSearchArgumentResolver,
        protected StorefrontAssistantPriceFormatterInterface $storefrontAssistantPriceFormatter,
        protected CategoryStorageClientInterface $categoryStorageClient,
        protected LocaleClientInterface $localeClient,
        protected StoreClientInterface $storeClient
    ) {
    }

    /**
     * @param array<string, mixed> $searchResult
     *
     * @return list<array<string, mixed>>
     */
    public function buildRefinementChips(array $searchResult, int $limit): array
    {
        $totalResults = $searchResult[static::RESULT_KEY_TOTAL_RESULTS] ?? null;

        if (!is_int($totalResults) || $totalResults <= 0 || $limit <= 0) {
            return [];
        }

        $chips = [];

        foreach ($this->rankRefinementChipCandidates($searchResult, $totalResults) as $candidates) {
            foreach (array_slice($candidates, 0, static::MAX_CHIPS_PER_REFINEMENT) as $chip) {
                $chips[] = $chip;

                if (count($chips) >= $limit) {
                    return $chips;
                }
            }
        }

        return $chips;
    }

    /**
     * @param array<string, mixed> $searchResult
     *
     * @return list<array<string, string>>
     */
    public function buildActiveFilters(array $searchResult): array
    {
        $appliedFilters = $searchResult[static::RESULT_KEY_APPLIED_FILTERS] ?? null;

        if (!is_array($appliedFilters) || $appliedFilters === []) {
            return [];
        }

        $refinements = $this->indexRefinementsByName($searchResult);
        $activeFilters = [];

        foreach ($appliedFilters as $parameterName => $value) {
            $parameterName = (string)$parameterName;

            if (in_array($parameterName, static::NON_FILTER_REQUEST_PARAMETERS, true)) {
                continue;
            }

            $activeFilters = array_merge($activeFilters, $this->buildActiveFiltersForParameter($parameterName, $value, $refinements));
        }

        return $activeFilters;
    }

    /**
     * @param array<string, mixed> $searchResult
     *
     * @return list<list<array<string, mixed>>>
     */
    protected function rankRefinementChipCandidates(array $searchResult, int $totalResults): array
    {
        $appliedFilters = $searchResult[static::RESULT_KEY_APPLIED_FILTERS] ?? [];
        $appliedParameterNames = is_array($appliedFilters) ? array_map('strval', array_keys($appliedFilters)) : [];
        $rankedCandidates = [];

        foreach ($this->getRefinements($searchResult) as $position => $refinement) {
            $name = (string)($refinement[static::REFINEMENT_KEY_NAME] ?? '');
            $filterArgument = (string)($refinement[static::KEY_FILTER_ARGUMENT] ?? '');

            if ($name === '' || $filterArgument === '' || in_array($name, $appliedParameterNames, true)) {
                continue;
            }

            $candidates = $this->buildChipCandidates($refinement, $filterArgument, $totalResults);

            if ($candidates === []) {
                continue;
            }

            $rankedCandidates[] = [
                'coverage' => min(1.0, array_sum(array_column($candidates, static::KEY_MATCH_COUNT)) / $totalResults),
                'position' => $position,
                'candidates' => $candidates,
            ];
        }

        usort(
            $rankedCandidates,
            static fn (array $a, array $b): int => [$b['coverage'], $a['position']] <=> [$a['coverage'], $b['position']],
        );

        return array_column($rankedCandidates, 'candidates');
    }

    /**
     * @param array<string, mixed> $refinement
     *
     * @return list<array<string, mixed>>
     */
    protected function buildChipCandidates(array $refinement, string $filterArgument, int $totalResults): array
    {
        $values = $refinement[static::REFINEMENT_KEY_VALUES] ?? null;

        if (!is_array($values)) {
            return [];
        }

        $candidates = [];

        foreach ($values as $refinementValue) {
            $matchCount = is_array($refinementValue) ? ($refinementValue[static::KEY_MATCH_COUNT] ?? null) : null;
            $value = is_array($refinementValue) ? trim((string)($refinementValue[static::KEY_VALUE] ?? '')) : '';

            if (!is_int($matchCount) || $matchCount <= 0 || $matchCount >= $totalResults || $value === '') {
                continue;
            }

            $candidates[] = [
                static::KEY_LABEL => $this->resolveValueLabel($refinementValue, $value),
                static::KEY_FILTER_ARGUMENT => $filterArgument,
                static::KEY_VALUE => $value,
                static::KEY_MATCH_COUNT => $matchCount,
            ];
        }

        return $candidates;
    }

    /**
     * @param array<string, array<string, mixed>> $refinements
     *
     * @return list<array<string, string>>
     */
    protected function buildActiveFiltersForParameter(string $parameterName, mixed $value, array $refinements): array
    {
        if ($parameterName === CatalogSearchArgumentResolver::REQUEST_PARAMETER_PRICE) {
            $priceFilter = is_array($value) ? $this->buildPriceFilter($value) : null;

            return $priceFilter !== null ? [$priceFilter] : [];
        }

        if ($parameterName === CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING) {
            return is_numeric($value) ? [$this->createActiveFilter(
                sprintf(static::RATING_LABEL, (int)$value),
                CatalogSearchArgumentResolver::PARAMETER_RATING_MIN,
                (string)(int)$value,
            )] : [];
        }

        $refinement = $refinements[$parameterName] ?? [];
        $filterArgument = (string)($refinement[static::KEY_FILTER_ARGUMENT] ?? '');
        $filterArgument = $filterArgument !== ''
            ? $filterArgument
            : (CatalogSearchArgumentResolver::REQUEST_PARAMETER_TO_FILTER_ARGUMENT[$parameterName] ?? $this->catalogSearchArgumentResolver->formatFilterArgument($parameterName));
        $activeFilters = [];

        foreach ($this->extractScalarValues($value) as $filterValue) {
            $activeFilters[] = $this->createActiveFilter(
                $this->resolveActiveFilterLabel($parameterName, $filterValue, $refinement),
                $filterArgument,
                $filterValue,
            );
        }

        return $activeFilters;
    }

    /**
     * @param array<int|string, mixed> $priceRange
     *
     * @return array<string, string>|null
     */
    protected function buildPriceFilter(array $priceRange): ?array
    {
        $min = $priceRange[CatalogSearchArgumentResolver::RANGE_KEY_MIN] ?? null;
        $max = $priceRange[CatalogSearchArgumentResolver::RANGE_KEY_MAX] ?? null;
        $minFormatted = is_numeric($min) ? $this->formatPrice((float)$min) : null;
        $maxFormatted = is_numeric($max) ? $this->formatPrice((float)$max) : null;

        if ($minFormatted !== null && $maxFormatted !== null) {
            $label = sprintf(static::PRICE_RANGE_LABEL, $minFormatted, $maxFormatted);

            return $this->createActiveFilter(
                $label,
                CatalogSearchArgumentResolver::PARAMETER_PRICE_MIN . static::FILTER_ARGUMENT_SEPARATOR . CatalogSearchArgumentResolver::PARAMETER_PRICE_MAX,
                $label,
            );
        }

        if ($minFormatted !== null) {
            return $this->createActiveFilter(sprintf(static::PRICE_MIN_LABEL, $minFormatted), CatalogSearchArgumentResolver::PARAMETER_PRICE_MIN, (string)$min);
        }

        if ($maxFormatted !== null) {
            return $this->createActiveFilter(sprintf(static::PRICE_MAX_LABEL, $maxFormatted), CatalogSearchArgumentResolver::PARAMETER_PRICE_MAX, (string)$max);
        }

        return null;
    }

    protected function formatPrice(float $amount): string
    {
        return $this->storefrontAssistantPriceFormatter->formatAmount(
            (int)round($amount * CatalogSearchArgumentResolver::PRICE_PRECISION),
        );
    }

    /**
     * @param array<string, mixed> $refinement
     */
    protected function resolveActiveFilterLabel(string $parameterName, string $filterValue, array $refinement): string
    {
        $values = $refinement[static::REFINEMENT_KEY_VALUES] ?? [];

        foreach (is_array($values) ? $values : [] as $refinementValue) {
            if (is_array($refinementValue) && (string)($refinementValue[static::KEY_VALUE] ?? '') === $filterValue) {
                return $this->resolveValueLabel($refinementValue, $filterValue);
            }
        }

        if ($parameterName === CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY && ctype_digit($filterValue)) {
            return $this->findCategoryNodeName((int)$filterValue) ?? $filterValue;
        }

        return $filterValue;
    }

    protected function findCategoryNodeName(int $idCategoryNode): ?string
    {
        $categoryNodeStorageTransfers = $this->categoryStorageClient->getCategoryNodeByIds(
            [$idCategoryNode],
            $this->localeClient->getCurrentLocale(),
            $this->storeClient->getCurrentStore()->getNameOrFail(),
        );

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            if ($categoryNodeStorageTransfer->getNodeId() === $idCategoryNode && (string)$categoryNodeStorageTransfer->getName() !== '') {
                return (string)$categoryNodeStorageTransfer->getName();
            }
        }

        return null;
    }

    protected function resolveValueLabel(mixed $refinementValue, string $value): string
    {
        $label = is_array($refinementValue) ? trim((string)($refinementValue[static::KEY_LABEL] ?? '')) : '';

        return $label !== '' ? $label : $value;
    }

    /**
     * @return list<string>
     */
    protected function extractScalarValues(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $scalarValues = [];

        foreach ($values as $singleValue) {
            if (!is_scalar($singleValue)) {
                continue;
            }

            $singleValue = trim((string)$singleValue);

            if ($singleValue !== '') {
                $scalarValues[] = $singleValue;
            }
        }

        return $scalarValues;
    }

    /**
     * @return array<string, string>
     */
    protected function createActiveFilter(string $label, string $filterArgument, string $value): array
    {
        return [
            static::KEY_LABEL => $label,
            static::KEY_FILTER_ARGUMENT => $filterArgument,
            static::KEY_VALUE => $value,
        ];
    }

    /**
     * @param array<string, mixed> $searchResult
     *
     * @return array<string, array<string, mixed>>
     */
    protected function indexRefinementsByName(array $searchResult): array
    {
        $refinements = [];

        foreach ($this->getRefinements($searchResult) as $refinement) {
            $refinements[(string)($refinement[static::REFINEMENT_KEY_NAME] ?? '')] = $refinement;
        }

        return $refinements;
    }

    /**
     * @param array<string, mixed> $searchResult
     *
     * @return list<array<string, mixed>>
     */
    protected function getRefinements(array $searchResult): array
    {
        $refinements = $searchResult[static::RESULT_KEY_AVAILABLE_REFINEMENTS] ?? null;

        if (!is_array($refinements)) {
            return [];
        }

        return array_values(array_filter($refinements, 'is_array'));
    }
}
