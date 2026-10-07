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
