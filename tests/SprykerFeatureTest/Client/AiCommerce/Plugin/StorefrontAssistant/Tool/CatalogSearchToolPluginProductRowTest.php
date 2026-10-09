<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group CatalogSearchToolPluginProductRowTest
 */
class CatalogSearchToolPluginProductRowTest extends Unit
{
    protected const string PARAMETER_QUERY = 'query';

    protected const string QUERY = 'camera';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_ATTRIBUTES = 'attributes';

    protected const string KEY_VARIANTS = 'variants';

    protected const string KEY_VARIANT_COUNT = 'variantCount';

    protected const string KEY_RATING = 'rating';

    protected const string KEY_REVIEW_COUNT = 'reviewCount';

    protected const string KEY_LABELS = 'labels';

    protected const string KEY_LABEL_IDS = 'labelIds';

    protected const string KEY_DESCRIPTION = 'description';

    protected const string KEY_PRICE = 'price';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'idProductConcrete';

    protected const string KEY_CONCRETE_SKU = 'concreteSku';

    protected const string STORAGE_KEY_ATTRIBUTES = 'attributes';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string STORAGE_KEY_SUPER_ATTRIBUTES = 'super_attributes';

    protected const string STORAGE_KEY_DESCRIPTION = 'description';

    protected const string SEARCH_KEY_RATING = 'rating';

    protected const string SEARCH_KEY_REVIEW_COUNT = 'review_count';

    protected const string SEARCH_KEY_ID_PRODUCT_LABELS = 'id_product_labels';

    protected const string SEARCH_KEY_PRICE = 'price';

    protected const float SEARCH_HIT_RATING = 4.25;

    protected const int SEARCH_HIT_REVIEW_COUNT = 4;

    protected const int SEARCH_HIT_PRICE = 4200;

    protected const int MAX_ATTRIBUTES = 10;

    protected const int MAX_DESCRIPTION_LENGTH = 200;

    protected const int PRICE_PRECISION = 100;

    protected const string KEY_IS_PRICE_OUTLIER = 'isPriceOutlier';

    protected const string SAME_NAME = 'Sony Cyber-shot DSC-W800';

    protected const int ID_PRODUCT_ABSTRACT_REGULAR_PRICE = 900016;

    protected const int ID_PRODUCT_ABSTRACT_OUTLIER_PRICE = 900017;

    protected const int REGULAR_PRICE = 8500;

    protected const int OUTLIER_PRICE = 311130;

    protected AiCommerceClientTester $tester;

    public function testGivenSearchHitWhenExecutedThenTheRowCarriesTheProductsStoredAttributes(): void
    {
        // Arrange
        [$idProductAbstract, $productData] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => ($productData[static::STORAGE_KEY_ATTRIBUTES] ?? []) !== [],
            'with attributes',
        );
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $attributes = $product[static::KEY_ATTRIBUTES];
        $this->assertNotEmpty($attributes);
        $this->assertLessThanOrEqual(static::MAX_ATTRIBUTES, count($attributes));

        foreach ($attributes as $attributeKey => $attributeValue) {
            $this->assertSame((string)$productData[static::STORAGE_KEY_ATTRIBUTES][$attributeKey], $attributeValue);
        }
    }

    public function testGivenSearchHitWithSeveralConcretesWhenExecutedThenTheRowCarriesItsVariants(): void
    {
        // Arrange
        [$idProductAbstract, $productData] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => count($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? []) > 1
                && ($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_SUPER_ATTRIBUTES] ?? []) !== [],
            'with several concrete products',
        );
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $attributeMap = $productData[static::STORAGE_KEY_ATTRIBUTE_MAP];
        $this->assertSame(count($attributeMap[static::STORAGE_KEY_PRODUCT_CONCRETE_IDS]), $product[static::KEY_VARIANT_COUNT]);
        $this->assertSame(array_keys($attributeMap[static::STORAGE_KEY_SUPER_ATTRIBUTES]), array_keys($product[static::KEY_VARIANTS]));
    }

    public function testGivenSearchHitWithASingleConcreteWhenExecutedThenTheRowCarriesNoVariants(): void
    {
        // Arrange
        [$idProductAbstract] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => count($productData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? []) === 1,
            'with a single concrete product',
        );
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $this->assertArrayNotHasKey(static::KEY_VARIANTS, $product);
        $this->assertArrayNotHasKey(static::KEY_VARIANT_COUNT, $product);
        $this->assertArrayNotHasKey(static::KEY_ID_PRODUCT_CONCRETE, $product);
        $this->assertArrayNotHasKey(static::KEY_CONCRETE_SKU, $product);
    }

    public function testGivenARatedSearchHitWhenExecutedThenTheRowCarriesRatingAndReviewCount(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getIdProductAbstractFromStorage();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract, [
            static::SEARCH_KEY_RATING => static::SEARCH_HIT_RATING,
            static::SEARCH_KEY_REVIEW_COUNT => static::SEARCH_HIT_REVIEW_COUNT,
        ]));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $this->assertSame(static::SEARCH_HIT_RATING, $product[static::KEY_RATING]);
        $this->assertSame(static::SEARCH_HIT_REVIEW_COUNT, $product[static::KEY_REVIEW_COUNT]);
    }

    public function testGivenAnUnratedSearchHitWhenExecutedThenTheRowCarriesNoRating(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getIdProductAbstractFromStorage();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $this->assertArrayNotHasKey(static::KEY_RATING, $product);
        $this->assertArrayNotHasKey(static::KEY_REVIEW_COUNT, $product);
    }

    public function testGivenALabelledSearchHitWhenExecutedThenTheRowCarriesTheLabelNames(): void
    {
        // Arrange
        $productLabelDictionaryItemTransfer = $this->tester->getProductLabelDictionaryItemFromStorage();
        $idProductAbstract = $this->tester->getIdProductAbstractFromStorage();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract, [
            static::SEARCH_KEY_ID_PRODUCT_LABELS => [$productLabelDictionaryItemTransfer->getIdProductLabelOrFail()],
        ]));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $this->assertSame([$productLabelDictionaryItemTransfer->getNameOrFail()], $product[static::KEY_LABELS]);
        $this->assertArrayNotHasKey(static::KEY_LABEL_IDS, $product);
    }

    public function testGivenALongDescriptionWhenExecutedThenTheAgentReadsAtMost200Characters(): void
    {
        // Arrange
        [$idProductAbstract, $productData] = $this->tester->getProductAbstractStorageDataMatching(
            static fn (array $productData): bool => mb_strlen((string)($productData[static::STORAGE_KEY_DESCRIPTION] ?? '')) > static::MAX_DESCRIPTION_LENGTH,
            'with a description longer than 200 characters',
        );
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $description = $product[static::KEY_DESCRIPTION];
        $this->assertSame(static::MAX_DESCRIPTION_LENGTH, mb_strlen($description));
        $this->assertSame(mb_substr(trim((string)$productData[static::STORAGE_KEY_DESCRIPTION]), 0, 20), mb_substr($description, 0, 20));
    }

    public function testGivenSearchHitsWhenExecutedThenProductStorageIsReadOnce(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getIdProductAbstractFromStorage();
        $bulkReadCount = 0;
        $this->tester->setUpProductStorageClientReadingPublishedData($bulkReadCount);
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract));

        // Act
        $this->executeAndGetFirstProduct();

        // Assert
        $this->assertSame(1, $bulkReadCount);
    }

    public function testGivenSearchHitWhenExecutedThenThePriceIsStillResolvedForItsFirstConcrete(): void
    {
        // Arrange
        $this->tester->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();
        $cardPrice = $this->tester->findDisplayedProductPrice($idProductAbstract);

        if ($cardPrice === null) {
            $this->markTestSkipped('The product found in storage has no price resolvable for the current store and currency.');
        }

        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRow($idProductAbstract, [
            static::SEARCH_KEY_PRICE => static::SEARCH_HIT_PRICE,
        ]));

        // Act
        $product = $this->executeAndGetFirstProduct();

        // Assert
        $this->assertSame(round($cardPrice / static::PRICE_PRECISION, 2), $product[static::KEY_PRICE]);
    }

    public function testGivenSameNameHitsWhenOneCostsMoreThanFiveTimesTheOtherThenOnlyItsRowIsFlaggedAsPriceOutlier(): void
    {
        // Arrange
        $this->tester->haveSameNameProductsPricedAt(static::SAME_NAME, [
            static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE => static::REGULAR_PRICE,
            static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE => static::OUTLIER_PRICE,
        ]);
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRows([
            static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE,
            static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE,
        ]));

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertSame(round(static::OUTLIER_PRICE / static::PRICE_PRECISION, 2), $result[static::KEY_PRODUCTS][1][static::KEY_PRICE]);
        $this->assertArrayNotHasKey(static::KEY_IS_PRICE_OUTLIER, $result[static::KEY_PRODUCTS][0]);
        $this->assertTrue($result[static::KEY_PRODUCTS][1][static::KEY_IS_PRICE_OUTLIER]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeAndGetFirstProduct(): array
    {
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        $this->assertCount(1, $result[static::KEY_PRODUCTS]);

        return $result[static::KEY_PRODUCTS][0];
    }
}
