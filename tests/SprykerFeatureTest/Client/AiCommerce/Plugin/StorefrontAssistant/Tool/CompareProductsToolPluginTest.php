<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group CompareProductsToolPluginTest
 */
class CompareProductsToolPluginTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    protected const string KEY_ERROR = 'error';

    protected const string KEY_COMPARED_PRODUCTS = 'comparedProducts';

    protected const string KEY_ATTRIBUTE_ROWS = 'attributeRows';

    protected const string KEY_COMPARISON_URL = 'comparisonUrl';

    protected const string KEY_IGNORED_ID_PRODUCT_ABSTRACTS = 'ignoredIdProductAbstracts';

    protected const string KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS = 'unknownIdProductAbstracts';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string KEY_NAME = 'name';

    protected const string KEY_SKU = 'sku';

    protected const string KEY_PRICE_FORMATTED = 'priceFormatted';

    protected const string KEY_RATING = 'rating';

    protected const string KEY_REVIEW_COUNT = 'reviewCount';

    protected const string KEY_IS_AVAILABLE = 'isAvailable';

    protected const string KEY_ATTRIBUTE = 'attribute';

    protected const string KEY_VALUES = 'values';

    protected const string KEY_IS_DIFFERENT = 'isDifferent';

    protected const string KEY_ALREADY_SHOWN = 'alreadyShown';

    protected const string KEY_PRODUCTS = 'products';

    protected const string STORAGE_KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string SKU_FIRST = 'compare-first';

    protected const string SKU_SECOND = 'compare-second';

    protected const string CONCRETE_SKU_FIRST = 'compare-first_1';

    protected const string CONCRETE_SKU_SECOND_SOLD_OUT = 'compare-second_1';

    protected const string CONCRETE_SKU_SECOND_IN_STOCK = 'compare-second_2';

    protected const string GLOSSARY_KEY_MEGAPIXEL = 'product.attribute.megapixel';

    protected const string MEGAPIXEL_NAME = 'Megapixel';

    protected const string SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY = 'processor_frequency';

    /**
     * @var array<string, string>
     */
    protected const array ATTRIBUTES_FIRST = [
        'brand' => 'Canon',
        'megapixel' => '20 MP',
        'color' => 'Red',
    ];

    /**
     * @var array<string, string>
     */
    protected const array ATTRIBUTES_SECOND = [
        'brand' => 'Canon',
        'optical_zoom' => '8 x',
        'color' => 'Silver',
    ];

    protected AiCommerceClientTester $tester;

    public function testGivenTwoProductsWhenComparedThenRatingAvailabilityAndAttributesAreReturnedForBoth(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([static::CONCRETE_SKU_FIRST => 5, static::CONCRETE_SKU_SECOND_SOLD_OUT => 0, static::CONCRETE_SKU_SECOND_IN_STOCK => 0]);
        $this->tester->haveProductReviewStorageClientReturning([$idFirst => [4.66, 3]]);

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Assert
        [$first, $second] = $comparison[static::KEY_COMPARED_PRODUCTS];
        $this->assertSame([$idFirst, static::SKU_FIRST, 4.7, 3, true], [$first[static::KEY_ID_PRODUCT_ABSTRACT], $first[static::KEY_SKU], $first[static::KEY_RATING], $first[static::KEY_REVIEW_COUNT], $first[static::KEY_IS_AVAILABLE]]);
        $this->assertSame([$idSecond, false, false], [$second[static::KEY_ID_PRODUCT_ABSTRACT], isset($second[static::KEY_RATING]), $second[static::KEY_IS_AVAILABLE]]);
        $this->assertCount(4, $comparison[static::KEY_ATTRIBUTE_ROWS]);
    }

    public function testGivenDifferingAttributesWhenComparedThenTheyAreMarkedDifferentAndListedFirst(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([]);
        $this->tester->haveGlossaryTranslating([static::GLOSSARY_KEY_MEGAPIXEL => static::MEGAPIXEL_NAME]);

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Assert
        $this->assertSame([
            [static::KEY_ATTRIBUTE => static::MEGAPIXEL_NAME, static::KEY_VALUES => ['20 MP', null], static::KEY_IS_DIFFERENT => true],
            [static::KEY_ATTRIBUTE => 'Color', static::KEY_VALUES => ['Red', 'Silver'], static::KEY_IS_DIFFERENT => true],
            [static::KEY_ATTRIBUTE => 'Optical zoom', static::KEY_VALUES => [null, '8 x'], static::KEY_IS_DIFFERENT => true],
            [static::KEY_ATTRIBUTE => 'Brand', static::KEY_VALUES => ['Canon', 'Canon'], static::KEY_IS_DIFFERENT => false],
        ], $comparison[static::KEY_ATTRIBUTE_ROWS]);
    }

    public function testGivenVariantOptionsWhenComparedThenTheirValuesAreComparedAsOneAttribute(): void
    {
        // Arrange
        $idFirst = $this->tester->getUnknownIdProductAbstract();
        $idSecond = $idFirst + 1;
        $this->tester->haveProductStorageClientReturning([
            $idFirst => $this->tester->createProductAbstractStorageDataWithVariants($idFirst, static::SKU_FIRST, [], [static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => ['2 GHz', '2.3 GHz']], [static::CONCRETE_SKU_FIRST => 1]),
            $idSecond => $this->tester->createProductAbstractStorageDataWithVariants($idSecond, static::SKU_SECOND, [static::SUPER_ATTRIBUTE_PROCESSOR_FREQUENCY => '1.5 GHz'], [], [static::CONCRETE_SKU_SECOND_IN_STOCK => 2]),
        ]);
        $this->haveNoStockReviewsOrTranslations();

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Assert
        $this->assertSame([['2 GHz / 2.3 GHz', '1.5 GHz']], array_column($comparison[static::KEY_ATTRIBUTE_ROWS], static::KEY_VALUES));
    }

    public function testGivenTwoProductsWhenComparedThenComparisonUrlCarriesAnInStockConcreteSkuOfEach(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([static::CONCRETE_SKU_FIRST => 5, static::CONCRETE_SKU_SECOND_SOLD_OUT => 0, static::CONCRETE_SKU_SECOND_IN_STOCK => 2]);

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Assert
        $this->assertSame(
            sprintf('/product-comparison?skus=%s,%s', static::CONCRETE_SKU_FIRST, static::CONCRETE_SKU_SECOND_IN_STOCK),
            $comparison[static::KEY_COMPARISON_URL],
        );
    }

    public function testGivenSameProductsComparedTwiceInOneTurnWhenComparedAgainThenNoSecondTableIsReturned(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([static::CONCRETE_SKU_FIRST => 5, static::CONCRETE_SKU_SECOND_SOLD_OUT => 0, static::CONCRETE_SKU_SECOND_IN_STOCK => 0]);
        $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idSecond, $idFirst]], false);

        // Assert
        $this->assertSame([static::KEY_ALREADY_SHOWN], array_keys($comparison));
    }

    public function testGivenComparisonShownInThisTurnWhenProductsAreDisplayedThenNoCardsAreShown(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([static::CONCRETE_SKU_FIRST => 5, static::CONCRETE_SKU_SECOND_SOLD_OUT => 0, static::CONCRETE_SKU_SECOND_IN_STOCK => 0]);
        $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Act
        $result = (new DisplayProductsToolPlugin())->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Assert
        $this->assertSame([], $result[static::KEY_PRODUCTS]);
        $this->assertArrayHasKey(static::KEY_ALREADY_SHOWN, $result);
    }

    public function testGivenSameProductsComparedInANewTurnWhenComparedThenTheTableIsReturnedAgain(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([static::CONCRETE_SKU_FIRST => 5, static::CONCRETE_SKU_SECOND_SOLD_OUT => 0, static::CONCRETE_SKU_SECOND_IN_STOCK => 0]);
        $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Assert
        $this->assertCount(2, $comparison[static::KEY_COMPARED_PRODUCTS]);
    }

    public function testGivenFewerThanTwoDistinctProductsWhenComparedThenAnErrorAsksForTwoToFour(): void
    {
        // Arrange
        [$idFirst] = $this->haveTwoComparableProducts([]);

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, (string)$idFirst, 0, true]]);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($comparison));
        $this->assertStringContainsString('2 to 4', $comparison[static::KEY_ERROR]);
    }

    public function testGivenFiveProductsWhenComparedThenOnlyTheFirstFourAreCompared(): void
    {
        // Arrange
        $idFirst = $this->tester->getUnknownIdProductAbstract();
        $productAbstractIds = range($idFirst, $idFirst + 4);
        $this->tester->haveProductsInProductStorage($productAbstractIds);
        $this->haveNoStockReviewsOrTranslations();

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => $productAbstractIds]);

        // Assert
        $this->assertSame(array_slice($productAbstractIds, 0, 4), array_column($comparison[static::KEY_COMPARED_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertSame([$idFirst + 4], $comparison[static::KEY_IGNORED_ID_PRODUCT_ABSTRACTS]);
    }

    public function testGivenOneUnknownProductAmongThreeWhenComparedThenItIsReportedAsUnknown(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([]);
        $idUnknown = $idSecond + 1;

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idUnknown, $idSecond]]);

        // Assert
        $this->assertSame([$idFirst, $idSecond], array_column($comparison[static::KEY_COMPARED_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertSame([$idUnknown], $comparison[static::KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS]);
    }

    public function testGivenOnlyOneKnownProductWhenComparedThenAnErrorNamesTheUnknownOne(): void
    {
        // Arrange
        [$idFirst, $idSecond] = $this->haveTwoComparableProducts([]);
        $idUnknown = $idSecond + 1;

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idUnknown]]);

        // Assert
        $this->assertArrayHasKey(static::KEY_ERROR, $comparison);
        $this->assertSame([$idUnknown], $comparison[static::KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS]);
    }

    public function testGivenTwoPublishedProductsWhenComparedThenRealStorageResolvesTheirPricesAndConcreteSkus(): void
    {
        // Arrange
        [$idFirst] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => ($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? []) !== [],
            'with a concrete product',
        );
        [$idSecond] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => (int)($productData[static::STORAGE_KEY_ID_PRODUCT_ABSTRACT] ?? 0) !== $idFirst
                && ($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? []) !== [],
            'with a concrete product besides the first one',
        );

        // Act
        $comparison = $this->tester->executeCompareProductsTool([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idFirst, $idSecond]]);

        // Assert
        $this->assertCount(2, array_filter(array_column($comparison[static::KEY_COMPARED_PRODUCTS], static::KEY_PRICE_FORMATTED)));
        $this->assertStringStartsWith('/product-comparison?skus=', $comparison[static::KEY_COMPARISON_URL]);
    }

    /**
     * @param array<string, int> $quantityBySku
     *
     * @return array{0: int, 1: int}
     */
    protected function haveTwoComparableProducts(array $quantityBySku): array
    {
        $idFirst = $this->tester->getUnknownIdProductAbstract();
        $idSecond = $idFirst + 1;
        $this->tester->haveProductStorageClientReturning([
            $idFirst => $this->tester->createProductAbstractStorageDataWithVariants($idFirst, static::SKU_FIRST, static::ATTRIBUTES_FIRST, [], [static::CONCRETE_SKU_FIRST => 1]),
            $idSecond => $this->tester->createProductAbstractStorageDataWithVariants($idSecond, static::SKU_SECOND, static::ATTRIBUTES_SECOND, [], [static::CONCRETE_SKU_SECOND_SOLD_OUT => 2, static::CONCRETE_SKU_SECOND_IN_STOCK => 3]),
        ]);
        $this->haveNoStockReviewsOrTranslations();
        $this->tester->haveProductStockStorageClientsReturning($quantityBySku === [] ? null : $this->tester->createProductAbstractAvailability(static::SKU_FIRST, $quantityBySku));

        return [$idFirst, $idSecond];
    }

    protected function haveNoStockReviewsOrTranslations(): void
    {
        $this->tester->haveProductStockStorageClientsReturning(null);
        $this->tester->haveProductReviewStorageClientReturning([]);
        $this->tester->haveGlossaryTranslating([]);
    }
}
