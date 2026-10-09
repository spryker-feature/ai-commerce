<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group ProductDiscoveryAgentPluginTest
 */
class ProductDiscoveryAgentPluginTest extends Unit
{
    protected const int ID_CATEGORY_NODE_CAMERAS = 2;

    protected const int ID_CATEGORY_NODE_DIGITAL_CAMERAS = 4;

    protected const string CATEGORY_NAME_CAMERAS = 'Cameras & Camcorders';

    protected const string CATEGORY_NAME_DIGITAL_CAMERAS = 'Digital Cameras';

    protected const string CUSTOMER_REFERENCE = 'DE--test-customer-product-discovery-agent';

    protected const string CONVERSATION_REFERENCE = 'test-conversation-product-discovery-agent';

    protected const string INLINE_CATEGORY_TREE_HEADLINE = 'Categories this shop carries';

    /**
     * @var list<string>
     */
    protected const array PROJECT_TOOL_NAMES = [
        CatalogSearchToolPlugin::TOOL_NAME,
        CategoryTreeToolPlugin::TOOL_NAME,
        DisplayProductsToolPlugin::TOOL_NAME,
    ];

    protected AiCommerceClientTester $tester;

    public function testGivenSmallCatalogWhenAgentRunsThenCategoryTreeToolIsNotOfferedAndTreeIsInPrompt(): void
    {
        // Arrange
        $this->haveCategoryTree();
        $this->haveProjectToolList();

        // Act
        $promptRequestTransfer = $this->tester->executeProductDiscoveryAgentAndGetPromptRequest($this->createChatRequest());

        // Assert
        $this->assertNotContains(CategoryTreeToolPlugin::TOOL_NAME, $promptRequestTransfer->getToolNames());
        $this->assertContains(CatalogSearchToolPlugin::TOOL_NAME, $promptRequestTransfer->getToolNames());
        $this->assertStringContainsString(static::INLINE_CATEGORY_TREE_HEADLINE, (string)$promptRequestTransfer->getSystemPrompt());
        $this->assertStringContainsString(
            sprintf("%d %s\n  %d %s", static::ID_CATEGORY_NODE_CAMERAS, static::CATEGORY_NAME_CAMERAS, static::ID_CATEGORY_NODE_DIGITAL_CAMERAS, static::CATEGORY_NAME_DIGITAL_CAMERAS),
            (string)$promptRequestTransfer->getSystemPrompt(),
        );
    }

    public function testGivenCatalogAboveInlineLimitWhenAgentRunsThenCategoryTreeToolIsOffered(): void
    {
        // Arrange
        $this->haveCategoryTree();
        $this->haveProjectToolList();
        $this->tester->mockConfigMethod('getStorefrontAssistantInlineCategoryTreeLimit', 0);

        // Act
        $promptRequestTransfer = $this->tester->executeProductDiscoveryAgentAndGetPromptRequest($this->createChatRequest());

        // Assert
        $this->assertContains(CategoryTreeToolPlugin::TOOL_NAME, $promptRequestTransfer->getToolNames());
        $this->assertStringNotContainsString(static::INLINE_CATEGORY_TREE_HEADLINE, (string)$promptRequestTransfer->getSystemPrompt());
        $this->assertStringContainsString(sprintf('call `%s`', CategoryTreeToolPlugin::TOOL_NAME), (string)$promptRequestTransfer->getSystemPrompt());
    }

    public function testGivenCategoryPageWhenAgentRunsThenBrowsedCategoryIdIsInPromptAndCategoryTreeToolIsNotOffered(): void
    {
        // Arrange
        $this->haveCategoryTree();
        $this->haveProjectToolList();
        $storefrontAssistantChatRequestTransfer = $this->createChatRequest()->setStorefrontAssistantPageContext(
            $this->tester->createCategoryPageContext(static::CATEGORY_NAME_DIGITAL_CAMERAS),
        );

        // Act
        $promptRequestTransfer = $this->tester->executeProductDiscoveryAgentAndGetPromptRequest($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertNotContains(CategoryTreeToolPlugin::TOOL_NAME, $promptRequestTransfer->getToolNames());
        $this->assertStringContainsString(
            sprintf('(category id %1$d). To list or recommend its products, pass %1$d as the `%2$s` `category` argument', static::ID_CATEGORY_NODE_DIGITAL_CAMERAS, CatalogSearchToolPlugin::TOOL_NAME),
            (string)$promptRequestTransfer->getSystemPrompt(),
        );
    }

    public function testGivenProjectToolListWhenAgentRunsThenOnlyConfiguredToolsAreOffered(): void
    {
        // Arrange
        $this->haveCategoryTree();
        $this->tester->mockConfigMethod('getProductDiscoveryAgentToolNames', [
            CatalogSearchToolPlugin::TOOL_NAME,
            DisplayProductsToolPlugin::TOOL_NAME,
        ]);

        // Act
        $promptRequestTransfer = $this->tester->executeProductDiscoveryAgentAndGetPromptRequest($this->createChatRequest());

        // Assert
        $this->assertSame(
            [CatalogSearchToolPlugin::TOOL_NAME, DisplayProductsToolPlugin::TOOL_NAME],
            $promptRequestTransfer->getToolNames(),
        );
    }

    public function testGivenProjectListingNoToolsWhenAgentRunsThenNoToolIsOffered(): void
    {
        // Arrange
        $this->haveCategoryTree();
        $this->tester->mockConfigMethod('getProductDiscoveryAgentToolNames', []);

        // Act
        $promptRequestTransfer = $this->tester->executeProductDiscoveryAgentAndGetPromptRequest($this->createChatRequest());

        // Assert
        $this->assertSame([], $promptRequestTransfer->getToolNames());
    }

    protected function haveProjectToolList(): void
    {
        $this->tester->mockConfigMethod('getProductDiscoveryAgentToolNames', static::PROJECT_TOOL_NAMES);
    }

    protected function haveCategoryTree(): void
    {
        $this->tester->haveCategoryStorageClientWithCategoryTree([
            $this->tester->createCategoryNodeStorage(static::ID_CATEGORY_NODE_CAMERAS, static::CATEGORY_NAME_CAMERAS, [
                $this->tester->createCategoryNodeStorage(static::ID_CATEGORY_NODE_DIGITAL_CAMERAS, static::CATEGORY_NAME_DIGITAL_CAMERAS),
            ]),
        ]);
    }

    protected function createChatRequest(): StorefrontAssistantChatRequestTransfer
    {
        return $this->tester->createStorefrontAssistantChatRequest(static::CUSTOMER_REFERENCE, static::CONVERSATION_REFERENCE);
    }
}
