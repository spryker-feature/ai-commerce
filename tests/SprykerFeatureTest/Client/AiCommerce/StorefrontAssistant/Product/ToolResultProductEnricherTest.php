<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Product;

use Codeception\Stub;
use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductEnricher;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Product
 * @group ToolResultProductEnricherTest
 */
class ToolResultProductEnricherTest extends Unit
{
    protected const string TOOL_RESULT = '{"products":[{"idProductAbstract":1,"name":"Camera"}]}';

    protected const string STORAGE_NAME_WITH_INVALID_UTF8 = "Caf\xB1";

    protected const string COMPARISON_RESULT = '{"comparedProducts":[{"idProductAbstract":9,"name":"Canon IXUS 285","sku":"009","isAvailable":true},{"idProductAbstract":20,"name":"Sony W830","sku":"020"}],"attributeRows":[{"attribute":"Zoom"}]}';

    public function testGivenAStoredProductNameWithInvalidUtf8WhenTheToolResultIsEnrichedThenTheEnrichedResultIsStillReturned(): void
    {
        // Arrange
        $toolResultProductEnricher = new ToolResultProductEnricher(
            Stub::makeEmpty(ToolResultProductExpanderInterface::class, [
                'expandProducts' => [['idProductAbstract' => 1, 'name' => static::STORAGE_NAME_WITH_INVALID_UTF8]],
            ]),
        );

        // Act
        $enrichedToolResult = $toolResultProductEnricher->enrich(static::TOOL_RESULT);

        // Assert
        $this->assertNotSame(static::TOOL_RESULT, $enrichedToolResult);
        $this->assertSame("Caf\u{FFFD}", json_decode($enrichedToolResult, true)['products'][0]['name']);
    }

    /**
     * The model reads the comparison without links and images; the customer-facing copy gets them from storage, while
     * every field the comparison already has stays as the model saw it.
     */
    public function testGivenAComparisonWhenTheToolResultIsEnrichedThenEachComparedProductGainsOnlyItsLinkAndImage(): void
    {
        // Arrange
        $toolResultProductEnricher = new ToolResultProductEnricher(
            Stub::makeEmpty(ToolResultProductExpanderInterface::class, [
                'expandProducts' => [
                    ['idProductAbstract' => 9, 'name' => 'Storage name', 'url' => '/en/canon-ixus-285-9', 'imageUrl' => 'https://images.example/9.jpg'],
                    ['idProductAbstract' => 20, 'name' => 'Storage name', 'url' => '/en/sony-w830-20', 'imageUrl' => 'https://images.example/20.jpg'],
                ],
            ]),
        );

        // Act
        $enrichedToolResult = json_decode($toolResultProductEnricher->enrich(static::COMPARISON_RESULT), true);

        // Assert
        $this->assertSame([
            ['idProductAbstract' => 9, 'name' => 'Canon IXUS 285', 'sku' => '009', 'isAvailable' => true, 'url' => '/en/canon-ixus-285-9', 'imageUrl' => 'https://images.example/9.jpg'],
            ['idProductAbstract' => 20, 'name' => 'Sony W830', 'sku' => '020', 'url' => '/en/sony-w830-20', 'imageUrl' => 'https://images.example/20.jpg'],
        ], $enrichedToolResult['comparedProducts']);
        $this->assertSame([['attribute' => 'Zoom']], $enrichedToolResult['attributeRows']);
    }

    public function testGivenAComparedProductMissingFromStorageWhenTheToolResultIsEnrichedThenItKeepsItsFieldsWithoutLinkAndImage(): void
    {
        // Arrange
        $toolResultProductEnricher = new ToolResultProductEnricher(
            Stub::makeEmpty(ToolResultProductExpanderInterface::class, [
                'expandProducts' => [['idProductAbstract' => 9, 'url' => '/en/canon-ixus-285-9', 'imageUrl' => 'https://images.example/9.jpg']],
            ]),
        );

        // Act
        $enrichedToolResult = json_decode($toolResultProductEnricher->enrich(static::COMPARISON_RESULT), true);

        // Assert
        $this->assertSame(
            ['idProductAbstract' => 20, 'name' => 'Sony W830', 'sku' => '020'],
            $enrichedToolResult['comparedProducts'][1],
        );
    }
}
