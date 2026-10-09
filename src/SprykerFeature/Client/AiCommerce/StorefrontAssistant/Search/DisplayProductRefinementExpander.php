<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class DisplayProductRefinementExpander implements DisplayProductRefinementExpanderInterface
{
    use LoggerTrait;

    /**
     * @uses \SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin::TOOL_NAME
     */
    protected const string TOOL_NAME_DISPLAY_PRODUCTS = 'display_products';

    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const string RESULT_KEY_REFINEMENT_CHIPS = 'refinementChips';

    protected const string RESULT_KEY_ACTIVE_FILTERS = 'activeFilters';

    protected const string RESULT_KEY_SEE_ALL = 'seeAll';

    protected const string ERROR_ENCODE_FAILED = 'Storefront Assistant display_products result could not be expanded: %s';

    public function __construct(
        protected CatalogSearchResultRegistryInterface $catalogSearchResultRegistry,
        protected RefinementChipBuilderInterface $refinementChipBuilder,
        protected SeeAllLinkBuilderInterface $seeAllLinkBuilder,
        protected int $maxRefinementChips
    ) {
    }

    public function expandToolResult(string $toolName, string $toolResult): string
    {
        if ($toolName !== static::TOOL_NAME_DISPLAY_PRODUCTS) {
            return $toolResult;
        }

        $decodedToolResult = json_decode($toolResult, true);

        if (!is_array($decodedToolResult)) {
            return $toolResult;
        }

        $productAbstractIds = $this->extractProductAbstractIds($decodedToolResult);
        $searchResult = $this->catalogSearchResultRegistry->findSearchResultContainingProductAbstractIds($productAbstractIds);

        if ($searchResult === null) {
            return $toolResult;
        }

        $expandedToolResult = $this->expandWithRefinements($decodedToolResult, $searchResult[CatalogSearchResultRegistry::ENTRY_KEY_RESULT]);
        $seeAll = $this->seeAllLinkBuilder->buildSeeAll($searchResult, count($productAbstractIds));

        if ($seeAll !== null) {
            $expandedToolResult[static::RESULT_KEY_SEE_ALL] = $seeAll;
        }

        if ($expandedToolResult === $decodedToolResult) {
            return $toolResult;
        }

        $encodedToolResult = json_encode($expandedToolResult);

        if ($encodedToolResult === false) {
            $this->getLogger()->warning(sprintf(static::ERROR_ENCODE_FAILED, json_last_error_msg()));

            return $toolResult;
        }

        return $encodedToolResult;
    }

    /**
     * @param array<string, mixed> $decodedToolResult
     * @param array<string, mixed> $catalogSearchResult
     *
     * @return array<string, mixed>
     */
    protected function expandWithRefinements(array $decodedToolResult, array $catalogSearchResult): array
    {
        $refinementChips = $this->refinementChipBuilder->buildRefinementChips($catalogSearchResult, $this->maxRefinementChips);
        $activeFilters = $this->refinementChipBuilder->buildActiveFilters($catalogSearchResult);

        if ($refinementChips === [] && $activeFilters === []) {
            return $decodedToolResult;
        }

        $decodedToolResult[static::RESULT_KEY_REFINEMENT_CHIPS] = $refinementChips;
        $decodedToolResult[static::RESULT_KEY_ACTIVE_FILTERS] = $activeFilters;

        return $decodedToolResult;
    }

    /**
     * @param array<string, mixed> $decodedToolResult
     *
     * @return array<int, int>
     */
    protected function extractProductAbstractIds(array $decodedToolResult): array
    {
        $products = $decodedToolResult[static::RESULT_KEY_PRODUCTS] ?? null;

        if (!is_array($products)) {
            return [];
        }

        $productAbstractIds = [];

        foreach ($products as $product) {
            $idProductAbstract = is_array($product) ? ($product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? null) : null;

            if (is_numeric($idProductAbstract)) {
                $productAbstractIds[] = (int)$idProductAbstract;
            }
        }

        return $productAbstractIds;
    }
}
