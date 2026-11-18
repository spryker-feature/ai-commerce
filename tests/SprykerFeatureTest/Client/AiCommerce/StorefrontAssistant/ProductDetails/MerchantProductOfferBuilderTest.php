<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\MerchantStorageTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\MerchantProductOfferBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductOfferDetailsExpander;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group ProductDetails
 * @group MerchantProductOfferBuilderTest
 */
class MerchantProductOfferBuilderTest extends Unit
{
    protected const string MERCHANT_REFERENCE = 'MER-1';

    public function testGivenNoMerchantReferenceWhenOffersBuiltThenNoOfferIsReturned(): void
    {
        // Arrange
        $requestTransfer = (new StorefrontAssistantProductDetailsRequestTransfer())
            ->setIdProductAbstract(1)
            ->setProductAbstractStorageData([]);

        // Act
        $offers = $this->createBuilder(null, 0)->buildMerchantProductOffers([], $requestTransfer, ['SKU-1' => 11], true);

        // Assert
        $this->assertSame([], $offers);
    }

    public function testGivenUnknownMerchantWhenOffersBuiltThenNoOfferIsReturned(): void
    {
        // Act
        $offers = $this->createBuilder(null, 0)->buildMerchantProductOffers([], $this->createRequestTransfer('SKU-1'), ['SKU-1' => 11], true);

        // Assert
        $this->assertSame([], $offers);
    }

    public function testGivenPriceVisibleWhenOffersBuiltThenOfferContainsFormattedPriceAndFallbackSku(): void
    {
        // Arrange
        $merchantStorageTransfer = (new MerchantStorageTransfer())->setName('Acme');

        // Act
        $offers = $this->createBuilder($merchantStorageTransfer, 1500)->buildMerchantProductOffers(
            [],
            $this->createRequestTransfer('UNKNOWN'),
            ['SKU-1' => 11, 'SKU-2' => 12],
            true,
        );

        // Assert
        $expectedOffer = [
            ProductOfferDetailsExpander::KEY_MERCHANT_NAME => 'Acme',
            ProductOfferDetailsExpander::KEY_MERCHANT_REFERENCE => static::MERCHANT_REFERENCE,
            ProductOfferDetailsExpander::KEY_PRODUCT_CONCRETE_SKU => 'SKU-1',
            ProductOfferDetailsExpander::KEY_PRICE_FORMATTED => 'F1500',
        ];
        $this->assertSame([[1500, $expectedOffer]], $offers);
    }

    public function testGivenPriceHiddenAndKnownSingleAvailabilityWhenOffersBuiltThenOfferHasNoPriceButAvailability(): void
    {
        // Arrange
        $merchantStorageTransfer = (new MerchantStorageTransfer())->setName('Acme');
        $productDetails = ['availability' => ['isKnown' => true, 'isAvailable' => true]];

        // Act
        $offers = $this->createBuilder($merchantStorageTransfer, 0)->buildMerchantProductOffers(
            $productDetails,
            $this->createRequestTransfer('SKU-1'),
            ['SKU-1' => 11],
            false,
        );

        // Assert
        $this->assertNull($offers[0][0]);
        $this->assertArrayNotHasKey(ProductOfferDetailsExpander::KEY_PRICE_FORMATTED, $offers[0][1]);
        $this->assertTrue($offers[0][1][ProductOfferDetailsExpander::KEY_IS_AVAILABLE]);
    }

    protected function createRequestTransfer(string $sku): StorefrontAssistantProductDetailsRequestTransfer
    {
        return (new StorefrontAssistantProductDetailsRequestTransfer())
            ->setIdProductAbstract(1)
            ->setSku($sku)
            ->setProductAbstractStorageData(['merchant_reference' => static::MERCHANT_REFERENCE]);
    }

    protected function createBuilder(?MerchantStorageTransfer $merchantStorageTransfer, int $price): MerchantProductOfferBuilder
    {
        $merchantStorageClient = $this->createMock(MerchantStorageClientInterface::class);
        $merchantStorageClient->method('findOne')->willReturn($merchantStorageTransfer);

        $priceProductStorageClient = $this->createMock(PriceProductStorageClientInterface::class);
        $priceProductStorageClient->method('getResolvedCurrentProductPriceTransfer')
            ->willReturn((new CurrentProductPriceTransfer())->setPrice($price));

        $priceFormatter = $this->createMock(StorefrontAssistantPriceFormatterInterface::class);
        $priceFormatter->method('formatAmount')->willReturnCallback(static fn (int $amount): string => 'F' . $amount);

        return new MerchantProductOfferBuilder($priceProductStorageClient, $merchantStorageClient, $priceFormatter);
    }
}
