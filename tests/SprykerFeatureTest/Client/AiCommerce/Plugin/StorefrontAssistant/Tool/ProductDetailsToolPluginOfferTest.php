<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group ProductDetailsToolPluginOfferTest
 */
class ProductDetailsToolPluginOfferTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string PARAMETER_SKU = 'sku';

    protected const string KEY_OFFERS = 'offers';

    protected const string KEY_LOWEST_OFFER_MERCHANT_NAME = 'lowestOfferMerchantName';

    protected const string KEY_SELLER_PRICES = 'sellerPrices';

    protected const string KEY_MERCHANT_NAME = 'merchantName';

    protected const string KEY_PRICE_FORMATTED = 'priceFormatted';

    protected const string KEY_IS_AVAILABLE = 'isAvailable';

    protected const string KEY_IS_DEFAULT = 'isDefault';

    protected const string KEY_PRODUCT_OFFER_REFERENCE = 'productOfferReference';

    protected const string KEY_PRODUCT_CONCRETE_SKU = 'productConcreteSku';

    protected const string STORAGE_KEY_MERCHANT_REFERENCE = 'merchant_reference';

    protected const string ABSTRACT_SKU = '004';

    protected const string CONCRETE_SKU = '004_30663302';

    protected const int ID_PRODUCT_CONCRETE = 301;

    protected const string CONCRETE_SKU_SECOND = '004_30663303';

    protected const int ID_PRODUCT_CONCRETE_SECOND = 302;

    protected const string MERCHANT_REFERENCE_SHOP = 'MER000001';

    protected const string MERCHANT_NAME_SHOP = 'Spryker';

    protected const string MERCHANT_NAME_BUDGET_CAMERAS = 'Budget Cameras';

    protected const string MERCHANT_NAME_VIDEO_KING = 'Video King';

    protected const string OFFER_REFERENCE_BUDGET_CAMERAS = 'offer52';

    protected const string OFFER_REFERENCE_VIDEO_KING = 'offer11';

    protected const int PRICE_SHOP = 7000;

    protected const int PRICE_SHOP_SECOND = 7500;

    protected const int PRICE_BUDGET_CAMERAS = 6300;

    protected const int PRICE_VIDEO_KING = 6650;

    protected AiCommerceClientTester $tester;

    public function testGivenTwoMerchantOffersWhenDetailsRequestedThenBothAreListedLowestFirstWithMerchantNames(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCamera($idProductAbstract, static::MERCHANT_REFERENCE_SHOP);
        $this->tester->haveProductStockStorageClientsReturning($this->tester->createProductAbstractAvailability(static::ABSTRACT_SKU, [static::CONCRETE_SKU => 5]));
        $this->tester->havePricePermission(true);
        $this->tester->haveMerchantProductSoldBy(static::MERCHANT_NAME_SHOP, static::PRICE_SHOP);
        $this->tester->haveProductOfferStorageClientReturning([
            $this->tester->createProductOfferStorage(static::OFFER_REFERENCE_VIDEO_KING, static::CONCRETE_SKU, static::MERCHANT_NAME_VIDEO_KING, static::PRICE_VIDEO_KING, 4.0),
            $this->tester->createProductOfferStorage(static::OFFER_REFERENCE_BUDGET_CAMERAS, static::CONCRETE_SKU, static::MERCHANT_NAME_BUDGET_CAMERAS, static::PRICE_BUDGET_CAMERAS, 0.0, true)->setIsDefault(true),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $offers = $productDetails[static::KEY_OFFERS];
        $this->assertSame([static::MERCHANT_NAME_BUDGET_CAMERAS, static::MERCHANT_NAME_VIDEO_KING, static::MERCHANT_NAME_SHOP], array_column($offers, static::KEY_MERCHANT_NAME));
        $this->assertSame([
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_BUDGET_CAMERAS),
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_VIDEO_KING),
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_SHOP),
        ], array_column($offers, static::KEY_PRICE_FORMATTED));
        $this->assertSame([true, true, true], array_column($offers, static::KEY_IS_AVAILABLE));
        $this->assertTrue($offers[0][static::KEY_IS_DEFAULT]);
        $this->assertSame(static::OFFER_REFERENCE_BUDGET_CAMERAS, $offers[0][static::KEY_PRODUCT_OFFER_REFERENCE]);
        $this->assertArrayNotHasKey(static::KEY_PRODUCT_OFFER_REFERENCE, $offers[2]);
        $this->assertSame(static::MERCHANT_NAME_BUDGET_CAMERAS, $productDetails[static::KEY_LOWEST_OFFER_MERCHANT_NAME]);
        $this->assertSame(sprintf(
            '%s %s; %s %s; %s %s',
            static::MERCHANT_NAME_BUDGET_CAMERAS,
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_BUDGET_CAMERAS),
            static::MERCHANT_NAME_VIDEO_KING,
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_VIDEO_KING),
            static::MERCHANT_NAME_SHOP,
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_SHOP),
        ), $productDetails[static::KEY_SELLER_PRICES]);
    }

    public function testGivenOutOfStockOfferWhenDetailsRequestedThenItIsMarkedUnavailable(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCamera($idProductAbstract);
        $this->tester->havePricePermission(true);
        $this->tester->haveMerchantProductSoldBy(null, null);
        $this->tester->haveProductOfferStorageClientReturning([
            $this->tester->createProductOfferStorage(static::OFFER_REFERENCE_BUDGET_CAMERAS, static::CONCRETE_SKU, static::MERCHANT_NAME_BUDGET_CAMERAS, static::PRICE_BUDGET_CAMERAS, 0.0, false),
            $this->tester->createProductOfferStorage(static::OFFER_REFERENCE_VIDEO_KING, static::CONCRETE_SKU, static::MERCHANT_NAME_VIDEO_KING, static::PRICE_VIDEO_KING, 3.0, false),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame(
            [static::MERCHANT_NAME_BUDGET_CAMERAS => false, static::MERCHANT_NAME_VIDEO_KING => true],
            array_column($productDetails[static::KEY_OFFERS], static::KEY_IS_AVAILABLE, static::KEY_MERCHANT_NAME),
        );
        $this->assertSame(static::MERCHANT_NAME_VIDEO_KING, $productDetails[static::KEY_LOWEST_OFFER_MERCHANT_NAME]);
        $this->assertSame(sprintf(
            '%s %s (out of stock); %s %s',
            static::MERCHANT_NAME_BUDGET_CAMERAS,
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_BUDGET_CAMERAS),
            static::MERCHANT_NAME_VIDEO_KING,
            $this->tester->formatAmountInCurrentCurrency(static::PRICE_VIDEO_KING),
        ), $productDetails[static::KEY_SELLER_PRICES]);
    }

    public function testGivenMerchantProductWithSeveralVariantsWhenDetailsRequestedBySkuThenOneMerchantOfferOfTheRequestedVariantIsListed(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCamera($idProductAbstract, static::MERCHANT_REFERENCE_SHOP, [
            static::CONCRETE_SKU => static::ID_PRODUCT_CONCRETE,
            static::CONCRETE_SKU_SECOND => static::ID_PRODUCT_CONCRETE_SECOND,
        ]);
        $this->tester->havePricePermission(true);
        $this->tester->haveMerchantProductSoldBy(static::MERCHANT_NAME_SHOP, null, [
            static::ID_PRODUCT_CONCRETE => static::PRICE_SHOP,
            static::ID_PRODUCT_CONCRETE_SECOND => static::PRICE_SHOP_SECOND,
        ]);
        $this->tester->haveProductOfferStorageClientReturning([]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([
            static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            static::PARAMETER_SKU => static::CONCRETE_SKU_SECOND,
        ]);

        // Assert
        $offers = $productDetails[static::KEY_OFFERS];
        $this->assertCount(1, $offers);
        $this->assertSame(static::MERCHANT_NAME_SHOP, $offers[0][static::KEY_MERCHANT_NAME]);
        $this->assertSame(static::CONCRETE_SKU_SECOND, $offers[0][static::KEY_PRODUCT_CONCRETE_SKU]);
        $this->assertSame($this->tester->formatAmountInCurrentCurrency(static::PRICE_SHOP_SECOND), $offers[0][static::KEY_PRICE_FORMATTED]);
    }

    public function testGivenMerchantProductWithSeveralVariantsWhenDetailsRequestedWithoutSkuThenOneMerchantOfferOfTheFirstVariantIsListed(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCamera($idProductAbstract, static::MERCHANT_REFERENCE_SHOP, [
            static::CONCRETE_SKU => static::ID_PRODUCT_CONCRETE,
            static::CONCRETE_SKU_SECOND => static::ID_PRODUCT_CONCRETE_SECOND,
        ]);
        $this->tester->havePricePermission(true);
        $this->tester->haveMerchantProductSoldBy(static::MERCHANT_NAME_SHOP, null, [
            static::ID_PRODUCT_CONCRETE => static::PRICE_SHOP,
            static::ID_PRODUCT_CONCRETE_SECOND => static::PRICE_SHOP_SECOND,
        ]);
        $this->tester->haveProductOfferStorageClientReturning([]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $offers = $productDetails[static::KEY_OFFERS];
        $this->assertCount(1, $offers);
        $this->assertSame(static::CONCRETE_SKU, $offers[0][static::KEY_PRODUCT_CONCRETE_SKU]);
        $this->assertSame($this->tester->formatAmountInCurrentCurrency(static::PRICE_SHOP), $offers[0][static::KEY_PRICE_FORMATTED]);
    }

    public function testGivenProductWithoutOffersWhenDetailsRequestedThenNoOffersSectionIsReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCamera($idProductAbstract);
        $this->tester->havePricePermission(true);
        $this->tester->haveMerchantProductSoldBy(null, null);
        $this->tester->haveProductOfferStorageClientReturning([]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_OFFERS, $productDetails);
        $this->assertArrayNotHasKey(static::KEY_LOWEST_OFFER_MERCHANT_NAME, $productDetails);
    }

    public function testGivenCustomerWithoutPricePermissionWhenDetailsRequestedThenSellersAreListedWithoutPrices(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCamera($idProductAbstract, static::MERCHANT_REFERENCE_SHOP);
        $this->tester->havePricePermission(false);
        $this->tester->haveMerchantProductSoldBy(static::MERCHANT_NAME_SHOP, static::PRICE_SHOP);
        $this->tester->haveProductOfferStorageClientReturning([
            $this->tester->createProductOfferStorage(static::OFFER_REFERENCE_BUDGET_CAMERAS, static::CONCRETE_SKU, static::MERCHANT_NAME_BUDGET_CAMERAS, static::PRICE_BUDGET_CAMERAS, 2.0),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertEqualsCanonicalizing([static::MERCHANT_NAME_SHOP, static::MERCHANT_NAME_BUDGET_CAMERAS], array_column($productDetails[static::KEY_OFFERS], static::KEY_MERCHANT_NAME));
        $this->assertSame([], array_column($productDetails[static::KEY_OFFERS], static::KEY_PRICE_FORMATTED));
        $this->assertArrayNotHasKey(static::KEY_LOWEST_OFFER_MERCHANT_NAME, $productDetails);
        $this->assertArrayNotHasKey(static::KEY_SELLER_PRICES, $productDetails);
    }

    /**
     * @param array<string, int> $productConcreteIdsBySku
     */
    protected function haveCamera(
        int $idProductAbstract,
        ?string $merchantReference = null,
        array $productConcreteIdsBySku = [self::CONCRETE_SKU => self::ID_PRODUCT_CONCRETE]
    ): void {
        $productAbstractStorageData = $this->tester->createProductAbstractStorageDataWithVariants(
            $idProductAbstract,
            static::ABSTRACT_SKU,
            ['brand' => 'Canon'],
            [],
            $productConcreteIdsBySku,
        );

        if ($merchantReference !== null) {
            $productAbstractStorageData[static::STORAGE_KEY_MERCHANT_REFERENCE] = $merchantReference;
        }

        $this->tester->haveProductStorageClientReturning([$idProductAbstract => $productAbstractStorageData]);
    }
}
