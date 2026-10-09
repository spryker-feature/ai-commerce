<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Search;

use Codeception\Test\Unit;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Search
 * @group RefinementChipBuilderTest
 */
class RefinementChipBuilderTest extends Unit
{
    protected AiCommerceClientTester $tester;

    public function testGivenPriceRatingAndCategoryFiltersWhenActiveFiltersAreBuiltThenEachHasAReadableLabel(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $searchResult = [
            'availableRefinements' => [
                ['name' => 'category', 'filterArgument' => 'category', 'values' => [['value' => '6', 'matchCount' => 24, 'label' => 'Notebooks']]],
            ],
            'appliedFilters' => ['category' => 6, 'price' => ['max' => 100.0], 'rating' => 4, 'sort' => 'price_asc'],
        ];

        // Act
        $activeFilters = $this->tester->getFactory()->createRefinementChipBuilder()->buildActiveFilters($searchResult);

        // Assert
        $this->assertSame(
            ['Notebooks' => 'category', '≤ ' . $this->tester->formatAmountInCurrentCurrency(10000) => 'priceMax', '≥ 4★' => 'ratingMin'],
            array_column($activeFilters, 'filterArgument', 'label'),
        );
    }

    public function testGivenManyRefinementsWhenChipsAreBuiltThenAtMostTwoValuesOfEachRefinementAreOffered(): void
    {
        // Arrange
        $searchResult = [
            'totalResults' => 50,
            'availableRefinements' => [
                [
        'name' => 'brand',
        'filterArgument' => 'filters.brand',
        'values' => [
                    ['value' => 'Acer', 'matchCount' => 20], ['value' => 'Asus', 'matchCount' => 15], ['value' => 'HP', 'matchCount' => 10],
                ]],
                ['name' => 'color', 'filterArgument' => 'filters.color', 'values' => [['value' => 'Black', 'matchCount' => 5]]],
            ],
            'appliedFilters' => [],
        ];

        // Act
        $refinementChips = $this->tester->getFactory()->createRefinementChipBuilder()->buildRefinementChips($searchResult, 6);

        // Assert
        $this->assertSame(['Acer', 'Asus', 'Black'], array_column($refinementChips, 'value'));
    }
}
