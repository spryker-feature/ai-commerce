<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group ProductDetailsToolPluginVolumePriceTest
 */
class ProductDetailsToolPluginVolumePriceTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string PARAMETER_QUANTITY = 'quantity';

    protected const string KEY_VOLUME_PRICES = 'volumePrices';

    protected const string KEY_HAS_TIERS = 'hasTiers';

    protected const string KEY_TIERS = 'tiers';

    protected const string KEY_REQUESTED = 'requested';

    protected const string KEY_QUANTITY = 'quantity';

    protected const string KEY_UNIT_PRICE_FORMATTED = 'unitPriceFormatted';

    protected const string KEY_TOTAL_FORMATTED = 'totalFormatted';

    protected const string ABSTRACT_SKU = '091';

    protected const string CONCRETE_SKU = '091_25873091';

    protected const int ID_PRODUCT_CONCRETE = 401;

    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    protected const string PRICE_TYPE_DEFAULT = 'DEFAULT';

    protected const int PRICE_BASE = 19712;

    protected const int PRICE_TIER_FIVE = 165;

    protected const int PRICE_TIER_TEN = 158;

    protected const int PRICE_TIER_TWENTY = 152;

    protected const int QUANTITY_TWENTY = 20;

    protected const int PRICE_MERCHANT_PRODUCT = 7000;

    protected const int PRICE_DEFAULT_OFFER = 6300;

    protected const string DEFAULT_OFFER_REFERENCE = 'offer1';

    protected const string VOLUME_PRICE_DATA = '{"volume_prices":[{"quantity":10,"net_price":145,"gross_price":158},{"quantity":5,"net_price":150,"gross_price":165},{"quantity":20,"net_price":140,"gross_price":152}]}';

    protected AiCommerceClientTester $tester;

    public function testGivenVolumeTiersWhenDetailsRequestedForTwentyThenTierUnitPriceAndTotalAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->haveSmartWatch();
        $this->tester->havePricePermission(true);
        $this->tester->havePriceProductStorageClientReturningByQuantity([
            1 => $this->createCurrentProductPrice(static::PRICE_BASE, static::VOLUME_PRICE_DATA),
            static::QUANTITY_TWENTY => $this->createCurrentProductPrice(static::PRICE_TIER_TWENTY, static::VOLUME_PRICE_DATA),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([
            static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            static::PARAMETER_QUANTITY => static::QUANTITY_TWENTY,
        ]);

        // Assert
        $volumePrices = $productDetails[static::KEY_VOLUME_PRICES];
        $this->assertTrue($volumePrices[static::KEY_HAS_TIERS]);
        $this->assertSame([5, 10, 20], array_column($volumePrices[static::KEY_TIERS], static::KEY_QUANTITY));
        $this->assertSame($this->tester->formatAmountInCurrentCurrency(static::PRICE_TIER_FIVE), $volumePrices[static::KEY_TIERS][0][static::KEY_UNIT_PRICE_FORMATTED]);
        $this->assertSame($this->tester->formatAmountInCurrentCurrency(static::PRICE_TIER_TEN), $volumePrices[static::KEY_TIERS][1][static::KEY_UNIT_PRICE_FORMATTED]);
        $this->assertSame([
            static::KEY_QUANTITY => static::QUANTITY_TWENTY,
            static::KEY_UNIT_PRICE_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::PRICE_TIER_TWENTY),
            static::KEY_TOTAL_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::PRICE_TIER_TWENTY * static::QUANTITY_TWENTY),
        ], $volumePrices[static::KEY_REQUESTED]);
    }

    public function testGivenNoVolumeTiersWhenDetailsRequestedForTwentyThenSameUnitPriceAndTotalAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->haveSmartWatch();
        $this->tester->havePricePermission(true);
        $this->tester->havePriceProductStorageClientReturningByQuantity([
            1 => $this->createCurrentProductPrice(static::PRICE_BASE, null),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([
            static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            static::PARAMETER_QUANTITY => static::QUANTITY_TWENTY,
        ]);

        // Assert
        $this->assertSame([
            static::KEY_HAS_TIERS => false,
            static::KEY_REQUESTED => [
                static::KEY_QUANTITY => static::QUANTITY_TWENTY,
                static::KEY_UNIT_PRICE_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::PRICE_BASE),
                static::KEY_TOTAL_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::PRICE_BASE * static::QUANTITY_TWENTY),
            ],
        ], $productDetails[static::KEY_VOLUME_PRICES]);
    }

    public function testGivenDefaultOfferWhenDetailsRequestedForTwentyThenQuantityPriceUsesTheDefaultOfferPrice(): void
    {
        // Arrange
        $idProductAbstract = $this->haveSmartWatch();
        $this->tester->havePricePermission(true);
        $this->haveDefaultOffer();
        $this->tester->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE,
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getResolvedCurrentProductPriceTransfer' => fn (PriceProductFilterTransfer $priceProductFilterTransfer): CurrentProductPriceTransfer => $this->createCurrentProductPrice(
                    $priceProductFilterTransfer->getProductOfferReference() === static::DEFAULT_OFFER_REFERENCE ? static::PRICE_DEFAULT_OFFER : static::PRICE_MERCHANT_PRODUCT,
                    null,
                ),
            ]),
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([
            static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            static::PARAMETER_QUANTITY => static::QUANTITY_TWENTY,
        ]);

        // Assert
        $this->assertSame([
            static::KEY_QUANTITY => static::QUANTITY_TWENTY,
            static::KEY_UNIT_PRICE_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::PRICE_DEFAULT_OFFER),
            static::KEY_TOTAL_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::PRICE_DEFAULT_OFFER * static::QUANTITY_TWENTY),
        ], $productDetails[static::KEY_VOLUME_PRICES][static::KEY_REQUESTED]);
    }

    public function testGivenDefaultOfferWithoutTiersAndProductTiersWhenDetailsRequestedForTwentyThenProductTierPriceIsKept(): void
    {
        // Arrange
        $idProductAbstract = $this->haveSmartWatch();
        $this->tester->havePricePermission(true);
        $this->haveDefaultOffer();
        $this->tester->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE,
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getResolvedCurrentProductPriceTransfer' => fn (PriceProductFilterTransfer $priceProductFilterTransfer): CurrentProductPriceTransfer => match (true) {
                    $priceProductFilterTransfer->getProductOfferReference() === static::DEFAULT_OFFER_REFERENCE => $this->createCurrentProductPrice(static::PRICE_DEFAULT_OFFER, null),
                    $priceProductFilterTransfer->getQuantity() === static::QUANTITY_TWENTY => $this->createCurrentProductPrice(static::PRICE_TIER_TWENTY, static::VOLUME_PRICE_DATA),
                    default => $this->createCurrentProductPrice(static::PRICE_BASE, static::VOLUME_PRICE_DATA),
                },
            ]),
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([
            static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            static::PARAMETER_QUANTITY => static::QUANTITY_TWENTY,
        ]);

        // Assert
        $this->assertTrue($productDetails[static::KEY_VOLUME_PRICES][static::KEY_HAS_TIERS]);
        $this->assertSame(
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_TIER_TWENTY),
            $productDetails[static::KEY_VOLUME_PRICES][static::KEY_REQUESTED][static::KEY_UNIT_PRICE_FORMATTED],
        );
    }

    public function testGivenMalformedPriceDataWhenDetailsRequestedThenTiersAreAbsentAndNoErrorIsThrown(): void
    {
        // Arrange
        $idProductAbstract = $this->haveSmartWatch();
        $this->tester->havePricePermission(true);
        $this->tester->havePriceProductStorageClientReturningByQuantity([
            1 => $this->createCurrentProductPrice(static::PRICE_BASE, '{"volume_prices":[{"quantity":"many"},"broken"'),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_VOLUME_PRICES, $productDetails);
    }

    public function testGivenNoPricePermissionWhenDetailsRequestedForTwentyThenNoVolumePricesAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->haveSmartWatch();
        $this->tester->havePricePermission(false);
        $this->tester->havePriceProductStorageClientReturningByQuantity([
            1 => $this->createCurrentProductPrice(static::PRICE_BASE, static::VOLUME_PRICE_DATA),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([
            static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            static::PARAMETER_QUANTITY => static::QUANTITY_TWENTY,
        ]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_VOLUME_PRICES, $productDetails);
    }

    protected function haveDefaultOffer(): void
    {
        $productOfferStorageTransfer = $this->tester->createProductOfferStorage(static::DEFAULT_OFFER_REFERENCE, static::CONCRETE_SKU, 'Budget Cameras', static::PRICE_DEFAULT_OFFER, 5.0);
        $this->tester->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_OFFER_STORAGE,
            Stub::makeEmpty(ProductOfferStorageClientInterface::class, [
                'getProductOfferStoragesBySkus' => (new ProductOfferStorageCollectionTransfer())->addProductOffer($productOfferStorageTransfer),
                'findProductOfferStorageByReference' => $productOfferStorageTransfer,
                'findProductConcreteDefaultProductOffer' => static::DEFAULT_OFFER_REFERENCE,
            ]),
        );
    }

    protected function haveSmartWatch(): int
    {
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->tester->haveProductStorageClientReturning([
            $idProductAbstract => $this->tester->createProductAbstractStorageDataWithVariants(
                $idProductAbstract,
                static::ABSTRACT_SKU,
                ['brand' => 'Sony'],
                [],
                [static::CONCRETE_SKU => static::ID_PRODUCT_CONCRETE],
            ),
        ]);

        return $idProductAbstract;
    }

    protected function createCurrentProductPrice(int $price, ?string $priceData): CurrentProductPriceTransfer
    {
        return (new CurrentProductPriceTransfer())
            ->setPrice($price)
            ->setPriceMode(static::PRICE_MODE_GROSS)
            ->setPriceDataByPriceType([static::PRICE_TYPE_DEFAULT => $priceData]);
    }
}
