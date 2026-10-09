<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Product;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReasonResolver;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Product
 * @group DisplayProductReasonResolverTest
 */
class DisplayProductReasonResolverTest extends Unit
{
    protected const string KEY_REASON = 'reason';

    protected const string SAME_NAME = 'Samsung Galaxy S6 edge';

    protected const string SAME_REASON = 'Curved display · 32 GB';

    public function testGivenSameNameCardsWithEqualReasonsWhenResolvedThenReasonsDifferByPrice(): void
    {
        // Arrange
        $products = [
            $this->createProduct(1, static::SAME_NAME, '€499.00', '001'),
            $this->createProduct(2, static::SAME_NAME, '€549.00', '002'),
        ];

        // Act
        $products = (new DisplayProductReasonResolver())->resolveReasons($products, [1 => static::SAME_REASON, 2 => static::SAME_REASON]);

        // Assert
        $this->assertSame('Curved display · 32 GB · €499.00', $products[0][static::KEY_REASON]);
        $this->assertSame('Curved display · 32 GB · €549.00', $products[1][static::KEY_REASON]);
    }

    public function testGivenSameNameCardsWithEqualReasonsAndEqualPricesWhenResolvedThenReasonsDifferBySku(): void
    {
        // Arrange
        $products = [
            $this->createProduct(1, static::SAME_NAME, '€499.00', '001'),
            $this->createProduct(2, ' samsung  galaxy s6 EDGE ', '€499.00', '002'),
        ];

        // Act
        $products = (new DisplayProductReasonResolver())->resolveReasons($products, []);

        // Assert
        $this->assertSame('€499.00 · 001', $products[0][static::KEY_REASON]);
        $this->assertSame('€499.00 · 002', $products[1][static::KEY_REASON]);
    }

    public function testGivenSameNameCardsWithDistinctReasonsWhenResolvedThenReasonsAreKept(): void
    {
        // Arrange
        $products = [
            $this->createProduct(1, static::SAME_NAME, '€499.00', '001'),
            $this->createProduct(2, static::SAME_NAME, '€499.00', '002'),
        ];

        // Act
        $products = (new DisplayProductReasonResolver())->resolveReasons($products, [1 => 'Black', 2 => 'Gold']);

        // Assert
        $this->assertSame('Black', $products[0][static::KEY_REASON]);
        $this->assertSame('Gold', $products[1][static::KEY_REASON]);
    }

    public function testGivenDifferentNameCardsWithEqualReasonsWhenResolvedThenReasonsAreKept(): void
    {
        // Arrange
        $products = [
            $this->createProduct(1, 'Canon IXUS 160', '€99.00', '001'),
            $this->createProduct(2, 'Canon IXUS 175', '€119.00', '002'),
        ];

        // Act
        $products = (new DisplayProductReasonResolver())->resolveReasons($products, [1 => 'Compact', 2 => 'Compact']);

        // Assert
        $this->assertSame('Compact', $products[0][static::KEY_REASON]);
        $this->assertSame('Compact', $products[1][static::KEY_REASON]);
    }

    public function testGivenLongEqualReasonsWhenDistinguishedThenTheDistinctionSurvivesWithinSixtyCharacters(): void
    {
        // Arrange
        $longReason = str_repeat('Curved display ', 6);
        $products = [
            $this->createProduct(1, static::SAME_NAME, '€499.00', '001'),
            $this->createProduct(2, static::SAME_NAME, '€549.00', '002'),
        ];

        // Act
        $products = (new DisplayProductReasonResolver())->resolveReasons($products, [1 => $longReason, 2 => $longReason]);

        // Assert
        $this->assertNotSame($products[0][static::KEY_REASON], $products[1][static::KEY_REASON]);
        $this->assertStringEndsWith('€499.00', $products[0][static::KEY_REASON]);
        $this->assertLessThanOrEqual(DisplayProductReasonResolver::MAX_REASON_LENGTH, mb_strlen($products[0][static::KEY_REASON]));
        $this->assertLessThanOrEqual(DisplayProductReasonResolver::MAX_REASON_LENGTH, mb_strlen($products[1][static::KEY_REASON]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function createProduct(int $idProductAbstract, string $name, string $priceFormatted, string $sku): array
    {
        return [
            'idProductAbstract' => $idProductAbstract,
            'name' => $name,
            'priceFormatted' => $priceFormatted,
            'sku' => $sku,
        ];
    }
}
