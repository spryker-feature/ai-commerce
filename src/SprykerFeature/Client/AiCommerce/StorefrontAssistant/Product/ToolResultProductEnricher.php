<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ProductComparisonBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class ToolResultProductEnricher implements ToolResultProductEnricherInterface
{
    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const int ENCODING_FLAGS = JSON_INVALID_UTF8_SUBSTITUTE;

    /**
     * The comparison keeps the fields the model compared; the customer only also needs the link and the image.
     *
     * @var list<string>
     */
    protected const array COMPARED_PRODUCT_DISPLAY_KEYS = [
        StorefrontAssistantProductMapper::KEY_URL,
        StorefrontAssistantProductMapper::KEY_IMAGE_URL,
    ];

    public function __construct(protected ToolResultProductExpanderInterface $toolResultProductExpander)
    {
    }

    public function expandToolResult(string $toolName, string $toolResult): string
    {
        return $this->enrich($toolResult);
    }

    public function enrich(string $toolResult): string
    {
        $decodedToolResult = json_decode($toolResult, true);

        if (!is_array($decodedToolResult)) {
            return $toolResult;
        }

        $products = $decodedToolResult[static::RESULT_KEY_PRODUCTS] ?? null;
        $comparedProducts = $decodedToolResult[ProductComparisonBuilder::RESULT_KEY_COMPARED_PRODUCTS] ?? null;
        $hasProducts = is_array($products) && $products !== [];
        $hasComparedProducts = is_array($comparedProducts) && $comparedProducts !== [];

        if (!$hasProducts && !$hasComparedProducts) {
            return $toolResult;
        }

        if ($hasProducts) {
            $decodedToolResult[static::RESULT_KEY_PRODUCTS] = $this->toolResultProductExpander->expandProducts($products);
        }

        if ($hasComparedProducts) {
            $decodedToolResult[ProductComparisonBuilder::RESULT_KEY_COMPARED_PRODUCTS] = $this->addDisplayFields($comparedProducts);
        }

        $encodedToolResult = json_encode($decodedToolResult, static::ENCODING_FLAGS);

        return $encodedToolResult !== false ? $encodedToolResult : $toolResult;
    }

    /**
     * @param array<int|string, mixed> $comparedProducts
     *
     * @return array<int|string, mixed>
     */
    protected function addDisplayFields(array $comparedProducts): array
    {
        $displayFieldsByIdProductAbstract = $this->getDisplayFieldsByIdProductAbstract($comparedProducts);

        foreach ($comparedProducts as $index => $comparedProduct) {
            $idProductAbstract = is_array($comparedProduct) ? ($comparedProduct[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? null) : null;

            if (!isset($displayFieldsByIdProductAbstract[$idProductAbstract])) {
                continue;
            }

            $comparedProducts[$index] = $comparedProduct + $displayFieldsByIdProductAbstract[$idProductAbstract];
        }

        return $comparedProducts;
    }

    /**
     * @param array<int|string, mixed> $comparedProducts
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getDisplayFieldsByIdProductAbstract(array $comparedProducts): array
    {
        $displayFieldsByIdProductAbstract = [];

        foreach ($this->toolResultProductExpander->expandProducts(array_values(array_filter($comparedProducts, 'is_array'))) as $expandedProduct) {
            $idProductAbstract = $expandedProduct[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? null;

            if (!is_int($idProductAbstract)) {
                continue;
            }

            $displayFieldsByIdProductAbstract[$idProductAbstract] = array_intersect_key(
                $expandedProduct,
                array_flip(static::COMPARED_PRODUCT_DISPLAY_KEYS),
            );
        }

        return $displayFieldsByIdProductAbstract;
    }
}
