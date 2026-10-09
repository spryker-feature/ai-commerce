<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\ProductRelationReader;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group ProductRelationsToolPluginTest
 */
class ProductRelationsToolPluginTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string PARAMETER_TYPE = 'type';

    protected const string TYPE_SIMILAR = 'similar';

    protected const string TYPE_ACCESSORIES = 'accessories';

    protected const string TYPE_ALTERNATIVES = 'alternatives';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string KEY_SOURCE = 'source';

    protected const string KEY_RELATION_TYPE = 'relationType';

    protected const string KEY_TOTAL_COUNT = 'totalCount';

    protected const string KEY_CATEGORY = 'category';

    protected const string KEY_CATEGORY_NODE_ID = 'categoryNodeId';

    protected const string KEY_ERROR = 'error';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string SOURCE_CURATED = 'curated';

    protected const string SOURCE_CATEGORY_FALLBACK = 'category_fallback';

    protected const string SOURCE_NONE = 'none';

    protected const string REQUEST_PARAMETER_CATEGORY = 'category';

    protected const string DISCONTINUED_CONCRETE_SKU = 'relation-discontinued-1';

    protected const int CATEGORY_NODE_ID = 4;

    protected const string CATEGORY_NAME = 'Digital Cameras';

    protected const int MAX_PRODUCTS = 10;

    protected const array VIEWED_CATEGORY_NODE_IDS = [10, 9];

    protected const int VIEWED_PARENT_CATEGORY_NODE_ID = 9;

    protected const array OTHER_CATEGORY_NODE_IDS = [4, 2];

    protected AiCommerceClientTester $tester;

    public function testGivenCuratedRelatedProductsWhenSimilarRequestedThenOnlyRelatedProductsAreReturned(): void
    {
        // Arrange
        [$idProductAbstract, $relatedProductAbstractIds] = $this->haveViewedProductWithRelations(3, 2);

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_SIMILAR));

        // Assert
        $this->assertSame(static::SOURCE_CURATED, $result[static::KEY_SOURCE]);
        $this->assertSame($relatedProductAbstractIds, array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertSame(3, $result[static::KEY_TOTAL_COUNT]);
    }

    public function testGivenUpSellingRelationWhenAccessoriesRequestedThenUpSellingProductsAreReturned(): void
    {
        // Arrange
        [$idProductAbstract, , $upSellingProductAbstractIds] = $this->haveViewedProductWithRelations(3, 2);

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_ACCESSORIES));

        // Assert
        $this->assertSame(static::TYPE_ACCESSORIES, $result[static::KEY_RELATION_TYPE]);
        $this->assertSame(static::SOURCE_CURATED, $result[static::KEY_SOURCE]);
        $this->assertSame($upSellingProductAbstractIds, array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
    }

    public function testGivenDiscontinuedProductWithAlternativeWhenAlternativesRequestedThenAlternativeIsReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->tester->haveProductsInProductStorage([$idProductAbstract, $idProductAbstract + 1], [static::DISCONTINUED_CONCRETE_SKU => $idProductAbstract]);
        $this->tester->haveProductAlternativeStorageClientReturning([static::DISCONTINUED_CONCRETE_SKU => [$idProductAbstract + 1, $idProductAbstract]]);

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_ALTERNATIVES));

        // Assert
        $this->assertSame(static::SOURCE_CURATED, $result[static::KEY_SOURCE]);
        $this->assertSame([$idProductAbstract + 1], array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
    }

    public function testGivenNoCuratedRelationsWhenSimilarRequestedThenCategoryIsSearchedAndViewedProductExcluded(): void
    {
        // Arrange
        [$idProductAbstract] = $this->haveViewedProductWithRelations(0, 0, 2);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters(
            $this->tester->createCatalogSearchResultWithRows([$idProductAbstract, $idProductAbstract + 1, $idProductAbstract + 2]),
            $capturedRequestParameters,
        );

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_SIMILAR));

        // Assert
        $this->assertSame(static::SOURCE_CATEGORY_FALLBACK, $result[static::KEY_SOURCE]);
        $this->assertEquals(static::CATEGORY_NODE_ID, $capturedRequestParameters[static::REQUEST_PARAMETER_CATEGORY]);
        $this->assertSame(static::CATEGORY_NODE_ID, $result[static::KEY_CATEGORY][static::KEY_CATEGORY_NODE_ID]);
        $this->assertSame([$idProductAbstract + 1, $idProductAbstract + 2], array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
    }

    public function testGivenNoCuratedAccessoriesWhenAccessoriesRequestedThenNothingIsReturnedWithoutCategorySearch(): void
    {
        // Arrange
        [$idProductAbstract] = $this->haveViewedProductWithRelations(2, 0, 2);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters(
            $this->tester->createCatalogSearchResultWithRows([$idProductAbstract + 1]),
            $capturedRequestParameters,
        );

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_ACCESSORIES));

        // Assert
        $this->assertSame(static::SOURCE_NONE, $result[static::KEY_SOURCE]);
        $this->assertSame([], $result[static::KEY_PRODUCTS]);
        $this->assertArrayHasKey(static::KEY_SUGGESTED_NEXT_ACTION, $result);
        $this->assertSame([], $capturedRequestParameters);
    }

    public function testGivenRelationsWhenRequestedThenViewedProductIsNeverReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->tester->haveProductsInProductStorage([$idProductAbstract, $idProductAbstract + 1]);
        $this->tester->haveProductRelationStorageClientReturning([$idProductAbstract, $idProductAbstract + 1]);

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_SIMILAR));

        // Assert
        $this->assertSame([$idProductAbstract + 1], array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertSame(1, $result[static::KEY_TOTAL_COUNT]);
    }

    public function testGivenInvalidTypeWhenRequestedThenAnErrorListsTheValidTypes(): void
    {
        // Arrange
        [$idProductAbstract] = $this->haveViewedProductWithRelations(1, 1);

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, 'cheaper'));

        // Assert
        $this->assertStringContainsString(
            sprintf('%s, %s, %s', static::TYPE_SIMILAR, static::TYPE_ACCESSORIES, static::TYPE_ALTERNATIVES),
            $result[static::KEY_ERROR],
        );
        $this->assertArrayNotHasKey(static::KEY_PRODUCTS, $result);
    }

    public function testGivenMoreThanTenRelationsWhenRequestedThenAtMostTenRowsAreReturned(): void
    {
        // Arrange
        [$idProductAbstract, $relatedProductAbstractIds] = $this->haveViewedProductWithRelations(12, 0);

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_SIMILAR));

        // Assert
        $this->assertSame(array_slice($relatedProductAbstractIds, 0, static::MAX_PRODUCTS), array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertSame(12, $result[static::KEY_TOTAL_COUNT]);
    }

    public function testGivenEnoughSimilarRelationsInTheViewedCategoryWhenSimilarRequestedThenOnlyThoseAreReturned(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $sameCategoryProductAbstractIds = range($idProductAbstract + 2, $idProductAbstract + 7);
        $this->tester->haveProductsInProductStorage(range($idProductAbstract, $idProductAbstract + 8));
        $this->tester->haveProductRelationStorageClientReturning(range($idProductAbstract + 1, $idProductAbstract + 8));
        $this->tester->haveProductCategoryStorageClientReturningCategoryNodeIds([
            $idProductAbstract => static::VIEWED_CATEGORY_NODE_IDS,
            $idProductAbstract + 1 => static::OTHER_CATEGORY_NODE_IDS,
            $idProductAbstract + 8 => static::OTHER_CATEGORY_NODE_IDS,
        ] + array_fill_keys($sameCategoryProductAbstractIds, [static::VIEWED_PARENT_CATEGORY_NODE_ID]));

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_SIMILAR));

        // Assert
        $this->assertSame(static::SOURCE_CURATED, $result[static::KEY_SOURCE]);
        $this->assertSame($sameCategoryProductAbstractIds, array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertSame(count($sameCategoryProductAbstractIds), $result[static::KEY_TOTAL_COUNT]);
    }

    public function testGivenFewSimilarRelationsInTheViewedCategoryWhenSimilarRequestedThenTheyLeadAndOtherRelationsFollow(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $this->tester->haveProductsInProductStorage(range($idProductAbstract, $idProductAbstract + 4));
        $this->tester->haveProductRelationStorageClientReturning(range($idProductAbstract + 1, $idProductAbstract + 4));
        $this->tester->haveProductCategoryStorageClientReturningCategoryNodeIds([
            $idProductAbstract => static::VIEWED_CATEGORY_NODE_IDS,
            $idProductAbstract + 1 => static::OTHER_CATEGORY_NODE_IDS,
            $idProductAbstract + 2 => static::OTHER_CATEGORY_NODE_IDS,
            $idProductAbstract + 3 => static::VIEWED_CATEGORY_NODE_IDS,
        ]);

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, static::TYPE_SIMILAR));

        // Assert
        $this->assertSame(
            [$idProductAbstract + 3, $idProductAbstract + 1, $idProductAbstract + 2, $idProductAbstract + 4],
            array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT),
        );
        $this->assertSame(4, $result[static::KEY_TOTAL_COUNT]);
    }

    /**
     * @dataProvider provideSupportedRelationTypes
     */
    public function testGivenSupportedTypeWhenRequestedThenRelationsAreReadWithoutError(string $relationType): void
    {
        // Arrange
        [$idProductAbstract] = $this->haveViewedProductWithRelations(1, 1);
        $this->tester->haveProductAlternativeStorageClientReturning([]);
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRows([$idProductAbstract + 1]));

        // Act
        $result = $this->tester->executeProductRelationsTool($this->createArguments($idProductAbstract, $relationType));

        // Assert
        $this->assertArrayNotHasKey(static::KEY_ERROR, $result);
        $this->assertSame($relationType, $result[static::KEY_RELATION_TYPE]);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideSupportedRelationTypes(): iterable
    {
        foreach (ProductRelationReader::TYPES as $relationType) {
            yield $relationType => [$relationType];
        }
    }

    /**
     * @return array{0: int, 1: list<int>, 2: list<int>}
     */
    protected function haveViewedProductWithRelations(int $relatedCount, int $upSellingCount, int $categoryProductCount = 0): array
    {
        $idProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $relatedProductAbstractIds = $relatedCount > 0 ? range($idProductAbstract + 1, $idProductAbstract + $relatedCount) : [];
        $upSellingProductAbstractIds = $upSellingCount > 0 ? range($idProductAbstract + $relatedCount + 1, $idProductAbstract + $relatedCount + $upSellingCount) : [];
        $productCount = max($relatedCount + $upSellingCount, $categoryProductCount);

        $this->tester->haveProductsInProductStorage(range($idProductAbstract, $idProductAbstract + $productCount));
        $this->tester->haveProductRelationStorageClientReturning($relatedProductAbstractIds, $upSellingProductAbstractIds);
        $this->tester->haveProductCategoryStorageClientReturning($idProductAbstract, static::CATEGORY_NODE_ID, static::CATEGORY_NAME);

        return [$idProductAbstract, $relatedProductAbstractIds, $upSellingProductAbstractIds];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createArguments(int $idProductAbstract, string $type): array
    {
        return [
            static::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            static::PARAMETER_TYPE => $type,
        ];
    }
}
