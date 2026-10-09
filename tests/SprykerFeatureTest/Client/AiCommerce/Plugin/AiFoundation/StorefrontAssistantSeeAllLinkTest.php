<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\AiFoundation;

use Codeception\Test\Unit;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group AiFoundation
 * @group StorefrontAssistantSeeAllLinkTest
 */
class StorefrontAssistantSeeAllLinkTest extends Unit
{
    protected const string FACET_BRAND = 'brand';

    protected const string KEY_SEE_ALL = 'seeAll';

    protected const string KEY_TOTAL_RESULTS = 'totalResults';

    protected const string KEY_QUERY_STRING = 'queryString';

    protected const string KEY_CATEGORY_URL = 'categoryUrl';

    protected const string LABEL_SALE = 'SALE %';

    protected const int ID_CATEGORY_NODE_NOTEBOOKS = 6;

    protected const string CATEGORY_URL_NOTEBOOKS = '/en/computer/notebooks';

    /**
     * @var list<int>
     */
    protected const array PRODUCT_ABSTRACT_IDS = [101, 102, 103, 104, 105];

    protected AiCommerceClientTester $tester;

    public function testGivenSearchWithMoreResultsThanDisplayedWhenProductsDisplayedThenSeeAllCarriesTotalAndSameFilters(): void
    {
        // Arrange
        $this->haveSearchInTurn(64, [
            'query' => 'camera',
            'filters' => [static::FACET_BRAND => 'Canon'],
            'priceMax' => 300,
            'sort' => 'price_asc',
            'limit' => 5,
        ]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertSame(64, $toolOutput[static::KEY_SEE_ALL][static::KEY_TOTAL_RESULTS]);
        $this->assertSame(
            ['q' => 'camera', 'price' => ['max' => '300'], 'sort' => 'price_asc', 'brand' => 'Canon'],
            $this->parseQueryString($toolOutput[static::KEY_SEE_ALL][static::KEY_QUERY_STRING]),
        );
    }

    public function testGivenSearchWithAtMostFiveResultsWhenProductsDisplayedThenNoSeeAllIsReturned(): void
    {
        // Arrange
        $this->haveSearchInTurn(5, ['query' => 'camera']);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_SEE_ALL, $toolOutput);
    }

    public function testGivenFewerProductsDisplayedThanFoundWhenTotalIsAtMostFiveThenNoSeeAllIsReturned(): void
    {
        // Arrange
        $this->haveSearchInTurn(4, ['query' => 'camera']);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput([static::PRODUCT_ABSTRACT_IDS[0]]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_SEE_ALL, $toolOutput);
    }

    public function testGivenBrowseModeLabelSearchWhenProductsDisplayedThenSeeAllHasEmptyQueryAndLabelFilter(): void
    {
        // Arrange
        $this->haveSearchInTurn(64, ['label' => [static::LABEL_SALE]]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertSame(
            ['q' => '', 'label' => [static::LABEL_SALE]],
            $this->parseQueryString($toolOutput[static::KEY_SEE_ALL][static::KEY_QUERY_STRING]),
        );
    }

    public function testGivenCategorySearchWhenProductsDisplayedThenSeeAllLinksToTheCategoryPageWithTheOtherFilters(): void
    {
        // Arrange
        $this->tester->haveCategoryStorageClientWithCategoryNodeUrls([static::ID_CATEGORY_NODE_NOTEBOOKS => static::CATEGORY_URL_NOTEBOOKS]);
        $this->haveSearchInTurn(24, ['category' => static::ID_CATEGORY_NODE_NOTEBOOKS, 'label' => [static::LABEL_SALE]]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertSame(static::CATEGORY_URL_NOTEBOOKS, $toolOutput[static::KEY_SEE_ALL][static::KEY_CATEGORY_URL]);
        $this->assertSame(
            ['label' => [static::LABEL_SALE]],
            $this->parseQueryString($toolOutput[static::KEY_SEE_ALL][static::KEY_QUERY_STRING]),
        );
    }

    public function testGivenCategoryWithoutStorefrontUrlWhenProductsDisplayedThenNoSeeAllIsReturned(): void
    {
        // Arrange
        $this->tester->haveCategoryStorageClientWithCategoryNodeUrls([]);
        $this->haveSearchInTurn(24, ['category' => static::ID_CATEGORY_NODE_NOTEBOOKS]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_SEE_ALL, $toolOutput);
    }

    public function testGivenProductsNotFoundBySearchOfTheTurnWhenProductsDisplayedThenNoSeeAllIsReturned(): void
    {
        // Arrange
        $this->haveSearchInTurn(64, ['query' => 'camera']);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput([static::PRODUCT_ABSTRACT_IDS[0], 999]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_SEE_ALL, $toolOutput);
    }

    /**
     * @param array<string, mixed> $catalogSearchArguments
     */
    protected function haveSearchInTurn(int $totalResults, array $catalogSearchArguments): void
    {
        $this->tester->setUpFilterableFacetConfigs([static::FACET_BRAND => false]);
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithRowsAndFacetCounts(static::PRODUCT_ABSTRACT_IDS, $totalResults, []),
        );
        $this->tester->executeCatalogSearchToolInNewTurn($catalogSearchArguments);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function parseQueryString(string $queryString): array
    {
        parse_str($queryString, $queryParameters);

        return $queryParameters;
    }
}
