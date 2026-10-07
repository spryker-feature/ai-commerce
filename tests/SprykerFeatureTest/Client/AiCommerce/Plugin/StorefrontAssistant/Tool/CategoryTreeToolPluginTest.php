<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use Spryker\Client\AiFoundation\Dependency\Tools\ToolPluginInterface;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group CategoryTreeToolPluginTest
 */
class CategoryTreeToolPluginTest extends Unit
{
    protected const string KEY_CATEGORIES = 'categories';

    protected AiCommerceClientTester $tester;

    public function testGivenAPublishedCatalogWhenExecutedThenEveryCategoryCarriesItsIdAndName(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $categoryTreeToolPlugin = new CategoryTreeToolPlugin();

        // Act
        $result = $categoryTreeToolPlugin->execute();

        // Assert
        $this->assertStringContainsString(
            sprintf('%d %s', $idCategoryNode, $this->tester->getCategoryNameFromStorage($idCategoryNode)),
            $result[static::KEY_CATEGORIES],
        );
    }

    public function testGivenANestedCatalogWhenExecutedThenChildCategoriesAreIndentedBelowTheirParent(): void
    {
        // Arrange
        $this->tester->ensureNestedCategoryTreeIsPublished();
        $categoryTreeToolPlugin = new CategoryTreeToolPlugin();

        // Act
        $result = $categoryTreeToolPlugin->execute();

        // Assert
        $this->assertMatchesRegularExpression('/\n {2}\d+ \S/', $result[static::KEY_CATEGORIES]);
    }

    public function testGivenThePluginWhenItsContractIsReadThenItExposesTheToolContractAiFoundationRegistersItBy(): void
    {
        // Arrange
        $categoryTreeToolPlugin = new CategoryTreeToolPlugin();

        // Act
        $parameters = $categoryTreeToolPlugin->getParameters();

        // Assert
        $this->assertInstanceOf(ToolPluginInterface::class, $categoryTreeToolPlugin);
        $this->assertSame(CategoryTreeToolPlugin::TOOL_NAME, $categoryTreeToolPlugin->getName());
        $this->assertSame([], $parameters);
    }
}
