<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\MoneyTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductTransfer;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Money\MoneyClientInterface;
use Spryker\Client\Price\PriceClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class PriceProductProductExpander implements ProductExpanderInterface
{
    protected const int DEFAULT_QUANTITY = 1;

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string PRICE_TYPE_ORIGINAL = 'ORIGINAL';

    public function __construct(
        protected PriceProductStorageClientInterface $priceProductStorageClient,
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductOfferStorageClientInterface $productOfferStorageClient,
        protected MoneyClientInterface $moneyClient,
        protected PriceClientInterface $priceClient,
        protected CurrencyClientInterface $currencyClient
    ) {
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

        $currencyIsoCode = (string)$this->currencyClient->getCurrent()->getCode();
        $priceMode = (string)$this->priceClient->getCurrentPriceMode();

        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $this->expandProductWithCurrentPrice(
                $storefrontAssistantProductTransfer,
                $productStorageData[$storefrontAssistantProductTransfer->getIdProductAbstract()] ?? null,
                $storeName,
                $currencyIsoCode,
                $priceMode,
            );
        }

        return $storefrontAssistantProductCollectionTransfer;
    }

    protected function expandProductWithCurrentPrice(
        StorefrontAssistantProductTransfer $storefrontAssistantProductTransfer,
        mixed $productData,
        string $storeName,
        string $currencyIsoCode,
        string $priceMode
    ): void {
        $idProductAbstract = $storefrontAssistantProductTransfer->getIdProductAbstract();

        if ($idProductAbstract === null || $idProductAbstract <= 0) {
            return;
        }

        $productConcrete = $this->findFirstProductConcrete($productData);

        if ($productConcrete === null) {
            return;
        }

        [$idProductConcrete, $concreteSku] = $productConcrete;

        $priceProductFilterTransfer = (new PriceProductFilterTransfer())
            ->setIdProductAbstract($idProductAbstract)
            ->setIdProduct($idProductConcrete)
            ->setQuantity(static::DEFAULT_QUANTITY)
            ->setStoreName($storeName)
            ->setCurrencyIsoCode($currencyIsoCode)
            ->setPriceMode($priceMode)
            ->setProductOfferReference($this->findDefaultProductOfferReference($concreteSku));

        $currentProductPriceTransfer = $this->priceProductStorageClient
            ->getResolvedCurrentProductPriceTransfer($priceProductFilterTransfer);

        $storefrontAssistantProductTransfer
            ->setCurrentProductPrice($currentProductPriceTransfer)
            ->setCurrencyIsoCode($currencyIsoCode)
            ->setPriceMode($priceMode);

        $price = $currentProductPriceTransfer->getPrice();

        if ($price === null) {
            return;
        }

        $storefrontAssistantProductTransfer
            ->setPrice($price)
            ->setPriceFormatted($this->formatAmount($price, $currencyIsoCode));

        $this->expandProductWithOriginalPrice($storefrontAssistantProductTransfer, $currentProductPriceTransfer, $price, $currencyIsoCode);
    }

    protected function expandProductWithOriginalPrice(
        StorefrontAssistantProductTransfer $storefrontAssistantProductTransfer,
        CurrentProductPriceTransfer $currentProductPriceTransfer,
        int $price,
        string $currencyIsoCode
    ): void {
        $originalPrice = $currentProductPriceTransfer->getPrices()[static::PRICE_TYPE_ORIGINAL] ?? null;

        if (!is_int($originalPrice) || $originalPrice <= $price) {
            return;
        }

        $storefrontAssistantProductTransfer
            ->setOriginalPrice($originalPrice)
            ->setOriginalPriceFormatted($this->formatAmount($originalPrice, $currencyIsoCode));
    }

    protected function findDefaultProductOfferReference(string $concreteSku): ?string
    {
        if ($concreteSku === '') {
            return null;
        }

        return $this->productOfferStorageClient->findProductConcreteDefaultProductOffer(
            (new ProductOfferStorageCriteriaTransfer())->setProductConcreteSkus([$concreteSku]),
        );
    }

    protected function formatAmount(int $amount, string $currencyIsoCode): string
    {
        return $this->moneyClient->formatWithSymbol(
            (new MoneyTransfer())
                ->setAmount((string)$amount)
                ->setCurrency($this->currencyClient->getCurrent()),
        );
    }

    /**
     * @return array{0: int, 1: string}|null
     */
    protected function findFirstProductConcrete(mixed $productData): ?array
    {
        if (!is_array($productData)) {
            return null;
        }

        $productConcreteIds = $productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($productConcreteIds)) {
            return null;
        }

        foreach ($productConcreteIds as $concreteSku => $idProductConcrete) {
            if (!is_bool($idProductConcrete) && is_numeric($idProductConcrete) && (int)$idProductConcrete > 0) {
                return [(int)$idProductConcrete, is_string($concreteSku) ? $concreteSku : ''];
            }
        }

        return null;
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
