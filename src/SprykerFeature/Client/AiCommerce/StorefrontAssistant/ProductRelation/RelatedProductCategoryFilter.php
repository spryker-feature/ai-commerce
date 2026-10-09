<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation;

use Spryker\Client\ProductCategoryStorage\ProductCategoryStorageClientInterface;

class RelatedProductCategoryFilter implements RelatedProductCategoryFilterInterface
{
    public function __construct(protected ProductCategoryStorageClientInterface $productCategoryStorageClient)
    {
    }

    /**
     * @param list<int> $relatedProductAbstractIds
     *
     * @return list<int>
     */
    public function filterProductAbstractIdsSharingCategory(
        int $idProductAbstract,
        array $relatedProductAbstractIds,
        string $storeName,
        string $localeName
    ): array {
        if ($relatedProductAbstractIds === []) {
            return [];
        }

        $categoryNodeIdsByIdProductAbstract = $this->getCategoryNodeIdsByIdProductAbstract(
            array_values(array_unique([$idProductAbstract, ...$relatedProductAbstractIds])),
            $storeName,
            $localeName,
        );
        $viewedCategoryNodeIds = $categoryNodeIdsByIdProductAbstract[$idProductAbstract] ?? [];

        if ($viewedCategoryNodeIds === []) {
            return [];
        }

        return array_values(array_filter(
            $relatedProductAbstractIds,
            static fn (int $relatedIdProductAbstract): bool => array_intersect_key(
                $categoryNodeIdsByIdProductAbstract[$relatedIdProductAbstract] ?? [],
                $viewedCategoryNodeIds,
            ) !== [],
        ));
    }

    /**
     * @param list<int> $productAbstractIds
     *
     * @return array<int, array<int, true>>
     */
    protected function getCategoryNodeIdsByIdProductAbstract(array $productAbstractIds, string $storeName, string $localeName): array
    {
        $categoryNodeIdsByIdProductAbstract = [];

        foreach ($this->productCategoryStorageClient->findBulkProductAbstractCategory($productAbstractIds, $localeName, $storeName) as $productAbstractCategoryStorageTransfer) {
            $idProductAbstract = (int)$productAbstractCategoryStorageTransfer->getIdProductAbstract();

            foreach ($productAbstractCategoryStorageTransfer->getCategories() as $productCategoryStorageTransfer) {
                if ($productCategoryStorageTransfer->getCategoryNodeId() !== null) {
                    $categoryNodeIdsByIdProductAbstract[$idProductAbstract][$productCategoryStorageTransfer->getCategoryNodeId()] = true;
                }
            }
        }

        return $categoryNodeIdsByIdProductAbstract;
    }
}
