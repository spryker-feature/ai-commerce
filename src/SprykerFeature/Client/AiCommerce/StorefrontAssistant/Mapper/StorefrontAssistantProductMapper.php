<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductTransfer;

class StorefrontAssistantProductMapper implements StorefrontAssistantProductMapperInterface
{
    public const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    public const string KEY_NAME = 'name';

    public const string KEY_SKU = 'sku';

    public const string KEY_URL = 'url';

    public const string KEY_PRICE = 'price';

    public const string KEY_IMAGE_URL = 'imageUrl';

    public const string KEY_DESCRIPTION = 'description';

    public const string KEY_CURRENCY_ISO_CODE = 'currencyIsoCode';

    public const string KEY_PRICE_MODE = 'priceMode';

    public const string KEY_PRICE_FORMATTED = 'priceFormatted';

    public const string KEY_ORIGINAL_PRICE = 'originalPrice';

    public const string KEY_ORIGINAL_PRICE_FORMATTED = 'originalPriceFormatted';

    public function mapProductsToStorefrontAssistantProductCollection(
        array $products,
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer
    ): StorefrontAssistantProductCollectionTransfer {
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $storefrontAssistantProductCollectionTransfer->addStorefrontAssistantProduct(
                (new StorefrontAssistantProductTransfer())
                    ->setIdProductAbstract($this->extractInt($product, static::KEY_ID_PRODUCT_ABSTRACT))
                    ->setName($this->extractString($product, static::KEY_NAME))
                    ->setSku($this->extractString($product, static::KEY_SKU))
                    ->setUrl($this->extractString($product, static::KEY_URL))
                    ->setPrice($this->extractInt($product, static::KEY_PRICE)),
            );
        }

        return $storefrontAssistantProductCollectionTransfer;
    }

    public function mapStorefrontAssistantProductCollectionToProducts(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer
    ): array {
        $products = [];

        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $products[] = array_filter([
                static::KEY_ID_PRODUCT_ABSTRACT => $storefrontAssistantProductTransfer->getIdProductAbstract(),
                static::KEY_NAME => $storefrontAssistantProductTransfer->getName(),
                static::KEY_SKU => $storefrontAssistantProductTransfer->getSku(),
                static::KEY_URL => $storefrontAssistantProductTransfer->getUrl(),
                static::KEY_PRICE => $storefrontAssistantProductTransfer->getPrice(),
                static::KEY_IMAGE_URL => $storefrontAssistantProductTransfer->getImageUrl(),
                static::KEY_DESCRIPTION => $storefrontAssistantProductTransfer->getDescription(),
                static::KEY_CURRENCY_ISO_CODE => $storefrontAssistantProductTransfer->getCurrencyIsoCode(),
                static::KEY_PRICE_MODE => $storefrontAssistantProductTransfer->getPriceMode(),
                static::KEY_PRICE_FORMATTED => $storefrontAssistantProductTransfer->getPriceFormatted(),
                static::KEY_ORIGINAL_PRICE => $storefrontAssistantProductTransfer->getOriginalPrice(),
                static::KEY_ORIGINAL_PRICE_FORMATTED => $storefrontAssistantProductTransfer->getOriginalPriceFormatted(),
            ], static fn ($value): bool => $value !== null);
        }

        return $products;
    }

    /**
     * @param array<mixed> $product
     */
    protected function extractString(array $product, string $key): string
    {
        $value = $product[$key] ?? null;

        return is_scalar($value) ? trim((string)$value) : '';
    }

    /**
     * @param array<mixed> $product
     */
    protected function extractInt(array $product, string $key): ?int
    {
        $value = $product[$key] ?? null;

        if ($value === null || is_bool($value) || !is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }
}
