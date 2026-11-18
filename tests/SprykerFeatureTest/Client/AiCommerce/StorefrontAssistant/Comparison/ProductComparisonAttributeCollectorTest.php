<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Comparison;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ProductComparisonAttributeCollector;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductAttributeDetailsExpander;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Comparison
 * @group ProductComparisonAttributeCollectorTest
 */
class ProductComparisonAttributeCollectorTest extends Unit
{
    public function testGivenAttributesAndSuperAttributesWhenCollectedThenSuperAttributesFillMissingKeysOnly(): void
    {
        // Arrange
        $productDetails = [
            StorefrontAssistantProductMapper::KEY_ATTRIBUTES => ['brand' => 'Acme', 'color' => 'red', 'weight' => 5],
            ProductAttributeDetailsExpander::KEY_VARIANTS => [
                ProductAttributeDetailsExpander::KEY_SUPER_ATTRIBUTES => [
                    'color' => ['blue', 'green'],
                    'size' => ['S', 'M'],
                    'empty' => [],
                ],
            ],
        ];

        // Act
        $attributes = (new ProductComparisonAttributeCollector())->collectAttributes($productDetails);

        // Assert
        $this->assertSame(['brand' => 'Acme', 'color' => 'red', 'weight' => '5', 'size' => 'S / M'], $attributes);
    }

    public function testGivenNoAttributesWhenCollectedThenEmptyArrayIsReturned(): void
    {
        // Act
        $attributes = (new ProductComparisonAttributeCollector())->collectAttributes([]);

        // Assert
        $this->assertSame([], $attributes);
    }

    public function testGivenSeveralAttributeListsWhenKeysCollectedThenKeysAreUniqueInFirstSeenOrder(): void
    {
        // Act
        $attributeKeys = (new ProductComparisonAttributeCollector())->collectAttributeKeys([
            ['brand' => 'A', 'color' => 'red'],
            ['color' => 'blue', 'size' => 'M'],
        ]);

        // Assert
        $this->assertSame(['brand', 'color', 'size'], $attributeKeys);
    }
}
