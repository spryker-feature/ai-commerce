<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\PriceOutlierMarkerInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;

class CatalogSearchResultExpander implements CatalogSearchResultExpanderInterface
{
    public function __construct(
        protected ToolResultProductExpanderInterface $toolResultProductExpander,
        protected PriceOutlierMarkerInterface $priceOutlierMarker
    ) {
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    public function expandWithProductData(array $result): array
    {
        $result[CatalogSearchResultMapper::RESULT_KEY_PRODUCTS] = $this->priceOutlierMarker->markPriceOutliers(
            $this->toolResultProductExpander->expandProductsForAgent($result[CatalogSearchResultMapper::RESULT_KEY_PRODUCTS]),
        );

        return $result;
    }
}
