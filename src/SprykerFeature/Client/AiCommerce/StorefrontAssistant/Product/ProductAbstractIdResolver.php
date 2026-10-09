<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class ProductAbstractIdResolver implements ProductAbstractIdResolverInterface
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    public function __construct(protected ProductStorageClientInterface $productStorageClient)
    {
    }

    public function resolveIdProductAbstractBySku(string $sku, string $localeName): ?int
    {
        $sku = trim($sku);

        if ($sku === '') {
            return null;
        }

        $productAbstractStorageData = $this->productStorageClient->findProductAbstractStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $sku,
            $localeName,
        );
        $idProductAbstract = $this->extractIdProductAbstract($productAbstractStorageData);

        if ($idProductAbstract !== null) {
            return $idProductAbstract;
        }

        return $this->resolveIdProductAbstractByConcreteSku($sku, $localeName);
    }

    protected function resolveIdProductAbstractByConcreteSku(string $sku, string $localeName): ?int
    {
        $idProductAbstract = $this->extractIdProductAbstract(
            $this->productStorageClient->findProductConcreteStorageDataByMapping(
                static::MAPPING_TYPE_SKU,
                $sku,
                $localeName,
            ),
        );

        if ($idProductAbstract === null || $this->productStorageClient->isProductAbstractRestricted($idProductAbstract)) {
            return null;
        }

        return $idProductAbstract;
    }

    /**
     * @param array<string, mixed>|null $productStorageData
     */
    protected function extractIdProductAbstract(?array $productStorageData): ?int
    {
        $idProductAbstract = $productStorageData[static::KEY_ID_PRODUCT_ABSTRACT] ?? null;

        if (!is_numeric($idProductAbstract) || (int)$idProductAbstract <= 0) {
            return null;
        }

        return (int)$idProductAbstract;
    }
}
