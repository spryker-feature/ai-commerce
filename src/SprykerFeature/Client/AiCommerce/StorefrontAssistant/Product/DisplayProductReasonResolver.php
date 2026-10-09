<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class DisplayProductReasonResolver implements DisplayProductReasonResolverInterface
{
    public const int MAX_REASON_LENGTH = 60;

    protected const string ELLIPSIS = '…';

    protected const string SEPARATOR = ' · ';

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<int|string, mixed> $reasons
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolveReasons(array $products, array $reasons): array
    {
        foreach ($products as $index => $product) {
            $products[$index][StorefrontAssistantProductMapper::KEY_REASON] = $this->resolveReason($product, $reasons);
        }

        foreach ($this->groupIndexesByNormalizedValue($products, StorefrontAssistantProductMapper::KEY_NAME) as $sameNameIndexes) {
            $products = $this->distinguishSameNameReasons($products, $sameNameIndexes);
        }

        return $products;
    }

    /**
     * @param array<string, mixed> $product
     * @param array<int|string, mixed> $reasons
     */
    protected function resolveReason(array $product, array $reasons): string
    {
        $reason = $this->sanitize($reasons[(int)($product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? 0)] ?? null);

        if ($reason !== '') {
            return $this->truncate($reason, static::MAX_REASON_LENGTH);
        }

        $priceFormatted = $this->sanitize($product[StorefrontAssistantProductMapper::KEY_PRICE_FORMATTED] ?? null);

        return $this->truncate(
            $priceFormatted !== '' ? $priceFormatted : $this->sanitize($product[StorefrontAssistantProductMapper::KEY_SKU] ?? null),
            static::MAX_REASON_LENGTH,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @param list<int> $sameNameIndexes
     *
     * @return array<int, array<string, mixed>>
     */
    protected function distinguishSameNameReasons(array $products, array $sameNameIndexes): array
    {
        $sameNameProducts = array_intersect_key($products, array_flip($sameNameIndexes));

        foreach ($this->groupIndexesByNormalizedValue($sameNameProducts, StorefrontAssistantProductMapper::KEY_REASON) as $equalReasonIndexes) {
            $priceFormattedValues = array_map(
                fn (int $index): string => $this->sanitize($products[$index][StorefrontAssistantProductMapper::KEY_PRICE_FORMATTED] ?? null),
                $equalReasonIndexes,
            );
            $hasDistinctPrices = count(array_unique($priceFormattedValues)) === count($equalReasonIndexes) && !in_array('', $priceFormattedValues, true);

            foreach ($equalReasonIndexes as $position => $index) {
                $products[$index][StorefrontAssistantProductMapper::KEY_REASON] = $this->appendDistinction(
                    (string)$products[$index][StorefrontAssistantProductMapper::KEY_REASON],
                    $hasDistinctPrices ? $priceFormattedValues[$position] : $this->sanitize($products[$index][StorefrontAssistantProductMapper::KEY_SKU] ?? null),
                    $this->sanitize($products[$index][StorefrontAssistantProductMapper::KEY_SKU] ?? null),
                );
            }
        }

        return $products;
    }

    protected function appendDistinction(string $reason, string $distinction, string $sku): string
    {
        if ($distinction !== '' && mb_stripos($reason, $distinction) !== false) {
            $distinction = $sku;
        }

        if ($distinction === '' || mb_stripos($reason, $distinction) !== false) {
            return $reason;
        }

        $suffix = static::SEPARATOR . $distinction;

        return $this->truncate($reason, static::MAX_REASON_LENGTH - mb_strlen($suffix)) . $suffix;
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return list<list<int>>
     */
    protected function groupIndexesByNormalizedValue(array $products, string $key): array
    {
        $groups = [];

        foreach ($products as $index => $product) {
            $groups[mb_strtolower($this->sanitize($product[$key] ?? null))][] = $index;
        }

        return array_values(array_filter($groups, static fn (array $indexes): bool => count($indexes) > 1));
    }

    protected function sanitize(mixed $value): string
    {
        if (!is_scalar($value) || is_bool($value)) {
            return '';
        }

        return trim((string)preg_replace('/\s+/u', ' ', strip_tags((string)$value)));
    }

    protected function truncate(string $value, int $maxLength): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(0, $maxLength - mb_strlen(static::ELLIPSIS)))) . static::ELLIPSIS;
    }
}
