<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Product;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\PriceOutlierMarker;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Product
 * @group PriceOutlierMarkerTest
 */
class PriceOutlierMarkerTest extends Unit
{
    protected const string KEY_IS_PRICE_OUTLIER = 'isPriceOutlier';

    protected const string SAME_NAME = 'Sony Cyber-shot DSC-W800';

    protected const float FACTOR = 5.0;

    public function testGivenTwoSameNameListingsAtEightyFiveAndThreeThousandWhenMarkedThenOnlyTheExpensiveOneIsFlagged(): void
    {
        // Arrange
        $products = [
            $this->createProduct(16, static::SAME_NAME, 85.0),
            $this->createProduct(17, ' sony  cyber-shot DSC-W800 ', 3111.3),
        ];

        // Act
        $products = (new PriceOutlierMarker(static::FACTOR))->markPriceOutliers($products);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_IS_PRICE_OUTLIER, $products[0]);
        $this->assertTrue($products[1][static::KEY_IS_PRICE_OUTLIER]);
    }

    public function testGivenThreeSameNameListingsWhenOneCostsMoreThanFiveTimesTheMedianOfTheOthersThenOnlyItIsFlagged(): void
    {
        // Arrange
        $products = [
            $this->createProduct(1, static::SAME_NAME, 8500),
            $this->createProduct(2, static::SAME_NAME, 9000),
            $this->createProduct(3, static::SAME_NAME, 311130),
            $this->createProduct(4, 'Canon IXUS 175', 311130),
        ];

        // Act
        $products = (new PriceOutlierMarker(static::FACTOR))->markPriceOutliers($products);

        // Assert
        $this->assertSame([3], $this->getFlaggedIds($products));
    }

    public function testGivenSingleListingWhenMarkedThenNothingIsFlagged(): void
    {
        // Arrange
        $products = [
            $this->createProduct(17, static::SAME_NAME, 3111.3),
            $this->createProduct(4, 'Canon IXUS 175', 63.0),
        ];

        // Act
        $products = (new PriceOutlierMarker(static::FACTOR))->markPriceOutliers($products);

        // Assert
        $this->assertSame([], $this->getFlaggedIds($products));
    }

    public function testGivenPricesWithinFactorWhenMarkedThenNothingIsFlagged(): void
    {
        // Arrange
        $products = [
            $this->createProduct(16, static::SAME_NAME, 85.0),
            $this->createProduct(17, static::SAME_NAME, 425.0),
        ];

        // Act
        $products = (new PriceOutlierMarker(static::FACTOR))->markPriceOutliers($products);

        // Assert
        $this->assertSame([], $this->getFlaggedIds($products));
    }

    public function testGivenMissingPriceWhenMarkedThenRowIsSkipped(): void
    {
        // Arrange
        $products = [
            $this->createProduct(16, static::SAME_NAME, null),
            $this->createProduct(17, static::SAME_NAME, 3111.3),
            $this->createProduct(18, static::SAME_NAME, 0),
        ];

        // Act
        $products = (new PriceOutlierMarker(static::FACTOR))->markPriceOutliers($products);

        // Assert
        $this->assertSame([], $this->getFlaggedIds($products));
    }

    /**
     * @return array<string, mixed>
     */
    protected function createProduct(int $idProductAbstract, string $name, int|float|null $price): array
    {
        return [
            'idProductAbstract' => $idProductAbstract,
            'name' => $name,
            'price' => $price,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return list<int>
     */
    protected function getFlaggedIds(array $products): array
    {
        $flaggedIds = [];

        foreach ($products as $product) {
            if (($product[static::KEY_IS_PRICE_OUTLIER] ?? false) === true) {
                $flaggedIds[] = $product['idProductAbstract'];
            }
        }

        return $flaggedIds;
    }
}
