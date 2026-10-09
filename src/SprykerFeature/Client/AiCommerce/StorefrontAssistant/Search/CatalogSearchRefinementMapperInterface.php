<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface CatalogSearchRefinementMapperInterface
{
    /**
     * @param mixed $facets
     *
     * @return list<int>
     */
    public function extractCategoryNodeIds($facets): array;

    /**
     * @param mixed $facets
     * @param array<string, \Generated\Shared\Transfer\FacetConfigTransfer> $facetConfigTransfers
     * @param array<int, string> $categoryNodeNames
     *
     * @return array<int, array<string, mixed>>
     */
    public function mapAvailableRefinements($facets, array $facetConfigTransfers, array $categoryNodeNames = []): array;

    /**
     * @param mixed $facets
     *
     * @return array<string, float|string>|null
     */
    public function findPriceRange($facets): ?array;

    /**
     * @param mixed $facets
     *
     * @return array<string, int>|null
     */
    public function findRatingRange($facets): ?array;
}
