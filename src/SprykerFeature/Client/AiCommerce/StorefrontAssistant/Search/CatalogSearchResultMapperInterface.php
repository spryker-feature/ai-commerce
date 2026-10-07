<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface CatalogSearchResultMapperInterface
{
    /**
     * @return array<string, mixed>
     */
    public function mapEmptyQueryResult(): array;

    /**
     * @param array<string, mixed> $searchResults
     *
     * @return list<int>
     */
    public function extractCategoryNodeIds(array $searchResults): array;

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
    ): array;
}
