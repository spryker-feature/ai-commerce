<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class DisplayProductReader implements DisplayProductReaderInterface
{
    public const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    public const int MAX_PRODUCTS = 5;

    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const string RESULT_KEY_DISPLAYED_COUNT = 'displayedCount';

    protected const string RESULT_KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS = 'unknownIdProductAbstracts';

    public function __construct(
        protected ToolResultProductExpanderInterface $toolResultProductExpander,
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
    public function getDisplayProducts(array $arguments): array
    {
        $productAbstractIds = $this->resolveProductAbstractIds($this->toolArgumentNormalizer->normalizeArguments($arguments));

        if ($productAbstractIds === []) {
            return [
                static::RESULT_KEY_PRODUCTS => [],
                static::RESULT_KEY_DISPLAYED_COUNT => 0,
            ];
        }

        $products = $this->rejectUnresolvedProducts(
            $this->toolResultProductExpander->expandProducts(
                array_map(
                    static fn (int $idProductAbstract): array => [StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
                    $productAbstractIds,
                ),
            ),
        );

        return $this->buildResult($products, $productAbstractIds);
    }

    /**
     * The identifiers the agent could not have shown are reported back, so a stale or invented
     * identifier is corrected on the next turn instead of silently shrinking the card list.
     *
     * @param array<int, array<string, mixed>> $products
     * @param array<int, int> $productAbstractIds
     *
     * @return array<string, mixed>
     */
    protected function buildResult(array $products, array $productAbstractIds): array
    {
        $result = [
            static::RESULT_KEY_PRODUCTS => $products,
            static::RESULT_KEY_DISPLAYED_COUNT => count($products),
        ];

        $displayedProductAbstractIds = array_column($products, StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT);
        $unknownProductAbstractIds = array_values(array_diff($productAbstractIds, $displayedProductAbstractIds));

        if ($unknownProductAbstractIds !== []) {
            $result[static::RESULT_KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS] = $unknownProductAbstractIds;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rejectUnresolvedProducts(array $products): array
    {
        return array_values(
            array_filter(
                $products,
                static fn (array $product): bool => trim((string)($product[StorefrontAssistantProductMapper::KEY_NAME] ?? '')) !== '',
            ),
        );
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<int, int>
     */
    protected function resolveProductAbstractIds(array $arguments): array
    {
        $value = $arguments[static::PARAMETER_ID_PRODUCT_ABSTRACTS] ?? null;

        if (!is_array($value)) {
            return [];
        }

        $productAbstractIds = [];

        foreach ($value as $idProductAbstract) {
            if (is_bool($idProductAbstract) || !is_numeric($idProductAbstract)) {
                continue;
            }

            $productAbstractIds[] = (int)$idProductAbstract;
        }

        $productAbstractIds = array_values(array_unique(array_filter(
            $productAbstractIds,
            static fn (int $idProductAbstract): bool => $idProductAbstract > 0,
        )));

        return array_slice($productAbstractIds, 0, static::MAX_PRODUCTS);
    }
}
