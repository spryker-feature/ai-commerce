<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface;
use Spryker\Client\AiFoundation\Dependency\Tools\ToolPluginInterface;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group DisplayProductsToolPluginTest
 */
class DisplayProductsToolPluginTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_DISPLAYED_COUNT = 'displayedCount';

    protected const string KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS = 'unknownIdProductAbstracts';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected AiCommerceClientTester $tester;

    public function testGivenPublishedProductWhenExecutedThenItIsResolvedFromRealProductStorage(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idProductAbstract]]);

        // Assert
        $this->assertSame(1, $result[static::KEY_DISPLAYED_COUNT]);
        $this->assertSame($idProductAbstract, $result[static::KEY_PRODUCTS][0][static::KEY_ID_PRODUCT_ABSTRACT]);
        $this->assertNotSame('', $result[static::KEY_PRODUCTS][0]['name']);
    }

    public function testGivenUnknownProductWhenExecutedThenItIsReportedBackInsteadOfDisplayed(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $unknownIdProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$unknownIdProductAbstract]]);

        // Assert
        $this->assertSame(0, $result[static::KEY_DISPLAYED_COUNT]);
        $this->assertSame([$unknownIdProductAbstract], $result[static::KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS]);
    }

    public function testGivenNoProductsWhenExecutedThenNothingIsDisplayed(): void
    {
        // Arrange
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => []]);

        // Assert
        $this->assertSame([], $result[static::KEY_PRODUCTS]);
        $this->assertSame(0, $result[static::KEY_DISPLAYED_COUNT]);
    }

    public function testGivenThePluginWhenItsContractIsReadThenItExposesTheToolContractAiFoundationRegistersItBy(): void
    {
        // Arrange
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $parameters = $displayProductsToolPlugin->getParameters();

        // Assert
        $this->assertInstanceOf(ToolPluginInterface::class, $displayProductsToolPlugin);
        $this->assertSame(DisplayProductsToolPlugin::TOOL_NAME, $displayProductsToolPlugin->getName());
        $this->assertContainsOnlyInstancesOf(ToolParameterInterface::class, $parameters);
    }
}
