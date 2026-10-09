<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductTransfer;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class ProductStorageProductExpander implements ProductExpanderInterface
{
    protected const string STORAGE_KEY_DESCRIPTION = 'description';

    protected const string STORAGE_KEY_NAME = 'name';

    protected const string STORAGE_KEY_SKU = 'sku';

    protected const string STORAGE_KEY_URL = 'url';

    protected const string STORAGE_KEY_ATTRIBUTES = 'attributes';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string STORAGE_KEY_SUPER_ATTRIBUTES = 'super_attributes';

    protected const int MAX_ATTRIBUTES = 10;

    protected const int MAX_ATTRIBUTE_VALUE_LENGTH = 50;

    public function __construct(protected ProductStorageClientInterface $productStorageClient)
    {
    }

    public function expand(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer,
        string $localeName,
        string $storeName
    ): StorefrontAssistantProductCollectionTransfer {
        $productAbstractIds = $this->extractProductAbstractIds($storefrontAssistantProductCollectionTransfer);

        if ($productAbstractIds === []) {
            return $storefrontAssistantProductCollectionTransfer;
        }

        $productStorageData = $this->productStorageClient
            ->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
                $productAbstractIds,
                $localeName,
                $storeName,
            );

        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $productData = $productStorageData[$storefrontAssistantProductTransfer->getIdProductAbstract()] ?? null;

            if (!is_array($productData)) {
                continue;
            }

            $this->expandProductWithStorageData($storefrontAssistantProductTransfer, $productData);
        }

        return $storefrontAssistantProductCollectionTransfer;
    }

    /**
     * @param array<mixed> $productData
     */
    protected function expandProductWithStorageData(
        StorefrontAssistantProductTransfer $storefrontAssistantProductTransfer,
        array $productData
    ): void {
        $description = $productData[static::STORAGE_KEY_DESCRIPTION] ?? null;

        if (is_string($description) && $description !== '') {
            $storefrontAssistantProductTransfer->setDescription($description);
        }

        $this->setWhenBlank(
            $productData,
            static::STORAGE_KEY_NAME,
            $storefrontAssistantProductTransfer->getName(),
            static fn (string $name): StorefrontAssistantProductTransfer => $storefrontAssistantProductTransfer->setName($name),
        );

        $this->setWhenBlank(
            $productData,
            static::STORAGE_KEY_SKU,
            $storefrontAssistantProductTransfer->getSku(),
            static fn (string $sku): StorefrontAssistantProductTransfer => $storefrontAssistantProductTransfer->setSku($sku),
        );

        $this->setWhenBlank(
            $productData,
            static::STORAGE_KEY_URL,
            $storefrontAssistantProductTransfer->getUrl(),
            static fn (string $url): StorefrontAssistantProductTransfer => $storefrontAssistantProductTransfer->setUrl($url),
        );

        $storefrontAssistantProductTransfer->setAttributes($this->extractAttributes($productData));
        $this->expandProductWithConcreteProducts($storefrontAssistantProductTransfer, $productData);
    }

    /**
     * @param array<mixed> $productData
     *
     * @return array<string, string>
     */
    protected function extractAttributes(array $productData): array
    {
        $storedAttributes = $productData[static::STORAGE_KEY_ATTRIBUTES] ?? null;

        if (!is_array($storedAttributes)) {
            return [];
        }

        $attributes = [];

        foreach ($storedAttributes as $attributeKey => $attributeValue) {
            if (!is_string($attributeKey) || !is_scalar($attributeValue) || is_bool($attributeValue)) {
                continue;
            }

            $attributeValue = trim((string)$attributeValue);

            if ($attributeValue === '' || mb_strlen($attributeValue) > static::MAX_ATTRIBUTE_VALUE_LENGTH) {
                continue;
            }

            $attributes[$attributeKey] = $attributeValue;

            if (count($attributes) === static::MAX_ATTRIBUTES) {
                break;
            }
        }

        return $attributes;
    }

    /**
     * @param array<mixed> $productData
     */
    protected function expandProductWithConcreteProducts(
        StorefrontAssistantProductTransfer $storefrontAssistantProductTransfer,
        array $productData
    ): void {
        $attributeMap = $productData[static::STORAGE_KEY_ATTRIBUTE_MAP] ?? null;

        if (!is_array($attributeMap)) {
            return;
        }

        $productConcreteIds = $this->extractProductConcreteIds($attributeMap);

        foreach ($productConcreteIds as $concreteSku => $idProductConcrete) {
            $storefrontAssistantProductTransfer
                ->setIdProductConcrete($idProductConcrete)
                ->setConcreteSku((string)$concreteSku);

            break;
        }

        $variantCount = count($productConcreteIds);

        if ($variantCount <= 1) {
            return;
        }

        $storefrontAssistantProductTransfer
            ->setVariantCount($variantCount)
            ->setVariants($this->extractVariants($attributeMap));
    }

    /**
     * @param array<mixed> $attributeMap
     *
     * @return array<string|int, int>
     */
    protected function extractProductConcreteIds(array $attributeMap): array
    {
        $storedProductConcreteIds = $attributeMap[static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($storedProductConcreteIds)) {
            return [];
        }

        $productConcreteIds = [];

        foreach ($storedProductConcreteIds as $concreteSku => $idProductConcrete) {
            if (!is_bool($idProductConcrete) && is_numeric($idProductConcrete) && (int)$idProductConcrete > 0) {
                $productConcreteIds[$concreteSku] = (int)$idProductConcrete;
            }
        }

        return $productConcreteIds;
    }

    /**
     * @param array<mixed> $attributeMap
     *
     * @return array<string, list<string>>
     */
    protected function extractVariants(array $attributeMap): array
    {
        $superAttributes = $attributeMap[static::STORAGE_KEY_SUPER_ATTRIBUTES] ?? null;

        if (!is_array($superAttributes)) {
            return [];
        }

        $variants = [];

        foreach ($superAttributes as $attributeKey => $attributeValues) {
            if (!is_string($attributeKey) || !is_array($attributeValues)) {
                continue;
            }

            $values = array_values(array_unique(array_map(
                'strval',
                array_filter($attributeValues, static fn ($value): bool => is_scalar($value) && !is_bool($value) && trim((string)$value) !== ''),
            )));

            if ($values !== []) {
                $variants[$attributeKey] = $values;
            }
        }

        return $variants;
    }

    /**
     * @param array<mixed> $productData
     * @param callable(string): \Generated\Shared\Transfer\StorefrontAssistantProductTransfer $setter
     */
    protected function setWhenBlank(array $productData, string $storageKey, ?string $currentValue, callable $setter): void
    {
        if (trim((string)$currentValue) !== '') {
            return;
        }

        $value = $productData[$storageKey] ?? null;

        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $setter($value);
    }

    /**
     * @return array<int, int>
     */
    protected function extractProductAbstractIds(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer
    ): array {
        $productAbstractIds = [];

        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $idProductAbstract = $storefrontAssistantProductTransfer->getIdProductAbstract();

            if ($idProductAbstract !== null && $idProductAbstract > 0) {
                $productAbstractIds[] = $idProductAbstract;
            }
        }

        return array_values(array_unique($productAbstractIds));
    }
}
