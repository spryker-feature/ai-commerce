<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchArgumentResolver;

class ToolResultProductExpander implements ToolResultProductExpanderInterface
{
    /**
     * @var list<string>
     */
    protected const array AGENT_EXCLUDED_KEYS = [
        StorefrontAssistantProductMapper::KEY_IMAGE_URL,
        StorefrontAssistantProductMapper::KEY_URL,
        StorefrontAssistantProductMapper::KEY_CURRENCY_ISO_CODE,
        StorefrontAssistantProductMapper::KEY_PRICE_MODE,
    ];

    /**
     * @var list<string>
     */
    protected const array AGENT_PRICE_KEYS = [
        StorefrontAssistantProductMapper::KEY_PRICE,
        StorefrontAssistantProductMapper::KEY_ORIGINAL_PRICE,
    ];

    protected const int AGENT_PRICE_FRACTION_DIGITS = 2;

    protected const int AGENT_DESCRIPTION_MAX_LENGTH = 200;

    protected const string AGENT_DESCRIPTION_ELLIPSIS = '…';

    public function __construct(
        protected StorefrontAssistantProductMapperInterface $storefrontAssistantProductMapper,
        protected ProductCollectionExpanderInterface $productCollectionExpander
    ) {
    }

    public function expandProducts(array $products): array
    {
        if ($products === []) {
            return [];
        }

        return $this->storefrontAssistantProductMapper->mapStorefrontAssistantProductCollectionToProducts(
            $this->productCollectionExpander->expand(
                $this->storefrontAssistantProductMapper->mapProductsToStorefrontAssistantProductCollection(
                    $products,
                    new StorefrontAssistantProductCollectionTransfer(),
                ),
            ),
        );
    }

    public function expandProductsForAgent(array $products): array
    {
        return array_map(
            fn (array $product): array => $this->shortenDescription($this->convertPricesToMajorUnits(
                array_diff_key($product, array_flip(static::AGENT_EXCLUDED_KEYS)),
            )),
            $this->expandProducts($products),
        );
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array<string, mixed>
     */
    protected function convertPricesToMajorUnits(array $product): array
    {
        foreach (static::AGENT_PRICE_KEYS as $priceKey) {
            $price = $product[$priceKey] ?? null;

            if (!is_int($price)) {
                continue;
            }

            $product[$priceKey] = round($price / CatalogSearchArgumentResolver::PRICE_PRECISION, static::AGENT_PRICE_FRACTION_DIGITS);
        }

        return $product;
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array<string, mixed>
     */
    protected function shortenDescription(array $product): array
    {
        $description = $product[StorefrontAssistantProductMapper::KEY_DESCRIPTION] ?? null;

        if (!is_string($description)) {
            return $product;
        }

        $description = trim((string)preg_replace('/\s+/u', ' ', strip_tags($description)));

        if (mb_strlen($description) > static::AGENT_DESCRIPTION_MAX_LENGTH) {
            $description = rtrim(mb_substr($description, 0, static::AGENT_DESCRIPTION_MAX_LENGTH - mb_strlen(static::AGENT_DESCRIPTION_ELLIPSIS)))
                . static::AGENT_DESCRIPTION_ELLIPSIS;
        }

        $product[StorefrontAssistantProductMapper::KEY_DESCRIPTION] = $description;

        return $product;
    }
}
