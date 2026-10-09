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

    public const string KEY_RATING = 'rating';

    public const string KEY_REVIEW_COUNT = 'reviewCount';

    public const string KEY_LABEL_IDS = 'labelIds';

    public const string KEY_LABELS = 'labels';

    public const string KEY_ATTRIBUTES = 'attributes';

    public const string KEY_VARIANTS = 'variants';

    public const string KEY_VARIANT_COUNT = 'variantCount';

    public const string KEY_REASON = 'reason';

    public const string KEY_IS_PRICE_OUTLIER = 'isPriceOutlier';

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
                    ->setPrice($this->extractInt($product, static::KEY_PRICE))
                    ->setRating($this->extractFloat($product, static::KEY_RATING))
                    ->setReviewCount($this->extractInt($product, static::KEY_REVIEW_COUNT))
                    ->setLabelIds($this->extractIntList($product, static::KEY_LABEL_IDS))
                    ->setReason($this->extractNullableString($product, static::KEY_REASON))
                    ->setIsPriceOutlier(($product[static::KEY_IS_PRICE_OUTLIER] ?? null) === true ? true : null),
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
                static::KEY_RATING => $storefrontAssistantProductTransfer->getRating(),
                static::KEY_REVIEW_COUNT => $storefrontAssistantProductTransfer->getReviewCount(),
                static::KEY_LABELS => $storefrontAssistantProductTransfer->getLabels(),
                static::KEY_ATTRIBUTES => $storefrontAssistantProductTransfer->getAttributes(),
                static::KEY_VARIANTS => $storefrontAssistantProductTransfer->getVariants(),
                static::KEY_VARIANT_COUNT => $storefrontAssistantProductTransfer->getVariantCount(),
                static::KEY_REASON => $storefrontAssistantProductTransfer->getReason(),
                static::KEY_IS_PRICE_OUTLIER => $storefrontAssistantProductTransfer->getIsPriceOutlier() ?: null,
            ], static fn ($value): bool => $value !== null && $value !== []);
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
    protected function extractNullableString(array $product, string $key): ?string
    {
        $value = $this->extractString($product, $key);

        return $value !== '' ? $value : null;
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

    /**
     * @param array<mixed> $product
     */
    protected function extractFloat(array $product, string $key): ?float
    {
        $value = $product[$key] ?? null;

        if ($value === null || is_bool($value) || !is_numeric($value)) {
            return null;
        }

        return (float)$value;
    }

    /**
     * @param array<mixed> $product
     *
     * @return array<int, int>
     */
    protected function extractIntList(array $product, string $key): array
    {
        $values = $product[$key] ?? null;

        if (!is_array($values)) {
            return [];
        }

        $ints = [];

        foreach ($values as $value) {
            if (!is_bool($value) && is_numeric($value) && (int)$value > 0) {
                $ints[] = (int)$value;
            }
        }

        return array_values(array_unique($ints));
    }
}
