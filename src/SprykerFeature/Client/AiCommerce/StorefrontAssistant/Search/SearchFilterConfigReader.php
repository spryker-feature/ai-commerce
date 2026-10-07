<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use Generated\Shared\Transfer\FacetConfigTransfer;
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface;

class SearchFilterConfigReader implements SearchFilterConfigReaderInterface
{
    protected const string PARAMETER_NAME_MERCHANT_NAME = 'merchant_name';

    protected const string PARAMETER_NAME_MERCHANT_REFERENCE = 'merchant_reference';

    /**
     * @var list<string>
     */
    protected const array EXCLUDED_PARAMETER_NAMES = [
        self::PARAMETER_NAME_MERCHANT_NAME,
        self::PARAMETER_NAME_MERCHANT_REFERENCE,
    ];

    /**
     * @var list<string>
     */
    protected const array RESERVED_PARAMETER_NAMES = [
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_CATEGORY,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PRICE,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_RATING,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_SORT,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PAGE,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_ITEMS_PER_PAGE,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_LABEL,
        CatalogSearchArgumentResolver::REQUEST_PARAMETER_PRODUCT_CLASS,
    ];

    /**
     * @param list<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface> $searchConfigExpanderPlugins
     */
    public function __construct(protected array $searchConfigExpanderPlugins)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, \Generated\Shared\Transfer\FacetConfigTransfer>
     */
    public function getFilterableFacetConfigs(): array
    {
        $facetConfigTransfersByParameterName = [];

        foreach ($this->searchConfigExpanderPlugins as $searchConfigExpanderPlugin) {
            foreach ($this->getFacetConfigTransfers($searchConfigExpanderPlugin) as $facetConfigTransfer) {
                $parameterName = $this->resolveParameterName($facetConfigTransfer);

                if ($parameterName === null) {
                    continue;
                }

                $facetConfigTransfersByParameterName[$parameterName] = $facetConfigTransfer;
            }
        }

        return $facetConfigTransfersByParameterName;
    }

    /**
     * @return iterable<\Generated\Shared\Transfer\FacetConfigTransfer>
     */
    protected function getFacetConfigTransfers(
        SearchConfigExpanderPluginInterface $searchConfigExpanderPlugin
    ): iterable {
        return $searchConfigExpanderPlugin->getSearchConfigExtension()->getFacetConfigs();
    }

    protected function resolveParameterName(FacetConfigTransfer $facetConfigTransfer): ?string
    {
        $parameterName = trim((string)$facetConfigTransfer->getParameterName());

        if ($parameterName === '') {
            return null;
        }

        if (in_array($parameterName, static::EXCLUDED_PARAMETER_NAMES, true)) {
            return null;
        }

        if (in_array($parameterName, static::RESERVED_PARAMETER_NAMES, true)) {
            return null;
        }

        return $parameterName;
    }
}
