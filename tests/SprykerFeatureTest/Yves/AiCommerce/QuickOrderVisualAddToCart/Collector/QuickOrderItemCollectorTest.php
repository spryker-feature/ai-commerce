<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\QuickOrderVisualAddToCart\Collector;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AttributeMapStorageTransfer;
use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use Generated\Shared\Transfer\ProductRecognitionTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Generated\Shared\Transfer\QuickOrderPageResponseTransfer;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Collector\QuickOrderItemCollector;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Finder\CatalogProductFinder;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Matcher\ProductNameMatcher;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group QuickOrderVisualAddToCart
 * @group Collector
 * @group QuickOrderItemCollectorTest
 */
class QuickOrderItemCollectorTest extends Unit
{
    protected const string LOCALE_NAME = 'en_US';

    protected const string KEY_SUGGESTION_BY_TYPE = 'suggestionByType';

    protected const string KEY_PRODUCT_CONCRETE = 'product_concrete';

    protected const string KEY_PRODUCT_ABSTRACT = 'product_abstract';

    protected const string KEY_SKU = 'sku';

    protected const string KEY_ABSTRACT_SKU = 'abstract_sku';

    protected const string KEY_NAME = 'name';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string CONCRETE_SKU = 'HVK-04-21410-01';

    protected const string ABSTRACT_SKU = 'HVK-04-21410';

    protected const string SECOND_VARIANT_CONCRETE_SKU = 'HVK-04-21410-02';

    protected const string PRODUCT_NAME = 'Front brake pad set HX';

    protected const string OTHER_CONCRETE_SKU = '010_30692994';

    protected const string OTHER_PRODUCT_NAME = 'Canon IXUS 160';

    protected const int ID_PRODUCT_ABSTRACT = 186;

    protected const int ID_PRODUCT_CONCRETE = 1860;

    protected const int ID_SECOND_VARIANT_PRODUCT_CONCRETE = 1861;

    protected AiCommerceYvesTester $tester;

    /**
     * @dataProvider provideRecognizedSkus
     */
    public function testGivenRecognizedSkuWhenCollectingItemsThenProductIsAddedAlthoughItsNameDoesNotContainTheSku(string $recognizedSku): void
    {
        // Arrange
        $quickOrderItemCollector = $this->createQuickOrderItemCollector([
            $recognizedSku => $this->createConcreteSearchResult(static::CONCRETE_SKU, static::ABSTRACT_SKU, static::PRODUCT_NAME),
        ]);

        // Act
        $quickOrderPageResponseTransfer = $quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions(
            $this->createProductRecognitionCollection([$recognizedSku => 4]),
        );

        // Assert
        $this->assertSame([static::CONCRETE_SKU => 4], $this->mapQuantitiesBySku($quickOrderPageResponseTransfer));
        $this->assertCount(0, $quickOrderPageResponseTransfer->getNotFoundProductNames());
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideRecognizedSkus(): array
    {
        return [
            'concrete SKU' => [static::CONCRETE_SKU],
            'concrete SKU in lower case with surrounding spaces' => [' hvk-04-21410-01 '],
        ];
    }

    public function testGivenSkusAndNamesWhenCollectingItemsThenMatchingProductsAreAddedAndUnrelatedSearchHitIsReportedAsNotFound(): void
    {
        // Arrange
        $unrelatedText = 'service-006-1';
        $quickOrderItemCollector = $this->createQuickOrderItemCollector([
            static::CONCRETE_SKU => $this->createConcreteSearchResult(static::CONCRETE_SKU, static::ABSTRACT_SKU, static::PRODUCT_NAME),
            static::OTHER_PRODUCT_NAME => $this->createConcreteSearchResult(static::OTHER_CONCRETE_SKU, '010', static::OTHER_PRODUCT_NAME),
            $unrelatedText => $this->createConcreteSearchResult(static::OTHER_CONCRETE_SKU, '010', static::OTHER_PRODUCT_NAME),
        ]);

        // Act
        $quickOrderPageResponseTransfer = $quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions(
            $this->createProductRecognitionCollection([static::CONCRETE_SKU => 2, static::OTHER_PRODUCT_NAME => 3, $unrelatedText => 1]),
        );

        // Assert
        $this->assertSame([static::CONCRETE_SKU => 2, static::OTHER_CONCRETE_SKU => 3], $this->mapQuantitiesBySku($quickOrderPageResponseTransfer));
        $this->assertSame([$unrelatedText], $quickOrderPageResponseTransfer->getNotFoundProductNames());
    }

    public function testGivenBlankRecognizedNameAndSearchHitWithoutSkuWhenCollectingItemsThenNoProductIsAdded(): void
    {
        // Arrange
        $blankName = '   ';
        $quickOrderItemCollector = $this->createQuickOrderItemCollector([
            $blankName => [static::KEY_SUGGESTION_BY_TYPE => [static::KEY_PRODUCT_CONCRETE => [[static::KEY_NAME => static::PRODUCT_NAME]]]],
        ]);

        // Act
        $quickOrderPageResponseTransfer = $quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions(
            $this->createProductRecognitionCollection([$blankName => 1]),
        );

        // Assert
        $this->assertSame([], $this->mapQuantitiesBySku($quickOrderPageResponseTransfer));
    }

    public function testGivenAbstractSkuAndConcreteSearchHitOfThatAbstractProductWhenCollectingItemsThenLineIsReportedAsNotFound(): void
    {
        // Arrange
        $quickOrderItemCollector = $this->createQuickOrderItemCollector([
            static::ABSTRACT_SKU => $this->createConcreteSearchResult(static::CONCRETE_SKU, static::ABSTRACT_SKU, static::PRODUCT_NAME),
        ]);

        // Act
        $quickOrderPageResponseTransfer = $quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions(
            $this->createProductRecognitionCollection([static::ABSTRACT_SKU => 5]),
        );

        // Assert
        $this->assertSame([], $this->mapQuantitiesBySku($quickOrderPageResponseTransfer));
        $this->assertSame([static::ABSTRACT_SKU], $quickOrderPageResponseTransfer->getNotFoundProductNames());
    }

    public function testGivenAbstractSkuFoundOnlyAsAbstractProductWhenCollectingItemsThenLineIsReportedAsNotFound(): void
    {
        // Arrange
        $quickOrderItemCollector = $this->createQuickOrderItemCollector(
            [static::ABSTRACT_SKU => $this->createAbstractSearchResult()],
            $this->createProductStorageClientMock(),
        );

        // Act
        $quickOrderPageResponseTransfer = $quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions(
            $this->createProductRecognitionCollection([static::ABSTRACT_SKU => 5]),
        );

        // Assert
        $this->assertSame([], $this->mapQuantitiesBySku($quickOrderPageResponseTransfer));
        $this->assertSame([static::ABSTRACT_SKU], $quickOrderPageResponseTransfer->getNotFoundProductNames());
    }

    /**
     * @dataProvider provideSecondVariantSkus
     */
    public function testGivenSkuOfSecondVariantFoundOnlyAsAbstractProductWhenCollectingItemsThenThatVariantIsAdded(string $recognizedSku): void
    {
        // Arrange
        $quickOrderItemCollector = $this->createQuickOrderItemCollector(
            [$recognizedSku => $this->createAbstractSearchResult()],
            $this->createProductStorageClientMock(),
        );

        // Act
        $quickOrderPageResponseTransfer = $quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions(
            $this->createProductRecognitionCollection([$recognizedSku => 3]),
        );

        // Assert
        $this->assertSame([static::SECOND_VARIANT_CONCRETE_SKU => 3], $this->mapQuantitiesBySku($quickOrderPageResponseTransfer));
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideSecondVariantSkus(): array
    {
        return [
            'concrete SKU' => [static::SECOND_VARIANT_CONCRETE_SKU],
            'concrete SKU in lower case with surrounding spaces' => [' hvk-04-21410-02 '],
        ];
    }

    public function testGivenProductNameFoundOnlyAsAbstractProductWhenCollectingItemsThenFirstVariantIsAdded(): void
    {
        // Arrange
        $quickOrderItemCollector = $this->createQuickOrderItemCollector(
            [static::PRODUCT_NAME => $this->createAbstractSearchResult()],
            $this->createProductStorageClientMock(),
        );

        // Act
        $quickOrderPageResponseTransfer = $quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions(
            $this->createProductRecognitionCollection([static::PRODUCT_NAME => 2]),
        );

        // Assert
        $this->assertSame([static::CONCRETE_SKU => 2], $this->mapQuantitiesBySku($quickOrderPageResponseTransfer));
    }

    /**
     * @param array<string, array<string, mixed>> $searchResultsBySearchString
     */
    protected function createQuickOrderItemCollector(
        array $searchResultsBySearchString,
        ?ProductStorageClientInterface $productStorageClient = null,
    ): QuickOrderItemCollector {
        $catalogClientMock = $this->createMock(CatalogClientInterface::class);
        $catalogClientMock->method('catalogSuggestMultiSearch')->willReturnCallback(
            static fn (array $searchStrings): array => array_map(
                static fn (string $searchString): array => $searchResultsBySearchString[$searchString] ?? [],
                $searchStrings,
            ),
        );
        $localeClient = $this->createConfiguredMock(LocaleClientInterface::class, ['getCurrentLocale' => static::LOCALE_NAME]);

        return new QuickOrderItemCollector(
            new CatalogProductFinder($catalogClientMock, $productStorageClient ?? $this->createMock(ProductStorageClientInterface::class), $localeClient),
            new ProductNameMatcher($this->tester->createQuickOrderVisualAddToCartConfig()),
        );
    }

    protected function createProductStorageClientMock(): ProductStorageClientInterface
    {
        $productStorageClientMock = $this->createMock(ProductStorageClientInterface::class);
        $productStorageClientMock->method('getProductAbstractViewTransfers')->willReturn([
            (new ProductViewTransfer())
                ->setSku(static::ABSTRACT_SKU)
                ->setAttributeMap((new AttributeMapStorageTransfer())->setProductConcreteIds([
                    static::CONCRETE_SKU => static::ID_PRODUCT_CONCRETE,
                    static::SECOND_VARIANT_CONCRETE_SKU => static::ID_SECOND_VARIANT_PRODUCT_CONCRETE,
                ])),
        ]);
        $productStorageClientMock->method('getProductConcreteViewTransfers')->willReturnCallback(
            static fn (array $productConcreteIds): array => array_map(
                static fn (int $idProductConcrete): ProductViewTransfer => (new ProductViewTransfer())
                    ->setSku($idProductConcrete === static::ID_SECOND_VARIANT_PRODUCT_CONCRETE ? static::SECOND_VARIANT_CONCRETE_SKU : static::CONCRETE_SKU)
                    ->setName(static::PRODUCT_NAME),
                $productConcreteIds,
            ),
        );

        return $productStorageClientMock;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createAbstractSearchResult(): array
    {
        return [static::KEY_SUGGESTION_BY_TYPE => [static::KEY_PRODUCT_ABSTRACT => [[static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT]]]];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createConcreteSearchResult(string $sku, string $abstractSku, string $name): array
    {
        return [
            static::KEY_SUGGESTION_BY_TYPE => [
                static::KEY_PRODUCT_CONCRETE => [
                    [static::KEY_SKU => $sku, static::KEY_ABSTRACT_SKU => $abstractSku, static::KEY_NAME => $name],
                ],
            ],
        ];
    }

    /**
     * @param array<string, int> $quantitiesByProductName
     */
    protected function createProductRecognitionCollection(array $quantitiesByProductName): ProductRecognitionCollectionTransfer
    {
        $productRecognitionCollectionTransfer = new ProductRecognitionCollectionTransfer();

        foreach ($quantitiesByProductName as $productName => $quantity) {
            $productRecognitionCollectionTransfer->addProductRecognition(
                (new ProductRecognitionTransfer())->setName($productName)->setQuantity($quantity),
            );
        }

        return $productRecognitionCollectionTransfer;
    }

    /**
     * @return array<string, int>
     */
    protected function mapQuantitiesBySku(QuickOrderPageResponseTransfer $quickOrderPageResponseTransfer): array
    {
        $quantitiesBySku = [];

        foreach ($quickOrderPageResponseTransfer->getQuickOrderItems() as $quickOrderItemTransfer) {
            $quantitiesBySku[$quickOrderItemTransfer->getSkuOrFail()] = $quickOrderItemTransfer->getQuantityOrFail();
        }

        return $quantitiesBySku;
    }
}
