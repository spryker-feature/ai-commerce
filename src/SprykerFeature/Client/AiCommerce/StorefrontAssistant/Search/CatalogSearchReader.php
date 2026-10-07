<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use Spryker\Client\Catalog\CatalogClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryNodeNameReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class CatalogSearchReader implements CatalogSearchReaderInterface
{
    public function __construct(
        protected CatalogClientInterface $catalogClient,
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected CatalogSearchArgumentResolverInterface $catalogSearchArgumentResolver,
        protected CatalogSearchResultMapperInterface $catalogSearchResultMapper,
        protected SearchFilterConfigReaderInterface $searchFilterConfigReader,
        protected CategoryNodeNameReaderInterface $categoryNodeNameReader,
        protected ToolResultProductExpanderInterface $toolResultProductExpander
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function searchProducts(array $arguments): array
    {
        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $query = $this->catalogSearchArgumentResolver->resolveQuery($arguments);

        if ($query === '') {
            return $this->catalogSearchResultMapper->mapEmptyQueryResult();
        }

        $facetConfigTransfers = $this->searchFilterConfigReader->getFilterableFacetConfigs();
        $ignoredArguments = [];
        $page = $this->catalogSearchArgumentResolver->resolvePage($arguments);
        $limit = $this->catalogSearchArgumentResolver->resolveLimit($arguments, $ignoredArguments);
        $requestParameters = $this->catalogSearchArgumentResolver->resolveRequestParameters(
            $arguments,
            $ignoredArguments,
            $page,
            $limit,
            $facetConfigTransfers,
        );

        $searchResults = $this->catalogClient->catalogSearch($query, $requestParameters);

        $result = $this->catalogSearchResultMapper->mapSearchResults(
            $searchResults,
            $requestParameters,
            $ignoredArguments,
            $page,
            $limit,
            $facetConfigTransfers,
            $this->categoryNodeNameReader->getCategoryNodeNamesByIds(
                $this->catalogSearchResultMapper->extractCategoryNodeIds($searchResults),
            ),
        );

        $result[CatalogSearchResultMapper::RESULT_KEY_PRODUCTS] = $this->toolResultProductExpander->expandProductsForAgent(
            $result[CatalogSearchResultMapper::RESULT_KEY_PRODUCTS],
        );

        return $result;
    }
}
