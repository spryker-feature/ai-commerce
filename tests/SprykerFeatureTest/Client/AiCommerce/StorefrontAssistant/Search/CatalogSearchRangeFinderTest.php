<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Search;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchRangeFinder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchResultDataExtractor;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Search
 * @group CatalogSearchRangeFinderTest
 */
class CatalogSearchRangeFinderTest extends Unit
{
    public function testGivenPriceFacetWhenPriceRangeFoundThenBoundsAreConvertedAndFormatted(): void
    {
        // Arrange
        $facets = [
            ['name' => 'brand', 'values' => []],
            ['name' => 'price', 'min' => 1000, 'max' => 25050],
        ];

        // Act
        $range = $this->createRangeFinder()->findPriceRange($facets);

        // Assert
        $this->assertSame(
            ['min' => 10.0, 'max' => 250.5, 'minFormatted' => 'F1000', 'maxFormatted' => 'F25050'],
            $range,
        );
    }

    public function testGivenFacetConfigParameterNameWhenRatingRangeFoundThenItMatchesByConfig(): void
    {
        // Arrange
        $facets = [['name' => 'other', 'config' => ['parameter_name' => 'rating'], 'min' => 1.4, 'max' => 4.6]];

        // Act
        $range = $this->createRangeFinder()->findRatingRange($facets);

        // Assert
        $this->assertSame(['min' => 1, 'max' => 5], $range);
    }

    public function testGivenNonPositiveMaxWhenRangeFoundThenNullIsReturned(): void
    {
        // Act
        $range = $this->createRangeFinder()->findRatingRange([['name' => 'rating', 'min' => 0, 'max' => 0]]);

        // Assert
        $this->assertNull($range);
    }

    public function testGivenNoMatchingFacetOrNonArrayFacetsWhenRangeFoundThenNullIsReturned(): void
    {
        // Act & Assert
        $this->assertNull($this->createRangeFinder()->findPriceRange([['name' => 'brand']]));
        $this->assertNull($this->createRangeFinder()->findPriceRange(null));
    }

    protected function createRangeFinder(): CatalogSearchRangeFinder
    {
        $priceFormatter = $this->createMock(StorefrontAssistantPriceFormatterInterface::class);
        $priceFormatter->method('formatAmount')->willReturnCallback(static fn (int $amount): string => 'F' . $amount);

        return new CatalogSearchRangeFinder(new SearchResultDataExtractor(), $priceFormatter);
    }
}
