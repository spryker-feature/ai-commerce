<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Prompt;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Prompt\PageContextSystemPromptComposer;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Prompt
 * @group StorefrontAssistantSystemPromptTest
 */
class StorefrontAssistantSystemPromptTest extends Unit
{
    protected const string UNKNOWN_CATEGORY_NAME = 'A Category That Does Not Exist';

    protected const string SYSTEM_PROMPT = 'You are a shopping assistant.';

    protected AiCommerceClientTester $tester;

    public function testGivenAPublishedCatalogWhenTheSystemPromptIsComposedThenTheCategoryTreeIsLeftToTheTool(): void
    {
        // Arrange
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $categoryName = $this->tester->getCategoryNameFromStorage($idCategoryNode);

        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt();

        // Assert
        $this->assertStringNotContainsString(sprintf('%d %s', $idCategoryNode, $categoryName), $systemPrompt);
        $this->assertStringContainsString(sprintf('`%s`', CategoryTreeToolPlugin::TOOL_NAME), $systemPrompt);
    }

    public function testGivenACategoryPageWhenTheSystemPromptIsComposedThenTheBrowsedCategoryCarriesItsId(): void
    {
        // Arrange
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $categoryName = $this->tester->getCategoryNameFromStorage($idCategoryNode);

        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(
            $this->tester->createCategoryPageContext($categoryName),
        );

        // Assert
        $this->assertStringContainsString(
            sprintf('browsing the category "%s" (category id %d)', $categoryName, $idCategoryNode),
            $systemPrompt,
        );
    }

    public function testGivenACategoryNameTheCatalogDoesNotCarryWhenTheSystemPromptIsComposedThenNoIdIsInvented(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(
            $this->tester->createCategoryPageContext(static::UNKNOWN_CATEGORY_NAME),
        );

        // Assert
        $this->assertStringContainsString(
            sprintf('browsing the category "%s".', static::UNKNOWN_CATEGORY_NAME),
            $systemPrompt,
        );
        $this->assertStringNotContainsString('(category id', $systemPrompt);
    }

    public function testGivenNoPageContextWhenTheSystemPromptIsComposedThenTheCategoryStorageIsNotRead(): void
    {
        // Arrange
        $categoryTreeFormatter = $this->makeEmpty(CategoryTreeFormatterInterface::class, [
            'formatCategoryTree' => Expected::never(),
            'findIdCategoryNodeByName' => Expected::never(),
        ]);

        // Act
        $systemPrompt = (new PageContextSystemPromptComposer($categoryTreeFormatter))->composeSystemPrompt(
            static::SYSTEM_PROMPT,
            new StorefrontAssistantChatRequestTransfer(),
        );

        // Assert
        $this->assertStringStartsWith(static::SYSTEM_PROMPT, $systemPrompt);
    }
}
