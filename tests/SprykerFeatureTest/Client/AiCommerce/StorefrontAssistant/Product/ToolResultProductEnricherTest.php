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
}
