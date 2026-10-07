<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

class ToolResultProductEnricher implements ToolResultProductEnricherInterface
{
    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const int ENCODING_FLAGS = JSON_INVALID_UTF8_SUBSTITUTE;

    public function __construct(protected ToolResultProductExpanderInterface $toolResultProductExpander)
    {
    }

    public function enrich(string $toolResult): string
    {
        $decodedToolResult = json_decode($toolResult, true);

        if (!is_array($decodedToolResult)) {
            return $toolResult;
        }

        $products = $decodedToolResult[static::RESULT_KEY_PRODUCTS] ?? null;

        if (!is_array($products) || $products === []) {
            return $toolResult;
        }

        $decodedToolResult[static::RESULT_KEY_PRODUCTS] = $this->toolResultProductExpander->expandProducts($products);

        $encodedToolResult = json_encode($decodedToolResult, static::ENCODING_FLAGS);

        return $encodedToolResult !== false ? $encodedToolResult : $toolResult;
    }
}
