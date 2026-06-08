<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Finder;

use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class CatalogProductFinder implements CatalogProductFinderInterface
{
    protected const string KEY_PRODUCT_SUGGESTION_BY_TYPE = 'suggestionByType';

    protected const string KEY_PRODUCT_CONCRETE = 'product_concrete';

    protected const string KEY_PRODUCT_ABSTRACT = 'product_abstract';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const int KEY_FIRST_PRODUCT = 0;

    public function __construct(
        protected CatalogClientInterface $catalogClient,
        protected ProductStorageClientInterface $productStorageClient,
        protected LocaleClientInterface $localeClient,
    ) {
    }

    /**
     * @param array<string> $productNames
     *
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer|null>
     */
    public function findProductsByNames(array $productNames): array
    {
        $searchStrings = [];

        foreach (array_unique(array_filter($productNames)) as $productName) {
            $searchStrings[$productName] = $productName;
        }

        $multiSearchResults = $this->catalogClient->catalogSuggestMultiSearch($searchStrings);
        $results = [];

        foreach ($productNames as $productName) {
            $searchResult = $multiSearchResults[$productName] ?? [];
            $results[$productName] = $this->extractItemTransferFromSearchResult($searchResult);
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $searchResult
     */
    protected function extractItemTransferFromSearchResult(array $searchResult): ?ItemTransfer
    {
        $catalogProductConcreteData = $searchResult[static::KEY_PRODUCT_SUGGESTION_BY_TYPE][static::KEY_PRODUCT_CONCRETE][static::KEY_FIRST_PRODUCT] ?? null;

        if ($catalogProductConcreteData) {
            return (new ItemTransfer())->fromArray($catalogProductConcreteData, true);
        }

        return $this->findItemTransferByProductAbstract($searchResult);
    }

    /**
     * @param array<string, mixed> $searchResult
     */
    protected function findItemTransferByProductAbstract(array $searchResult): ?ItemTransfer
    {
        $abstractProductData = $searchResult[static::KEY_PRODUCT_SUGGESTION_BY_TYPE][static::KEY_PRODUCT_ABSTRACT][static::KEY_FIRST_PRODUCT] ?? null;

        if (!$abstractProductData) {
            return null;
        }

        $idProductAbstract = $abstractProductData[static::KEY_ID_PRODUCT_ABSTRACT] ?? null;

        if (!$idProductAbstract) {
            return null;
        }

        $localeName = $this->localeClient->getCurrentLocale();
        $abstractViewTransfers = $this->productStorageClient->getProductAbstractViewTransfers([(int)$idProductAbstract], $localeName);
        $abstractViewTransfer = $abstractViewTransfers[static::KEY_FIRST_PRODUCT] ?? null;

        if (!$abstractViewTransfer) {
            return null;
        }

        $attributeMap = $abstractViewTransfer->getAttributeMap();

        if (!$attributeMap) {
            return null;
        }

        $concreteIds = array_map(
            static fn (mixed $id): int => (int)$id,
            array_values($attributeMap->getProductConcreteIds()),
        );

        if (!$concreteIds) {
            return null;
        }

        $concreteViewTransfers = $this->productStorageClient->getProductConcreteViewTransfers([$concreteIds[static::KEY_FIRST_PRODUCT]], $localeName);
        $concreteViewTransfer = $concreteViewTransfers[static::KEY_FIRST_PRODUCT] ?? null;

        if (!$concreteViewTransfer) {
            return null;
        }

        return (new ItemTransfer())
            ->setSku($concreteViewTransfer->getSku())
            ->setName($concreteViewTransfer->getName());
    }
}
