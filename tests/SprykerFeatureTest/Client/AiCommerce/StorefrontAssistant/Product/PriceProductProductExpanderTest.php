<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Product;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\MoneyTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductTransfer;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Money\MoneyClientInterface;
use Spryker\Client\Price\PriceClientInterface;
use Spryker\Client\PriceProduct\PriceProductClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\PriceProductProductExpander;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Product
 * @group PriceProductProductExpanderTest
 */
class PriceProductProductExpanderTest extends Unit
{
    protected const int ID_PRODUCT_ABSTRACT = 10;

    protected const int ID_PRODUCT_CONCRETE = 101;

    protected const string CONCRETE_SKU = '010_1';

    protected const string PRODUCT_OFFER_REFERENCE = 'offer-1';

    protected const int PRICE_ABSTRACT = 19200;

    protected const int PRICE_DEFAULT_OFFER = 58297;

    protected const string STORE_NAME = 'DE';

    protected const string LOCALE_NAME = 'en_US';

    protected const string CURRENCY_CODE = 'EUR';

    protected const string PRICE_MODE = 'GROSS_MODE';

    public function testGivenAProductWithSeveralSelectableVariantsWhenExpandedThenTheAbstractPriceIsResolvedWithoutAnOffer(): void
    {
        // Arrange
        $resolvedPriceProductFilterTransfers = [];
        $priceProductProductExpander = $this->createPriceProductProductExpander(
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getPriceProductAbstractTransfers' => [],
                'getResolvedCurrentProductPriceTransfer' => Expected::never(),
            ]),
            Stub::makeEmpty(ProductOfferStorageClientInterface::class, [
                'findProductConcreteDefaultProductOffer' => Expected::never(),
            ]),
            Stub::makeEmpty(PriceProductClientInterface::class, [
                'resolveProductPriceTransferByPriceProductFilter' => function (array $priceProductTransfers, PriceProductFilterTransfer $priceProductFilterTransfer) use (&$resolvedPriceProductFilterTransfers): CurrentProductPriceTransfer {
                    $resolvedPriceProductFilterTransfers[] = $priceProductFilterTransfer;

                    return (new CurrentProductPriceTransfer())->setPrice(static::PRICE_ABSTRACT);
                },
            ]),
        );
        $storefrontAssistantProductTransfer = $this->createProduct()
            ->setVariantCount(3)
            ->setVariants(['power' => ['45 kW', '60 kW', '80 kW']]);

        // Act
        $priceProductProductExpander->expand($this->createCollection($storefrontAssistantProductTransfer), static::LOCALE_NAME, static::STORE_NAME);

        // Assert
        $this->assertSame(static::PRICE_ABSTRACT, $storefrontAssistantProductTransfer->getPrice());
        $this->assertCount(1, $resolvedPriceProductFilterTransfers);
        $this->assertNull($resolvedPriceProductFilterTransfers[0]->getIdProduct());
        $this->assertNull($resolvedPriceProductFilterTransfers[0]->getProductOfferReference());
        $this->assertSame(static::ID_PRODUCT_ABSTRACT, $resolvedPriceProductFilterTransfers[0]->getIdProductAbstract());
    }

    public function testGivenAProductWithOneVariantWhenExpandedThenTheConcretePriceOfItsDefaultOfferIsResolved(): void
    {
        // Arrange
        $priceProductProductExpander = $this->createPriceProductProductExpander(
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getPriceProductAbstractTransfers' => Expected::never(),
                'getResolvedCurrentProductPriceTransfer' => static fn (PriceProductFilterTransfer $priceProductFilterTransfer): CurrentProductPriceTransfer => (new CurrentProductPriceTransfer())
                    ->setPrice($priceProductFilterTransfer->getIdProduct() === static::ID_PRODUCT_CONCRETE && $priceProductFilterTransfer->getProductOfferReference() === static::PRODUCT_OFFER_REFERENCE ? static::PRICE_DEFAULT_OFFER : null),
            ]),
            Stub::makeEmpty(ProductOfferStorageClientInterface::class, [
                'findProductConcreteDefaultProductOffer' => static::PRODUCT_OFFER_REFERENCE,
            ]),
            Stub::makeEmpty(PriceProductClientInterface::class, [
                'resolveProductPriceTransferByPriceProductFilter' => Expected::never(),
            ]),
        );
        $storefrontAssistantProductTransfer = $this->createProduct();

        // Act
        $priceProductProductExpander->expand($this->createCollection($storefrontAssistantProductTransfer), static::LOCALE_NAME, static::STORE_NAME);

        // Assert
        $this->assertSame(static::PRICE_DEFAULT_OFFER, $storefrontAssistantProductTransfer->getPrice());
    }

    public function testGivenSeveralVariantsWithoutVariantAttributesWhenExpandedThenTheFirstVariantPriceIsResolvedAsOnTheProductDetailPage(): void
    {
        // Arrange
        $priceProductProductExpander = $this->createPriceProductProductExpander(
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getPriceProductAbstractTransfers' => Expected::never(),
                'getResolvedCurrentProductPriceTransfer' => (new CurrentProductPriceTransfer())->setPrice(static::PRICE_DEFAULT_OFFER),
            ]),
            Stub::makeEmpty(ProductOfferStorageClientInterface::class),
            Stub::makeEmpty(PriceProductClientInterface::class, [
                'resolveProductPriceTransferByPriceProductFilter' => Expected::never(),
            ]),
        );
        $storefrontAssistantProductTransfer = $this->createProduct()->setVariantCount(2);

        // Act
        $priceProductProductExpander->expand($this->createCollection($storefrontAssistantProductTransfer), static::LOCALE_NAME, static::STORE_NAME);

        // Assert
        $this->assertSame(static::PRICE_DEFAULT_OFFER, $storefrontAssistantProductTransfer->getPrice());
    }

    protected function createPriceProductProductExpander(
        PriceProductStorageClientInterface $priceProductStorageClient,
        ProductOfferStorageClientInterface $productOfferStorageClient,
        PriceProductClientInterface $priceProductClient
    ): PriceProductProductExpander {
        return new PriceProductProductExpander(
            $priceProductStorageClient,
            $productOfferStorageClient,
            Stub::makeEmpty(MoneyClientInterface::class, [
                'formatWithSymbol' => static fn (MoneyTransfer $moneyTransfer): string => sprintf('€%s', $moneyTransfer->getAmount()),
            ]),
            Stub::makeEmpty(PriceClientInterface::class, [
                'getCurrentPriceMode' => static::PRICE_MODE,
            ]),
            Stub::makeEmpty(CurrencyClientInterface::class, [
                'getCurrent' => (new CurrencyTransfer())->setCode(static::CURRENCY_CODE),
            ]),
            $priceProductClient,
        );
    }

    protected function createProduct(): StorefrontAssistantProductTransfer
    {
        return (new StorefrontAssistantProductTransfer())
            ->setIdProductAbstract(static::ID_PRODUCT_ABSTRACT)
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->setConcreteSku(static::CONCRETE_SKU);
    }

    protected function createCollection(StorefrontAssistantProductTransfer $storefrontAssistantProductTransfer): StorefrontAssistantProductCollectionTransfer
    {
        return (new StorefrontAssistantProductCollectionTransfer())->addStorefrontAssistantProduct($storefrontAssistantProductTransfer);
    }
}
