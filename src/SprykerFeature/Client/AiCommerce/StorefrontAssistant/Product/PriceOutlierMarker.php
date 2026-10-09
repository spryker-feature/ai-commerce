<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class PriceOutlierMarker implements PriceOutlierMarkerInterface
{
    public function __construct(protected float $priceOutlierFactor)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    public function markPriceOutliers(array $products): array
    {
        foreach ($this->groupPricesByNormalizedName($products) as $pricesByIndex) {
            foreach ($pricesByIndex as $index => $price) {
                $otherPrices = $pricesByIndex;
                unset($otherPrices[$index]);

                if ($price > $this->priceOutlierFactor * $this->calculateMedian($otherPrices)) {
                    $products[$index][StorefrontAssistantProductMapper::KEY_IS_PRICE_OUTLIER] = true;
                }
            }
        }

        return $products;
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return list<array<int, float>>
     */
    protected function groupPricesByNormalizedName(array $products): array
    {
        $groups = [];

        foreach ($products as $index => $product) {
            $price = $product[StorefrontAssistantProductMapper::KEY_PRICE] ?? null;
            $name = $product[StorefrontAssistantProductMapper::KEY_NAME] ?? null;

            if ((!is_int($price) && !is_float($price)) || $price <= 0 || !is_string($name)) {
                continue;
            }

            $normalizedName = mb_strtolower(trim((string)preg_replace('/\s+/u', ' ', $name)));

            if ($normalizedName !== '') {
                $groups[$normalizedName][$index] = (float)$price;
            }
        }

        return array_values(array_filter($groups, static fn (array $pricesByIndex): bool => count($pricesByIndex) > 1));
    }

    /**
     * @param array<int, float> $prices
     */
    protected function calculateMedian(array $prices): float
    {
        sort($prices);
        $middle = intdiv(count($prices), 2);

        return count($prices) % 2 === 1 ? $prices[$middle] : ($prices[$middle - 1] + $prices[$middle]) / 2;
    }
}
