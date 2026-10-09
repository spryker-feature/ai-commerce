<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductSet;

use Generated\Shared\Transfer\ProductSetDataStorageTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductSetPageSearch\ProductSetPageSearchClientInterface;
use Spryker\Client\ProductSetStorage\ProductSetStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;

class ProductSetReader implements ProductSetReaderInterface
{
    public const int MAX_PRODUCT_SETS = 10;

    public const string RESULT_KEY_SETS = 'sets';

    public const string RESULT_KEY_ID_PRODUCT_SET = 'idProductSet';

    public const string RESULT_KEY_NAME = 'name';

    public const string RESULT_KEY_URL = 'url';

    public const string RESULT_KEY_ITEMS = 'items';

    public const string RESULT_KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    /**
     * @uses \Spryker\Client\ProductSetPageSearch\Plugin\Elasticsearch\ResultFormatter\ProductSetPageSearchListResultFormatterPlugin::NAME
     */
    protected const string SEARCH_RESULT_KEY_PRODUCT_SETS = 'productSets';

    protected const string STORAGE_KEY_NAME = 'name';

    protected const string STORAGE_KEY_URL = 'url';

    public function __construct(
        protected ProductSetPageSearchClientInterface $productSetPageSearchClient,
        protected ProductSetStorageClientInterface $productSetStorageClient,
        protected ProductStorageClientInterface $productStorageClient,
        protected StoreClientInterface $storeClient,
        protected LocaleClientInterface $localeClient
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductSets(): array
    {
        $productSetDataStorageTransfers = $this->findProductSetDataStorageTransfers($this->localeClient->getCurrentLocale());
        $productAbstractStorageDataById = $this->getProductAbstractStorageDataById($productSetDataStorageTransfers);
        $productSets = [];

        foreach ($productSetDataStorageTransfers as $productSetDataStorageTransfer) {
            $items = $this->buildItems($productSetDataStorageTransfer, $productAbstractStorageDataById);

            if ($items === []) {
                continue;
            }

            $productSets[] = [
                static::RESULT_KEY_ID_PRODUCT_SET => (int)$productSetDataStorageTransfer->getIdProductSet(),
                static::RESULT_KEY_NAME => (string)$productSetDataStorageTransfer->getName(),
                static::RESULT_KEY_URL => (string)$productSetDataStorageTransfer->getUrl(),
                static::RESULT_KEY_ITEMS => $items,
            ];
        }

        return [static::RESULT_KEY_SETS => $productSets];
    }

    /**
     * @return list<\Generated\Shared\Transfer\ProductSetDataStorageTransfer>
     */
    protected function findProductSetDataStorageTransfers(string $localeName): array
    {
        $productSetDataStorageTransfers = [];

        foreach ($this->findListedProductSetIds() as $idProductSet) {
            $productSetDataStorageTransfer = $this->productSetStorageClient->getProductSetByIdProductSet($idProductSet, $localeName);

            if ($productSetDataStorageTransfer === null || trim((string)$productSetDataStorageTransfer->getName()) === '' || trim((string)$productSetDataStorageTransfer->getUrl()) === '') {
                continue;
            }

            $productSetDataStorageTransfers[] = $productSetDataStorageTransfer;
        }

        return $productSetDataStorageTransfers;
    }

    /**
     * @return list<int>
     */
    protected function findListedProductSetIds(): array
    {
        $listedProductSetDataStorageTransfers = $this->productSetPageSearchClient->getProductSetList(static::MAX_PRODUCT_SETS)[static::SEARCH_RESULT_KEY_PRODUCT_SETS] ?? [];

        if (!is_array($listedProductSetDataStorageTransfers)) {
            return [];
        }

        $productSetIds = [];

        foreach ($listedProductSetDataStorageTransfers as $listedProductSetDataStorageTransfer) {
            if ($listedProductSetDataStorageTransfer instanceof ProductSetDataStorageTransfer && (int)$listedProductSetDataStorageTransfer->getIdProductSet() > 0) {
                $productSetIds[] = (int)$listedProductSetDataStorageTransfer->getIdProductSet();
            }
        }

        return array_values(array_unique($productSetIds));
    }

    /**
     * @param list<\Generated\Shared\Transfer\ProductSetDataStorageTransfer> $productSetDataStorageTransfers
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getProductAbstractStorageDataById(array $productSetDataStorageTransfers): array
    {
        $productAbstractIds = [];

        foreach ($productSetDataStorageTransfers as $productSetDataStorageTransfer) {
            foreach ($this->extractProductAbstractIds($productSetDataStorageTransfer) as $idProductAbstract) {
                $productAbstractIds[$idProductAbstract] = $idProductAbstract;
            }
        }

        if ($productAbstractIds === []) {
            return [];
        }

        return $this->productStorageClient->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
            array_values($productAbstractIds),
            $this->localeClient->getCurrentLocale(),
            (string)$this->storeClient->getCurrentStore()->getName(),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $productAbstractStorageDataById
     *
     * @return list<array<string, mixed>>
     */
    protected function buildItems(ProductSetDataStorageTransfer $productSetDataStorageTransfer, array $productAbstractStorageDataById): array
    {
        $items = [];

        foreach ($this->extractProductAbstractIds($productSetDataStorageTransfer) as $idProductAbstract) {
            $productAbstractStorageData = $productAbstractStorageDataById[$idProductAbstract] ?? null;
            $name = trim((string)($productAbstractStorageData[static::STORAGE_KEY_NAME] ?? ''));
            $url = trim((string)($productAbstractStorageData[static::STORAGE_KEY_URL] ?? ''));

            if ($name === '' || $url === '') {
                continue;
            }

            $items[] = [
                static::RESULT_KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract,
                static::RESULT_KEY_NAME => $name,
                static::RESULT_KEY_URL => $url,
            ];
        }

        return $items;
    }

    /**
     * @return list<int>
     */
    protected function extractProductAbstractIds(ProductSetDataStorageTransfer $productSetDataStorageTransfer): array
    {
        $productAbstractIds = [];

        foreach ($productSetDataStorageTransfer->getProductAbstractIds() as $idProductAbstract) {
            if (is_numeric($idProductAbstract) && (int)$idProductAbstract > 0) {
                $productAbstractIds[] = (int)$idProductAbstract;
            }
        }

        return array_values(array_unique($productAbstractIds));
    }
}
