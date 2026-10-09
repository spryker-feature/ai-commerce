<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAbstractAvailabilityTransfer;
use Generated\Shared\Transfer\ProductAbstractOptionStorageTransfer;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group ProductDetailsToolPluginTest
 */
class ProductDetailsToolPluginTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string PARAMETER_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string KEY_NAME = 'name';

    protected const string KEY_ATTRIBUTES = 'attributes';

    protected const string KEY_VARIANTS = 'variants';

    protected const string KEY_VARIANT_COUNT = 'variantCount';

    protected const string KEY_SUPER_ATTRIBUTES = 'superAttributes';

    protected const string KEY_CONCRETES = 'concretes';

    protected const string KEY_CONCRETE_COUNT = 'concreteCount';

    protected const string KEY_SKU = 'sku';

    protected const string KEY_ERROR = 'error';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string STORAGE_KEY_SUPER_ATTRIBUTES = 'super_attributes';

    protected const string ABSTRACT_SKU = '004';

    protected const string CONCRETE_SKU_FIRST = '145_29885470';

    protected const string CONCRETE_SKU_SECOND = '145_29885471';

    protected const string CONCRETE_SKU_THIRD = '145_29885473';

    protected const string SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY = 'processor_frequency';

    protected const string PROCESSOR_FREQUENCY_FIRST = '2 GHz';

    protected const string PROCESSOR_FREQUENCY_SECOND = '1.5 GHz';

    protected const string PROCESSOR_FREQUENCY_THIRD = '2.3 GHz';

    protected const string UNKNOWN_SKU = 'no-such-sku-for-product-details';

    protected const string KEY_AVAILABILITY = 'availability';

    protected const string KEY_IS_KNOWN = 'isKnown';

    protected const string KEY_IS_AVAILABLE = 'isAvailable';

    protected const string KEY_AVAILABLE_VARIANT_COUNT = 'availableVariantCount';

    protected const string KEY_UNAVAILABLE_VARIANT_COUNT = 'unavailableVariantCount';

    protected const string KEY_DISCONTINUED = 'discontinued';

    protected const string KEY_IS_DISCONTINUED = 'isDiscontinued';

    protected const string KEY_NOTE = 'note';

    protected const string KEY_DISCONTINUED_VARIANT_SKUS = 'discontinuedVariantSkus';

    protected const string KEY_HAS_ALTERNATIVES = 'hasAlternatives';

    protected const string KEY_DISCONTINUED_NOTE = 'discontinuedNote';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string CONCRETE_SKU_CAMERA = '004_30663302';

    protected const string DISCONTINUED_NOTE = 'Replaced by the next model.';

    protected const string KEY_OPTIONS = 'options';

    protected const string KEY_RATING = 'rating';

    protected const string KEY_REVIEW_COUNT = 'reviewCount';

    protected const string KEY_BUNDLED_PRODUCTS = 'bundledProducts';

    protected const string KEY_QUANTITY = 'quantity';

    protected const string KEY_URL = 'url';

    protected const string KEY_GROUP = 'group';

    protected const string KEY_PRICE_FORMATTED = 'priceFormatted';

    protected const string OPTION_GROUP_INSURANCE = 'product.option.group.name.insurance';

    protected const string OPTION_GROUP_GIFT_WRAPPING = 'product.option.group.name.gift_wrapping';

    protected const string OPTION_VALUE_INSURANCE = 'product.option.insurance';

    protected const string OPTION_VALUE_GIFT_WRAPPING = 'product.option.gift_wrapping';

    protected const string OPTION_SKU_INSURANCE = 'OP_insurance';

    protected const string OPTION_SKU_GIFT_WRAPPING = 'OP_gift_wrapping';

    protected const int OPTION_PRICE_INSURANCE = 9000;

    protected const int OPTION_PRICE_GIFT_WRAPPING = 400;

    /**
     * @var array<string, string>
     */
    protected const array OPTION_TRANSLATIONS = [
        self::OPTION_GROUP_INSURANCE => 'Insurance',
        self::OPTION_GROUP_GIFT_WRAPPING => 'Gift wrapping',
        self::OPTION_VALUE_INSURANCE => 'Two-year insurance',
        self::OPTION_VALUE_GIFT_WRAPPING => 'Gift wrap',
    ];

    /**
     * @var array<string, string>
     */
    protected const array CAMERA_ATTRIBUTES = [
        'optical_zoom' => '8 x',
        'combined_zoom' => '32 x',
        'display' => 'LCD',
        'hdmi' => 'no',
        'brand' => 'Canon',
        'color' => 'Silver',
        'sensor_type' => 'CCD',
        'image_stabilizer' => 'digital',
        'video_resolution' => '1280 x 720 pixels',
        'battery_type' => 'NB-11LH',
        'weight' => '135 g',
    ];

    protected AiCommerceClientTester $tester;

    public function testGivenProductWithAttributesWhenDetailsRequestedByIdThenAllLocalizedAttributesAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->tester->haveProductStorageClientReturning([
            $idProductAbstract => $this->tester->createProductAbstractStorageDataWithVariants($idProductAbstract, static::ABSTRACT_SKU, static::CAMERA_ATTRIBUTES + ['empty' => ' ']),
        ]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame($idProductAbstract, $productDetails[static::KEY_ID_PRODUCT_ABSTRACT]);
        $this->assertSame(static::CAMERA_ATTRIBUTES, $productDetails[static::KEY_ATTRIBUTES]);
    }

    public function testGivenProductWithSuperAttributeWhenDetailsRequestedThenEveryVariantValueIsListed(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveProductWithThreeProcessorVariants($idProductAbstract);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $variants = $productDetails[static::KEY_VARIANTS];
        $this->assertSame([static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => [static::PROCESSOR_FREQUENCY_FIRST, static::PROCESSOR_FREQUENCY_SECOND, static::PROCESSOR_FREQUENCY_THIRD]], $variants[static::KEY_SUPER_ATTRIBUTES]);
        $this->assertSame(3, $variants[static::KEY_CONCRETE_COUNT]);
        $this->assertSame(
            [static::KEY_SKU => static::CONCRETE_SKU_SECOND, static::KEY_ATTRIBUTES => [static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => static::PROCESSOR_FREQUENCY_SECOND]],
            $variants[static::KEY_CONCRETES][1],
        );
        $this->assertArrayNotHasKey(static::KEY_VARIANT_COUNT, $productDetails);
    }

    public function testGivenAbstractSkuWhenDetailsRequestedThenProductIsResolvedBySkuMapping(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->tester->haveProductStorageClientReturning(
            [$idProductAbstract => $this->tester->createProductAbstractStorageDataWithVariants($idProductAbstract, static::ABSTRACT_SKU, static::CAMERA_ATTRIBUTES)],
            [static::ABSTRACT_SKU => $idProductAbstract],
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_SKU => ' ' . static::ABSTRACT_SKU]);

        // Assert
        $this->assertSame($idProductAbstract, $productDetails[static::KEY_ID_PRODUCT_ABSTRACT]);
        $this->assertSame(static::ABSTRACT_SKU, $productDetails[static::KEY_SKU]);
    }

    public function testGivenConcreteSkuWhenDetailsRequestedThenOwningAbstractIsResolved(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveProductWithThreeProcessorVariants($idProductAbstract, [static::CONCRETE_SKU_THIRD => $idProductAbstract]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_SKU => static::CONCRETE_SKU_THIRD]);

        // Assert
        $this->assertSame($idProductAbstract, $productDetails[static::KEY_ID_PRODUCT_ABSTRACT]);
        $this->assertContains(static::CONCRETE_SKU_THIRD, array_column($productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES], static::KEY_SKU));
    }

    public function testGivenUnknownSkuWhenDetailsRequestedThenAnErrorIsReturned(): void
    {
        // Arrange
        $this->tester->haveProductStorageClientReturning([]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_SKU => static::UNKNOWN_SKU]);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($productDetails));
        $this->assertStringContainsString(static::UNKNOWN_SKU, $productDetails[static::KEY_ERROR]);
    }

    public function testGivenUnknownIdWhenDetailsRequestedThenAnErrorIsReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->tester->haveProductStorageClientReturning([]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($productDetails));
        $this->assertStringContainsString((string)$idProductAbstract, $productDetails[static::KEY_ERROR]);
    }

    public function testGivenNeitherIdNorSkuWhenDetailsRequestedThenAnErrorIsReturned(): void
    {
        // Arrange
        $this->tester->haveProductStorageClientReturning([]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => 0, static::PARAMETER_SKU => ' ']);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($productDetails));
    }

    public function testGivenPublishedProductWhenDetailsRequestedThenRealStorageResolvesIt(): void
    {
        // Arrange
        [$idProductAbstract, $productData] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => count($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? []) > 1
                && ($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_SUPER_ATTRIBUTES] ?? []) !== [],
            'with several concrete products',
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $attributeMap = $productData[static::STORAGE_KEY_ATTRIBUTE_MAP];
        $this->assertNotSame('', $productDetails[static::KEY_NAME]);
        $this->assertSame(array_keys($attributeMap[static::STORAGE_KEY_SUPER_ATTRIBUTES]), array_keys($productDetails[static::KEY_VARIANTS][static::KEY_SUPER_ATTRIBUTES]));
        $this->assertSame(array_keys($attributeMap[static::STORAGE_KEY_PRODUCT_CONCRETE_IDS]), array_column($productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES], static::KEY_SKU));
    }

    public function testGivenAvailableProductWhenDetailsRequestedThenAvailabilityIsTrue(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOneVariant($idProductAbstract);
        $this->tester->haveProductStockStorageClientsReturning($this->tester->createProductAbstractAvailability(static::ABSTRACT_SKU, [static::CONCRETE_SKU_CAMERA => 10]));

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([static::KEY_IS_KNOWN => true, static::KEY_IS_AVAILABLE => true], $productDetails[static::KEY_AVAILABILITY]);
        $this->assertTrue($productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES][0][static::KEY_IS_AVAILABLE]);
        $this->assertArrayNotHasKey(static::KEY_DISCONTINUED, $productDetails);
    }

    public function testGivenOneOutOfStockVariantWhenDetailsRequestedThenUnavailableAndAvailableVariantsAreNamed(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveProductWithThreeProcessorVariants($idProductAbstract);
        $this->tester->haveProductStockStorageClientsReturning($this->createProcessorVariantsAvailability([5, 0, 0], [static::CONCRETE_SKU_THIRD]));

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $concretes = $productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES];
        $this->assertSame([static::KEY_IS_KNOWN => true, static::KEY_IS_AVAILABLE => true, static::KEY_AVAILABLE_VARIANT_COUNT => 2, static::KEY_UNAVAILABLE_VARIANT_COUNT => 1], $productDetails[static::KEY_AVAILABILITY]);
        $this->assertSame([true, false, true], array_column($concretes, static::KEY_IS_AVAILABLE));
        $this->assertSame([static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => static::PROCESSOR_FREQUENCY_SECOND], $concretes[1][static::KEY_ATTRIBUTES]);
    }

    public function testGivenDiscontinuedProductWhenDetailsRequestedThenDiscontinuedFlagNoteAndAlternativesFlagAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveProductWithThreeProcessorVariants($idProductAbstract);
        $this->tester->haveProductStockStorageClientsReturning(
            $this->createProcessorVariantsAvailability([0, 0, 0], [static::CONCRETE_SKU_FIRST, static::CONCRETE_SKU_SECOND, static::CONCRETE_SKU_THIRD]),
            [static::CONCRETE_SKU_FIRST => static::DISCONTINUED_NOTE, static::CONCRETE_SKU_SECOND => '', static::CONCRETE_SKU_THIRD => ' '],
            [static::CONCRETE_SKU_SECOND => [185]],
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([static::KEY_IS_DISCONTINUED => true, static::KEY_NOTE => static::DISCONTINUED_NOTE, static::KEY_HAS_ALTERNATIVES => true], $productDetails[static::KEY_DISCONTINUED]);
        $this->assertSame([static::KEY_IS_KNOWN => true, static::KEY_IS_AVAILABLE => false, static::KEY_AVAILABLE_VARIANT_COUNT => 0, static::KEY_UNAVAILABLE_VARIANT_COUNT => 3], $productDetails[static::KEY_AVAILABILITY]);
        $this->assertSame(static::DISCONTINUED_NOTE, $productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES][0][static::KEY_DISCONTINUED_NOTE]);
        $this->assertStringContainsString('product_relations type alternatives', $productDetails[static::KEY_SUGGESTED_NEXT_ACTION]);
    }

    public function testGivenOneDiscontinuedVariantWhenDetailsRequestedThenOnlyThatVariantIsReportedDiscontinued(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveProductWithThreeProcessorVariants($idProductAbstract);
        $this->tester->haveProductStockStorageClientsReturning($this->createProcessorVariantsAvailability([0, 0, 0], [static::CONCRETE_SKU_FIRST, static::CONCRETE_SKU_SECOND, static::CONCRETE_SKU_THIRD]), [static::CONCRETE_SKU_THIRD => '']);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([static::KEY_IS_DISCONTINUED => false, static::KEY_DISCONTINUED_VARIANT_SKUS => [static::CONCRETE_SKU_THIRD], static::KEY_HAS_ALTERNATIVES => false], $productDetails[static::KEY_DISCONTINUED]);
        $this->assertSame([true, true, false], array_column($productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES], static::KEY_IS_AVAILABLE));
        $this->assertSame([false, true], [isset($productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES][0][static::KEY_IS_DISCONTINUED]), $productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES][2][static::KEY_IS_DISCONTINUED]]);
        $this->assertArrayNotHasKey(static::KEY_SUGGESTED_NEXT_ACTION, $productDetails);
    }

    public function testGivenDiscontinuedProductWithoutAlternativesWhenDetailsRequestedThenNoNextActionIsSuggested(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveProductWithThreeProcessorVariants($idProductAbstract);
        $this->tester->haveProductStockStorageClientsReturning(
            $this->createProcessorVariantsAvailability([0, 0, 0], [static::CONCRETE_SKU_FIRST, static::CONCRETE_SKU_SECOND, static::CONCRETE_SKU_THIRD]),
            [static::CONCRETE_SKU_FIRST => '', static::CONCRETE_SKU_SECOND => '', static::CONCRETE_SKU_THIRD => ''],
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([static::KEY_IS_DISCONTINUED => true, static::KEY_HAS_ALTERNATIVES => false], $productDetails[static::KEY_DISCONTINUED]);
        $this->assertArrayNotHasKey(static::KEY_SUGGESTED_NEXT_ACTION, $productDetails);
    }

    public function testGivenSeveralDiscontinuedVariantsWhenDetailsRequestedThenAlternativesAreReadInOneBulkCall(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveProductWithThreeProcessorVariants($idProductAbstract);
        $this->tester->haveProductStockStorageClientsReturning(
            $this->createProcessorVariantsAvailability([0, 0, 0], [static::CONCRETE_SKU_FIRST, static::CONCRETE_SKU_SECOND, static::CONCRETE_SKU_THIRD]),
            [static::CONCRETE_SKU_FIRST => '', static::CONCRETE_SKU_THIRD => ''],
            [static::CONCRETE_SKU_THIRD => [185]],
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $concreteAlternativeProductCriteriaTransfers = $this->tester->getConcreteAlternativeProductCriteriaTransfers();
        $this->assertCount(1, $concreteAlternativeProductCriteriaTransfers);
        $concreteAlternativeProductConditionsTransfer = $concreteAlternativeProductCriteriaTransfers[0]->getConcreteAlternativeProductConditionsOrFail();
        $this->assertSame([static::CONCRETE_SKU_FIRST, static::CONCRETE_SKU_THIRD], $concreteAlternativeProductConditionsTransfer->getSkus());
        $this->assertNotEmpty($concreteAlternativeProductConditionsTransfer->getLocaleName());
        $this->assertTrue($productDetails[static::KEY_DISCONTINUED][static::KEY_HAS_ALTERNATIVES]);
    }

    public function testGivenNoAvailabilityRecordWhenDetailsRequestedThenAvailabilityIsUnknownNotTrue(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOneVariant($idProductAbstract);
        $this->tester->haveProductStockStorageClientsReturning(null);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([static::KEY_IS_KNOWN => false], $productDetails[static::KEY_AVAILABILITY]);
        $this->assertArrayNotHasKey(static::KEY_IS_AVAILABLE, $productDetails[static::KEY_VARIANTS][static::KEY_CONCRETES][0]);
    }

    public function testGivenPublishedProductWhenDetailsRequestedThenRealStorageReportsItsAvailability(): void
    {
        // Arrange
        [$idProductAbstract] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => ($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? []) !== [],
            'with a concrete product',
        );

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $availability = $productDetails[static::KEY_AVAILABILITY];
        $this->assertIsBool($availability[static::KEY_IS_KNOWN]);
        $this->assertSame($availability[static::KEY_IS_KNOWN], isset($availability[static::KEY_IS_AVAILABLE]));
    }

    public function testGivenProductWithOptionsWhenDetailsRequestedThenOptionNamesAreTranslatedAndPricesFormatted(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOptions($idProductAbstract, static::OPTION_PRICE_INSURANCE, static::OPTION_PRICE_GIFT_WRAPPING);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([
            [static::KEY_GROUP => 'Insurance', static::KEY_NAME => 'Two-year insurance', static::KEY_SKU => static::OPTION_SKU_INSURANCE, static::KEY_PRICE_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::OPTION_PRICE_INSURANCE)],
            [static::KEY_GROUP => 'Gift wrapping', static::KEY_NAME => 'Gift wrap', static::KEY_SKU => static::OPTION_SKU_GIFT_WRAPPING, static::KEY_PRICE_FORMATTED => $this->tester->formatAmountInCurrentCurrency(static::OPTION_PRICE_GIFT_WRAPPING)],
        ], $productDetails[static::KEY_OPTIONS]);
    }

    public function testGivenOptionsWithoutResolvedPriceWhenDetailsRequestedThenOptionsAreListedWithoutPrice(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOptions($idProductAbstract, null, null);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame(['Two-year insurance', 'Gift wrap'], array_column($productDetails[static::KEY_OPTIONS], static::KEY_NAME));
        $this->assertSame([], array_column($productDetails[static::KEY_OPTIONS], static::KEY_PRICE_FORMATTED));
    }

    public function testGivenProductWithoutOptionsWhenDetailsRequestedThenNoOptionsSectionIsReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOneVariant($idProductAbstract);
        $this->tester->haveProductOptionStorageClientReturning($this->tester->createProductAbstractOptionStorage($idProductAbstract, [static::OPTION_GROUP_INSURANCE => []]));

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_OPTIONS, $productDetails);
    }

    public function testGivenPublishedProductWithOptionsWhenDetailsRequestedThenRealStorageListsTranslatedOptions(): void
    {
        // Arrange
        [$idProductAbstract, $productAbstractOptionStorageTransfer] = $this->tester->getProductAbstractWithOptionsFromStorage();
        $optionValueKeys = $this->extractOptionValueKeys($productAbstractOptionStorageTransfer);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame(array_keys($optionValueKeys), array_column($productDetails[static::KEY_OPTIONS], static::KEY_SKU));
        $this->assertSame([], array_intersect(array_column($productDetails[static::KEY_OPTIONS], static::KEY_NAME), $optionValueKeys));
    }

    /**
     * @return array<string, string>
     */
    protected function extractOptionValueKeys(ProductAbstractOptionStorageTransfer $productAbstractOptionStorageTransfer): array
    {
        $optionValueKeys = [];

        foreach ($productAbstractOptionStorageTransfer->getProductOptionGroups() as $productOptionGroupStorageTransfer) {
            foreach ($productOptionGroupStorageTransfer->getProductOptionValues() as $productOptionValueStorageTransfer) {
                $optionValueKeys[(string)$productOptionValueStorageTransfer->getSku()] = (string)$productOptionValueStorageTransfer->getValue();
            }
        }

        return $optionValueKeys;
    }

    protected function haveCameraWithOptions(int $idProductAbstract, ?int $insurancePrice, ?int $giftWrappingPrice): void
    {
        $this->haveCameraWithOneVariant($idProductAbstract);
        $this->tester->haveGlossaryTranslating(static::OPTION_TRANSLATIONS);
        $this->tester->haveProductOptionStorageClientReturning($this->tester->createProductAbstractOptionStorage($idProductAbstract, [
            static::OPTION_GROUP_INSURANCE => [['sku' => static::OPTION_SKU_INSURANCE, 'value' => static::OPTION_VALUE_INSURANCE, 'price' => $insurancePrice]],
            static::OPTION_GROUP_GIFT_WRAPPING => [['sku' => static::OPTION_SKU_GIFT_WRAPPING, 'value' => static::OPTION_VALUE_GIFT_WRAPPING, 'price' => $giftWrappingPrice]],
        ]));
    }

    public function testGivenReviewedProductWhenDetailsRequestedThenRatingAndReviewCountAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOneVariant($idProductAbstract);
        $this->tester->haveProductReviewStorageClientReturning([$idProductAbstract => [4.66, 3]]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([4.7, 3], [$productDetails[static::KEY_RATING], $productDetails[static::KEY_REVIEW_COUNT]]);
    }

    public function testGivenProductWithoutReviewsWhenDetailsRequestedThenNoRatingIsReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOneVariant($idProductAbstract);
        $this->tester->haveProductReviewStorageClientReturning([$idProductAbstract => [0.0, 0]]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_RATING, $productDetails);
    }

    public function testGivenBundleProductWhenDetailsRequestedThenEveryBundledProductIsListedWithQuantity(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveBundleWithTwoProducts($idProductAbstract);
        $this->tester->haveProductBundleStorageClientReturning([301 => [118 => 2, 140 => 1]]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertSame([
            [static::KEY_SKU => 'bundled-118', static::KEY_NAME => 'ASUS VivoWatch', static::KEY_QUANTITY => 2, static::KEY_URL => '/en/asus-vivowatch-99'],
            [static::KEY_SKU => 'bundled-140', static::KEY_NAME => 'ASUS ZenPad', static::KEY_QUANTITY => 1, static::KEY_URL => '/en/asus-zenpad-114'],
        ], $productDetails[static::KEY_BUNDLED_PRODUCTS]);
    }

    public function testGivenProductThatIsNoBundleWhenDetailsRequestedThenNoBundledProductsAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->haveCameraWithOneVariant($idProductAbstract);
        $this->tester->haveProductBundleStorageClientReturning([]);

        // Act
        $productDetails = $this->tester->executeProductDetailsTool([static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_BUNDLED_PRODUCTS, $productDetails);
    }

    protected function haveBundleWithTwoProducts(int $idProductAbstract): void
    {
        $this->tester->haveProductStorageClientReturning([
            $idProductAbstract => $this->tester->createProductAbstractStorageDataWithVariants($idProductAbstract, static::ABSTRACT_SKU, [], [], [static::CONCRETE_SKU_CAMERA => 301]),
        ], [], [], [
            118 => ['id_product_concrete' => 118, 'name' => 'ASUS VivoWatch', 'url' => '/en/asus-vivowatch-99'],
            140 => ['id_product_concrete' => 140, 'name' => 'ASUS ZenPad', 'url' => '/en/asus-zenpad-114'],
        ]);
    }

    protected function haveCameraWithOneVariant(int $idProductAbstract): void
    {
        $this->tester->haveProductStorageClientReturning([
            $idProductAbstract => $this->tester->createProductAbstractStorageDataWithVariants(
                $idProductAbstract,
                static::ABSTRACT_SKU,
                static::CAMERA_ATTRIBUTES,
                [],
                [static::CONCRETE_SKU_CAMERA => 301],
            ),
        ]);
    }

    /**
     * @param list<int> $quantities
     * @param list<string> $neverOutOfStockSkus
     */
    protected function createProcessorVariantsAvailability(array $quantities, array $neverOutOfStockSkus = []): ProductAbstractAvailabilityTransfer
    {
        return $this->tester->createProductAbstractAvailability(
            '145',
            array_combine([static::CONCRETE_SKU_FIRST, static::CONCRETE_SKU_SECOND, static::CONCRETE_SKU_THIRD], $quantities),
            $neverOutOfStockSkus,
        );
    }

    /**
     * @param array<string, int> $idProductAbstractByConcreteSku
     */
    protected function haveProductWithThreeProcessorVariants(int $idProductAbstract, array $idProductAbstractByConcreteSku = []): void
    {
        $this->tester->haveProductStockStorageClientsReturning(null);
        $this->tester->haveProductStorageClientReturning(
            [
            $idProductAbstract => $this->tester->createProductAbstractStorageDataWithVariants(
                $idProductAbstract,
                (string)$idProductAbstract,
                ['brand' => 'DELL'],
                [static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => [static::PROCESSOR_FREQUENCY_FIRST, static::PROCESSOR_FREQUENCY_SECOND, static::PROCESSOR_FREQUENCY_THIRD]],
                [static::CONCRETE_SKU_FIRST => 201, static::CONCRETE_SKU_SECOND => 202, static::CONCRETE_SKU_THIRD => 203],
                [
                    201 => [static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => static::PROCESSOR_FREQUENCY_FIRST],
                    202 => [static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => static::PROCESSOR_FREQUENCY_SECOND],
                    203 => [static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => static::PROCESSOR_FREQUENCY_THIRD],
                ],
            )],
            [],
            $idProductAbstractByConcreteSku,
        );
    }
}
