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

class PriceProductProductExpander implements ProductExpanderInterface
{
    protected const int DEFAULT_QUANTITY = 1;

    protected const string PRICE_TYPE_ORIGINAL = 'ORIGINAL';

    public function __construct(
        protected PriceProductStorageClientInterface $priceProductStorageClient,
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
        $currencyIsoCode = (string)$this->currencyClient->getCurrent()->getCode();
        $priceMode = (string)$this->priceClient->getCurrentPriceMode();

        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $this->expandProductWithCurrentPrice(
                $storefrontAssistantProductTransfer,
                $storeName,
                $currencyIsoCode,
                $priceMode,
            );
        }

        return $storefrontAssistantProductCollectionTransfer;
    }

    protected function expandProductWithCurrentPrice(
        StorefrontAssistantProductTransfer $storefrontAssistantProductTransfer,
        string $storeName,
        string $currencyIsoCode,
        string $priceMode
    ): void {
        $idProductAbstract = $storefrontAssistantProductTransfer->getIdProductAbstract();

        $idProductConcrete = $storefrontAssistantProductTransfer->getIdProductConcrete();

        if ($idProductAbstract === null || $idProductAbstract <= 0 || $idProductConcrete === null || $idProductConcrete <= 0) {
            return;
        }

        $priceProductFilterTransfer = (new PriceProductFilterTransfer())
            ->setIdProductAbstract($idProductAbstract)
            ->setIdProduct($idProductConcrete)
            ->setQuantity(static::DEFAULT_QUANTITY)
            ->setStoreName($storeName)
            ->setCurrencyIsoCode($currencyIsoCode)
            ->setPriceMode($priceMode)
            ->setProductOfferReference($this->findDefaultProductOfferReference((string)$storefrontAssistantProductTransfer->getConcreteSku()));

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
}
