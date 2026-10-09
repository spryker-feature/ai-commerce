<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class ProductAttributeDetailsExpander implements ProductDetailsExpanderInterface
{
    public const string KEY_VARIANTS = StorefrontAssistantProductMapper::KEY_VARIANTS;

    public const string KEY_SUPER_ATTRIBUTES = 'superAttributes';

    public const string KEY_CONCRETES = 'concretes';

    public const string KEY_CONCRETE_COUNT = 'concreteCount';

    public const string KEY_CONCRETE_SKU = 'sku';

    public const string KEY_CONCRETE_ATTRIBUTES = 'attributes';

    public const int MAX_CONCRETES = 20;

    protected const string STORAGE_KEY_ATTRIBUTES = 'attributes';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_SUPER_ATTRIBUTES = 'super_attributes';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string STORAGE_KEY_ATTRIBUTE_VARIANT_MAP = 'attribute_variant_map';

    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, mixed>
     */
    public function expand(
        array $productDetails,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): array {
        $productAbstractStorageData = $storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData();
        unset(
            $productDetails[StorefrontAssistantProductMapper::KEY_ATTRIBUTES],
            $productDetails[StorefrontAssistantProductMapper::KEY_VARIANTS],
            $productDetails[StorefrontAssistantProductMapper::KEY_VARIANT_COUNT],
        );

        $attributes = $this->extractScalarAttributes($productAbstractStorageData[static::STORAGE_KEY_ATTRIBUTES] ?? null);

        if ($attributes !== []) {
            $productDetails[StorefrontAssistantProductMapper::KEY_ATTRIBUTES] = $attributes;
        }

        $attributeMap = $productAbstractStorageData[static::STORAGE_KEY_ATTRIBUTE_MAP] ?? null;
        $variants = is_array($attributeMap) ? $this->buildVariants($attributeMap) : [];

        if ($variants !== []) {
            $productDetails[static::KEY_VARIANTS] = $variants;
        }

        return $productDetails;
    }

    /**
     * @param array<mixed> $attributeMap
     *
     * @return array<string, mixed>
     */
    protected function buildVariants(array $attributeMap): array
    {
        $concretes = $this->buildConcretes($attributeMap);

        if ($concretes === []) {
            return [];
        }

        $variants = [];
        $superAttributes = $this->extractSuperAttributes($attributeMap[static::STORAGE_KEY_SUPER_ATTRIBUTES] ?? null);

        if ($superAttributes !== []) {
            $variants[static::KEY_SUPER_ATTRIBUTES] = $superAttributes;
        }

        $variants[static::KEY_CONCRETE_COUNT] = count($concretes);
        $variants[static::KEY_CONCRETES] = array_slice($concretes, 0, static::MAX_CONCRETES);

        return $variants;
    }

    /**
     * @param array<mixed> $attributeMap
     *
     * @return list<array<string, mixed>>
     */
    protected function buildConcretes(array $attributeMap): array
    {
        $productConcreteIds = $attributeMap[static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($productConcreteIds)) {
            return [];
        }

        $attributeVariantMap = $attributeMap[static::STORAGE_KEY_ATTRIBUTE_VARIANT_MAP] ?? null;
        $attributeVariantMap = is_array($attributeVariantMap) ? $attributeVariantMap : [];
        $concretes = [];

        foreach ($productConcreteIds as $concreteSku => $idProductConcrete) {
            if (is_bool($idProductConcrete) || !is_numeric($idProductConcrete) || trim((string)$concreteSku) === '') {
                continue;
            }

            $concrete = [static::KEY_CONCRETE_SKU => (string)$concreteSku];
            $concreteAttributes = $this->extractScalarAttributes($attributeVariantMap[(int)$idProductConcrete] ?? null);

            if ($concreteAttributes !== []) {
                $concrete[static::KEY_CONCRETE_ATTRIBUTES] = $concreteAttributes;
            }

            $concretes[] = $concrete;
        }

        return $concretes;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function extractSuperAttributes(mixed $storedSuperAttributes): array
    {
        if (!is_array($storedSuperAttributes)) {
            return [];
        }

        $superAttributes = [];

        foreach ($storedSuperAttributes as $attributeKey => $attributeValues) {
            if (!is_string($attributeKey) || !is_array($attributeValues)) {
                continue;
            }

            $values = array_values(array_unique(array_map(
                static fn ($value): string => trim((string)$value),
                array_filter($attributeValues, static fn ($value): bool => is_scalar($value) && !is_bool($value) && trim((string)$value) !== ''),
            )));

            if ($values !== []) {
                $superAttributes[$attributeKey] = $values;
            }
        }

        return $superAttributes;
    }

    /**
     * @return array<string, string>
     */
    protected function extractScalarAttributes(mixed $storedAttributes): array
    {
        if (!is_array($storedAttributes)) {
            return [];
        }

        $attributes = [];

        foreach ($storedAttributes as $attributeKey => $attributeValue) {
            if (!is_string($attributeKey) || !is_scalar($attributeValue) || is_bool($attributeValue)) {
                continue;
            }

            $attributeValue = trim((string)$attributeValue);

            if ($attributeValue !== '') {
                $attributes[$attributeKey] = $attributeValue;
            }
        }

        return $attributes;
    }
}
