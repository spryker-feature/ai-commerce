<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use Spryker\Client\Catalog\CatalogClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class CatalogSuggestionReader implements CatalogSuggestionReaderInterface
{
    public const string PARAMETER_QUERY = 'query';

    public const string PARAMETER_LIMIT = 'limit';

    public const int DEFAULT_LIMIT = 5;

    public const int LIMIT_MIN = 1;

    public const int LIMIT_MAX = 10;

    /**
     * @uses \Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter\SuggestionByTypeResultFormatterPlugin::NAME
     */
    protected const string RESULT_FORMATTER_KEY_SUGGESTION_BY_TYPE = 'suggestionByType';

    /**
     * @uses \Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter\CompletionResultFormatterPlugin
     */
    protected const string RESULT_FORMATTER_KEY_COMPLETION = 'completion';

    protected const string RESULT_KEY_MATCHING_TERMS = 'matchingTerms';

    protected const string RESULT_KEY_RESULTS_BY_TYPE = 'resultsByType';

    protected const string RESULT_KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string NEXT_ACTION_NOTHING_MATCHED = 'This shop publishes nothing under that term. Try the plain product noun, or another word the customer used, before telling the customer it is unavailable.';

    protected const int MAX_MATCHING_TERMS = 10;

    public function __construct(
        protected CatalogClientInterface $catalogClient,
        protected SuggestionResultFormatterInterface $suggestionResultFormatter,
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function getSuggestions(array $arguments): array
    {
        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $query = $this->resolveQuery($arguments);

        if ($query === '') {
            return [
                static::RESULT_KEY_MATCHING_TERMS => [],
                static::RESULT_KEY_RESULTS_BY_TYPE => [],
            ];
        }

        $suggestionResults = $this->catalogClient->catalogSuggestSearch($query);

        return $this->buildResult($suggestionResults, $this->resolveLimit($arguments));
    }

    /**
     * @param array<string, mixed> $suggestionResults
     *
     * @return array<string, mixed>
     */
    protected function buildResult(array $suggestionResults, int $limit): array
    {
        $matchingTerms = $this->resolveMatchingTerms($suggestionResults);
        $suggestionsByType = $suggestionResults[static::RESULT_FORMATTER_KEY_SUGGESTION_BY_TYPE] ?? [];
        $resultsByType = is_array($suggestionsByType)
            ? $this->suggestionResultFormatter->formatSuggestionsByType($suggestionsByType, $limit)
            : [];

        $result = [
            static::RESULT_KEY_MATCHING_TERMS => $matchingTerms,
            static::RESULT_KEY_RESULTS_BY_TYPE => $resultsByType,
        ];

        if ($matchingTerms === [] && $resultsByType === []) {
            $result[static::RESULT_KEY_SUGGESTED_NEXT_ACTION] = static::NEXT_ACTION_NOTHING_MATCHED;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $suggestionResults
     *
     * @return array<int, string>
     */
    protected function resolveMatchingTerms(array $suggestionResults): array
    {
        $completions = $suggestionResults[static::RESULT_FORMATTER_KEY_COMPLETION] ?? null;

        if (!is_array($completions)) {
            return [];
        }

        $matchingTerms = [];

        foreach ($completions as $completion) {
            if (!is_scalar($completion)) {
                continue;
            }

            $completion = trim((string)$completion);

            if ($completion !== '') {
                $matchingTerms[] = $completion;
            }
        }

        return array_slice(array_values(array_unique($matchingTerms)), 0, static::MAX_MATCHING_TERMS);
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function resolveLimit(array $arguments): int
    {
        $limit = $arguments[static::PARAMETER_LIMIT] ?? null;

        if (!is_numeric($limit)) {
            return static::DEFAULT_LIMIT;
        }

        $limit = (int)$limit;

        if ($limit < static::LIMIT_MIN || $limit > static::LIMIT_MAX) {
            return static::DEFAULT_LIMIT;
        }

        return $limit;
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function resolveQuery(array $arguments): string
    {
        $query = $arguments[static::PARAMETER_QUERY] ?? null;

        return is_scalar($query) ? trim((string)$query) : '';
    }
}
