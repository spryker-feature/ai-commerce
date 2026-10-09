<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation;

use Generated\Shared\Transfer\ConcreteAlternativeProductConditionsTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductCriteriaTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Client\ProductAlternativeStorage\ProductAlternativeStorageClientInterface;
use Spryker\Client\ProductRelationStorage\ProductRelationStorageClientInterface;

class ProductRelationIdReader implements ProductRelationIdReaderInterface
{
    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    public function __construct(
        protected ProductRelationStorageClientInterface $productRelationStorageClient,
        protected ProductAlternativeStorageClientInterface $productAlternativeStorageClient
    ) {
    }

    /**
     * @param array<mixed> $productAbstractStorageData
     *
     * @return list<int>
     */
    public function getRelatedProductAbstractIds(
        string $relationType,
        int $idProductAbstract,
        array $productAbstractStorageData,
        string $storeName,
        string $localeName
    ): array {
        if (!in_array($relationType, ProductRelationReader::TYPES, true)) {
            return [];
        }

        $productAbstractIds = match ($relationType) {
            ProductRelationReader::TYPE_SIMILAR => $this->productRelationStorageClient->findRelatedAbstractProductIds($idProductAbstract, $storeName),
            ProductRelationReader::TYPE_ACCESSORIES => $this->findUpSellingProductAbstractIds($idProductAbstract, $storeName),
            ProductRelationReader::TYPE_ALTERNATIVES => $this->findAlternativeProductAbstractIds($productAbstractStorageData, $localeName),
        };

        return array_values(array_unique(array_map('intval', $productAbstractIds)));
    }

    /**
     * @return array<int>
     */
    protected function findUpSellingProductAbstractIds(int $idProductAbstract, string $storeName): array
    {
        $quoteTransfer = (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName($storeName))
            ->addItem((new ItemTransfer())->setIdProductAbstract($idProductAbstract));

        return $this->productRelationStorageClient->findUpSellingAbstractProductIds($quoteTransfer);
    }

    /**
     * @param array<mixed> $productAbstractStorageData
     *
     * @return list<int>
     */
    protected function findAlternativeProductAbstractIds(array $productAbstractStorageData, string $localeName): array
    {
        $concreteSkus = $this->extractConcreteSkus($productAbstractStorageData);

        if ($concreteSkus === []) {
            return [];
        }

        $concreteAlternativeProductCollectionTransfer = $this->productAlternativeStorageClient->getConcreteAlternativeProductCollection(
            (new ConcreteAlternativeProductCriteriaTransfer())->setConcreteAlternativeProductConditions(
                (new ConcreteAlternativeProductConditionsTransfer())
                    ->setSkus($concreteSkus)
                    ->setLocaleName($localeName),
            ),
        );

        $productAbstractIds = [];

        foreach ($concreteAlternativeProductCollectionTransfer->getConcreteAlternativeProducts() as $concreteAlternativeProductTransfer) {
            foreach ($concreteAlternativeProductTransfer->getAlternativeProducts() as $productViewTransfer) {
                if ($productViewTransfer->getIdProductAbstract() !== null) {
                    $productAbstractIds[] = $productViewTransfer->getIdProductAbstract();
                }
            }
        }

        return $productAbstractIds;
    }

    /**
     * @param array<mixed> $productAbstractStorageData
     *
     * @return list<string>
     */
    protected function extractConcreteSkus(array $productAbstractStorageData): array
    {
        $productConcreteIds = $productAbstractStorageData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($productConcreteIds)) {
            return [];
        }

        $concreteSkus = array_filter(
            array_map(static fn (int|string $concreteSku): string => trim((string)$concreteSku), array_keys($productConcreteIds)),
            static fn (string $concreteSku): bool => $concreteSku !== '',
        );

        return array_values(array_unique($concreteSkus));
    }
}
