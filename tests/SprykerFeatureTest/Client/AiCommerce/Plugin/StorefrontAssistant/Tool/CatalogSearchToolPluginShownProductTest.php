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
 * @group CatalogSearchToolPluginShownProductTest
 */
class CatalogSearchToolPluginShownProductTest extends Unit
{
    protected const string PARAMETER_QUERY = 'query';

    protected const string PARAMETER_EXCLUDE_SHOWN = 'excludeShown';

    protected const string QUERY = 'laptop';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string KEY_HAS_MORE_RESULTS = 'hasMoreResults';

    protected const string KEY_SHOWN_PRODUCTS_LEFT_OUT = 'shownProductsLeftOut';

    protected const string KEY_ALL_MATCHING_PRODUCTS_SHOWN = 'allMatchingProductsShown';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const int FETCHED_PRODUCT_COUNT = 10;

    protected const int TOTAL_RESULTS = 24;

    protected const array SHOWN_PRODUCT_ABSTRACT_IDS = [1, 2, 3, 4, 5];

    protected const array UNSHOWN_PRODUCT_ABSTRACT_IDS = [6, 7, 8, 9, 10];

    protected const int EVERY_MATCH_COUNT = 5;

    protected AiCommerceClientTester $tester;

    public function testGivenProductsShownEarlierInTheConversationWhenSearchingWithExcludeShownThenOnlyUnshownProductsAreReturned(): void
    {
        // Arrange
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithProductsAndTotal(static::FETCHED_PRODUCT_COUNT, static::TOTAL_RESULTS),
        );

        // Act
        $result = $this->tester->executeCatalogSearchInConversationThatShowed(
            [static::PARAMETER_QUERY => static::QUERY, static::PARAMETER_EXCLUDE_SHOWN => true],
            static::SHOWN_PRODUCT_ABSTRACT_IDS,
        );

        // Assert
        $this->assertSame(static::UNSHOWN_PRODUCT_ABSTRACT_IDS, array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertSame(count(static::SHOWN_PRODUCT_ABSTRACT_IDS), $result[static::KEY_SHOWN_PRODUCTS_LEFT_OUT]);
        $this->assertTrue($result[static::KEY_HAS_MORE_RESULTS]);
    }

    public function testGivenEveryMatchAlreadyShownWhenSearchingWithExcludeShownThenNoProductsAreReturnedAndAllShownIsStated(): void
    {
        // Arrange
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithProductsAndTotal(static::EVERY_MATCH_COUNT, static::EVERY_MATCH_COUNT),
        );

        // Act
        $result = $this->tester->executeCatalogSearchInConversationThatShowed(
            [static::PARAMETER_QUERY => static::QUERY, static::PARAMETER_EXCLUDE_SHOWN => true],
            static::SHOWN_PRODUCT_ABSTRACT_IDS,
        );

        // Assert
        $this->assertSame([], $result[static::KEY_PRODUCTS]);
        $this->assertTrue($result[static::KEY_ALL_MATCHING_PRODUCTS_SHOWN]);
        $this->assertStringContainsString('offer exactly one way to widen the search', $result[static::KEY_SUGGESTED_NEXT_ACTION]);
    }

    public function testGivenProductsShownEarlierWhenSearchingWithoutExcludeShownThenTheyAreStillReturned(): void
    {
        // Arrange
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithProductsAndTotal(static::FETCHED_PRODUCT_COUNT, static::TOTAL_RESULTS),
        );

        // Act
        $result = $this->tester->executeCatalogSearchInConversationThatShowed(
            [static::PARAMETER_QUERY => static::QUERY],
            static::SHOWN_PRODUCT_ABSTRACT_IDS,
        );

        // Assert
        $this->assertSame(static::SHOWN_PRODUCT_ABSTRACT_IDS, array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertArrayNotHasKey(static::KEY_SHOWN_PRODUCTS_LEFT_OUT, $result);
    }
}
