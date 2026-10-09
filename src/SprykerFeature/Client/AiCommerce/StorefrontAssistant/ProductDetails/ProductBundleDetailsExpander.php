<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\ProductBundleStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductBundleStorageTransfer;
use Generated\Shared\Transfer\ProductForProductBundleStorageTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\ProductBundleStorage\ProductBundleStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class ProductBundleDetailsExpander implements ProductDetailsExpanderInterface
{
    public const string KEY_BUNDLED_PRODUCTS = 'bundledProducts';

    public const string KEY_SKU = 'sku';

    public const string KEY_NAME = 'name';

    public const string KEY_QUANTITY = 'quantity';

    public const string KEY_URL = 'url';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string STORAGE_KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    protected const string STORAGE_KEY_NAME = 'name';

    protected const string STORAGE_KEY_URL = 'url';

    public function __construct(
        protected ProductBundleStorageClientInterface $productBundleStorageClient,
        protected ProductStorageClientInterface $productStorageClient
    ) {
    }

    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, mixed>
     */
    public function expand(
        array $productDetails,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): array {
        $productBundleStorageTransfer = $this->findFirstProductBundle(
            $this->extractProductConcreteIds($storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData()),
        );

        if ($productBundleStorageTransfer === null || $productBundleStorageTransfer->getBundledProducts()->count() === 0) {
            return $productDetails;
        }

        $productConcreteStorageDataById = $this->getProductConcreteStorageDataById(
            $productBundleStorageTransfer,
            $storefrontAssistantProductDetailsRequestTransfer->getLocaleNameOrFail(),
        );
        $bundledProducts = [];

        foreach ($productBundleStorageTransfer->getBundledProducts() as $productForProductBundleStorageTransfer) {
            $bundledProducts[] = $this->mapBundledProduct(
                $productForProductBundleStorageTransfer,
                $productConcreteStorageDataById[(int)$productForProductBundleStorageTransfer->getIdProductConcrete()] ?? [],
            );
        }

        $productDetails[static::KEY_BUNDLED_PRODUCTS] = $bundledProducts;

        return $productDetails;
    }

    /**
     * @param list<int> $productConcreteIds
     */
    protected function findFirstProductBundle(array $productConcreteIds): ?ProductBundleStorageTransfer
    {
        if ($productConcreteIds === []) {
            return null;
        }

        $productBundleStorageTransfers = $this->productBundleStorageClient->getProductBundles(
            (new ProductBundleStorageCriteriaTransfer())->setProductConcreteIds($productConcreteIds),
        );

        foreach ($productConcreteIds as $idProductConcrete) {
            if (isset($productBundleStorageTransfers[$idProductConcrete])) {
                return $productBundleStorageTransfers[$idProductConcrete];
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getProductConcreteStorageDataById(ProductBundleStorageTransfer $productBundleStorageTransfer, string $localeName): array
    {
        $bundledProductConcreteIds = [];

        foreach ($productBundleStorageTransfer->getBundledProducts() as $productForProductBundleStorageTransfer) {
            $bundledProductConcreteIds[] = (int)$productForProductBundleStorageTransfer->getIdProductConcrete();
        }

        $productConcreteStorageDataById = [];

        foreach ($this->productStorageClient->getBulkProductConcreteStorageData($bundledProductConcreteIds, $localeName) as $productConcreteStorageData) {
            if (is_array($productConcreteStorageData) && isset($productConcreteStorageData[static::STORAGE_KEY_ID_PRODUCT_CONCRETE])) {
                $productConcreteStorageDataById[(int)$productConcreteStorageData[static::STORAGE_KEY_ID_PRODUCT_CONCRETE]] = $productConcreteStorageData;
            }
        }

        return $productConcreteStorageDataById;
    }

    /**
     * @param array<string, mixed> $productConcreteStorageData
     *
     * @return array<string, mixed>
     */
    protected function mapBundledProduct(
        ProductForProductBundleStorageTransfer $productForProductBundleStorageTransfer,
        array $productConcreteStorageData
    ): array {
        return array_filter([
            static::KEY_SKU => (string)$productForProductBundleStorageTransfer->getSku(),
            static::KEY_NAME => trim((string)($productConcreteStorageData[static::STORAGE_KEY_NAME] ?? $productForProductBundleStorageTransfer->getName())),
            static::KEY_QUANTITY => (int)$productForProductBundleStorageTransfer->getQuantity(),
            static::KEY_URL => trim((string)($productConcreteStorageData[static::STORAGE_KEY_URL] ?? $productForProductBundleStorageTransfer->getUrl())),
        ], static fn (string|int $value): bool => $value !== '');
    }

    /**
     * @param array<mixed> $productAbstractStorageData
     *
     * @return list<int>
     */
    protected function extractProductConcreteIds(array $productAbstractStorageData): array
    {
        $productConcreteIds = $productAbstractStorageData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($productConcreteIds)) {
            return [];
        }

        $validProductConcreteIds = [];

        foreach ($productConcreteIds as $idProductConcrete) {
            if (is_numeric($idProductConcrete) && (int)$idProductConcrete > 0) {
                $validProductConcreteIds[] = (int)$idProductConcrete;
            }
        }

        return array_values(array_unique($validProductConcreteIds));
    }
}
