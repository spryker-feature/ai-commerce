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
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductDetailsToolPlugin;
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

    protected const string LOCALE_NAME = 'en_US';

    protected const int ID_PRODUCT_ABSTRACT = 5;

    protected const string PRODUCT_SKU = '005';

    protected const string PRODUCT_NAME = 'Canon IXUS 175';

    protected const string LABEL_DISCONTINUED = 'Discontinued';

    protected const string SEARCH_QUERY = 'samsung';

    protected const string FILTER_BRAND_SAMSUNG = 'brand: Samsung';

    protected const string INLINE_CATEGORY_TREE = "2 Cameras & Camcorders\n  4 Digital Cameras\n5 Computer";

    protected const string CUSTOMER_REFERENCE = 'DE--test-customer-page-context';

    protected const string CONVERSATION_REFERENCE = 'test-conversation-page-context';

    protected AiCommerceClientTester $tester;

    public function testGivenInlineCategoryTreeWhenComposedThenTreeIsInlinedAndToolLookupIsNotSuggested(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(null, static::INLINE_CATEGORY_TREE);

        // Assert
        $this->assertStringContainsString(static::INLINE_CATEGORY_TREE, $systemPrompt);
        $this->assertStringContainsString('the complete list', $systemPrompt);
        $this->assertStringContainsString('say plainly that the shop does not sell it', $systemPrompt);
        $this->assertStringNotContainsString(sprintf('`%s`', CategoryTreeToolPlugin::TOOL_NAME), $systemPrompt);
    }

    public function testGivenAnyRequestWhenComposedThenModelLookupAndNoPriceSortRulesArePresent(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(null, static::INLINE_CATEGORY_TREE);

        // Assert
        $this->assertStringContainsString('recognised in a photo they attached', $systemPrompt);
        $this->assertStringContainsString('is its brand and model and nothing else, such as "Samsung Galaxy S5 mini"', $systemPrompt);
        $this->assertStringContainsString('Never `sort` a model lookup', $systemPrompt);
        $this->assertStringContainsString('pass it as a filter in the first call', $systemPrompt);
        $this->assertStringContainsString('`filters` {"brand": "Sony"}', $systemPrompt);
        $this->assertStringContainsString('browse that category with `category` and leave `query` out', $systemPrompt);
    }

    public function testGivenAnyRequestWhenComposedThenShowMoreGuidanceIsPresent(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(null, static::INLINE_CATEGORY_TREE);

        // Assert
        $this->assertStringContainsString('"Show me more"', $systemPrompt);
        $this->assertStringContainsString('`excludeShown` true', $systemPrompt);
        $this->assertStringContainsString('say that all matching products have been shown and offer exactly one way to widen the search', $systemPrompt);
    }

    public function testGivenAnyRequestWhenComposedThenRefinementAndOffTopicRulesArePresent(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(null, static::INLINE_CATEGORY_TREE);

        // Assert
        $this->assertStringContainsString(
            sprintf('Before asking the customer to narrow down, or when they ask for help narrowing, run one `%s`', CatalogSearchToolPlugin::TOOL_NAME),
            $systemPrompt,
        );
        $this->assertStringContainsString('name at least three values from its `availableRefinements`, each with its `matchCount`', $systemPrompt);
        $this->assertStringContainsString('Never ask an open question such as "which brand do you prefer?"', $systemPrompt);
        $this->assertStringContainsString('politely decline in one short reply without calling any tool', $systemPrompt);
    }

    public function testGivenSearchPageWithFiltersWhenComposedThenNarrowingAsksForThreeValuesWithCounts(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(
            $this->tester->createSearchPageContext(static::SEARCH_QUERY, [static::FILTER_BRAND_SAMSUNG]),
        );

        // Assert
        $this->assertStringContainsString('name at least three values from those refinements with the `matchCount` of each', $systemPrompt);
        $this->assertStringNotContainsString('offer at least two of those refinements', $systemPrompt);
    }

    public function testGivenNoInlineCategoryTreeWhenComposedThenCategoryTreeToolIsNamed(): void
    {
        // Arrange
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $categoryName = $this->tester->getCategoryNameFromStorage($idCategoryNode);

        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt();

        // Assert
        $this->assertStringNotContainsString(sprintf('%d %s', $idCategoryNode, $categoryName), $systemPrompt);
        $this->assertStringNotContainsString('the complete list', $systemPrompt);
        $this->assertStringContainsString(sprintf('call `%s`', CategoryTreeToolPlugin::TOOL_NAME), $systemPrompt);
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
        $this->assertTrue(str_starts_with($systemPrompt, static::SYSTEM_PROMPT));
    }

    public function testGivenAStoreLocaleWhenComposedThenTheAgentSearchesInTheCatalogLanguageAndRepliesInTheCustomersLanguage(): void
    {
        // Arrange
        $storefrontAssistantChatRequestTransfer = (new StorefrontAssistantChatRequestTransfer())->setLocaleName(static::LOCALE_NAME);

        // Act
        $systemPrompt = (new PageContextSystemPromptComposer($this->makeEmpty(CategoryTreeFormatterInterface::class)))->composeSystemPrompt(
            static::SYSTEM_PROMPT,
            $storefrontAssistantChatRequestTransfer,
        );

        // Assert
        $this->assertStringContainsString(
            sprintf('Catalog language: %s. Every `catalog_search` and `catalog_suggest` argument is in it: translate the customer\'s words first', static::LOCALE_NAME),
            $systemPrompt,
        );
    }

    public function testGivenResolvedProductPageWhenComposedThenIdProductAbstractAndLabelsAreStated(): void
    {
        // Arrange
        $storefrontAssistantPageContextTransfer = $this->tester->createProductPageContext(static::PRODUCT_NAME, static::PRODUCT_SKU)
            ->setIdProductAbstract(static::ID_PRODUCT_ABSTRACT)
            ->setProductLabels([static::LABEL_DISCONTINUED]);

        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt($storefrontAssistantPageContextTransfer);

        // Assert
        $this->assertStringContainsString(
            sprintf('viewing the product "%s" (SKU "%s", idProductAbstract %d).', static::PRODUCT_NAME, static::PRODUCT_SKU, static::ID_PRODUCT_ABSTRACT),
            $systemPrompt,
        );
        $this->assertStringContainsString(sprintf('Its labels: "%s".', static::LABEL_DISCONTINUED), $systemPrompt);
    }

    public function testGivenProductPageWhenComposedThenProductPageGuidanceForbidsSearchingAndRedisplaying(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(
            $this->tester->createProductPageContext(static::PRODUCT_NAME, static::PRODUCT_SKU),
        );

        // Assert
        $this->assertStringContainsString('Never search the catalog to identify it', $systemPrompt);
        $this->assertStringContainsString(sprintf('Do not show it back with `%s`', DisplayProductsToolPlugin::TOOL_NAME), $systemPrompt);
        $this->assertStringContainsString('must never be called available', $systemPrompt);
    }

    public function testGivenProductPageWhenComposedThenProductFactsAreReadWithProductDetails(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(
            $this->tester->createProductPageContext(static::PRODUCT_NAME, static::PRODUCT_SKU),
        );

        // Assert
        $this->assertStringContainsString(
            sprintf('with `%s` and that idProductAbstract, never with `%s`', ProductDetailsToolPlugin::TOOL_NAME, CatalogSearchToolPlugin::TOOL_NAME),
            $systemPrompt,
        );
    }

    public function testGivenProductPageWhenComposedThenCheaperAlternativesAreSearchedBelowThePriceReadWithProductDetails(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(
            $this->tester->createProductPageContext(static::PRODUCT_NAME, static::PRODUCT_SKU),
        );

        // Assert
        $this->assertStringContainsString(
            sprintf('For cheaper alternatives, read its price with `%s` first, then search its category with that price as `priceMax`.', ProductDetailsToolPlugin::TOOL_NAME),
            $systemPrompt,
        );
    }

    public function testGivenNamedSearchFiltersWhenComposedThenFiltersAreQuotedAsNameValue(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPrompt(
            $this->tester->createSearchPageContext(static::SEARCH_QUERY, [static::FILTER_BRAND_SAMSUNG]),
        );

        // Assert
        $this->assertStringContainsString(sprintf('narrowed the results by: "%s".', static::FILTER_BRAND_SAMSUNG), $systemPrompt);
        $this->assertStringContainsString(
            sprintf('call `%s` with this query and these filters', CatalogSearchToolPlugin::TOOL_NAME),
            $systemPrompt,
        );
    }

    public function testGivenProductPageAndLocaleWhenTheAgentRunsThenThePromptStatesTheResolvedProductAndCatalogLanguage(): void
    {
        // Arrange
        $this->tester->haveProductStorageClientWithProducts([
            static::PRODUCT_SKU => $this->tester->createProductAbstractStorageData(static::ID_PRODUCT_ABSTRACT, static::PRODUCT_SKU, static::PRODUCT_NAME),
        ]);
        $this->tester->haveProductLabelStorageClientWithLabels([static::ID_PRODUCT_ABSTRACT => [static::LABEL_DISCONTINUED]]);
        $storefrontAssistantChatRequestTransfer = $this->createChatRequestFromProductPage();

        // Act
        $systemPrompt = $this->tester->executeProductDiscoveryAgentAndGetSystemPrompt($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertStringContainsString(sprintf('idProductAbstract %d', static::ID_PRODUCT_ABSTRACT), $systemPrompt);
        $this->assertStringContainsString(sprintf('Its labels: "%s".', static::LABEL_DISCONTINUED), $systemPrompt);
        $this->assertStringContainsString(
            sprintf('Catalog language: %s', $storefrontAssistantChatRequestTransfer->getLocaleName()),
            $systemPrompt,
        );
    }

    protected function createChatRequestFromProductPage(): StorefrontAssistantChatRequestTransfer
    {
        return $this->tester->createStorefrontAssistantChatRequest(static::CUSTOMER_REFERENCE, static::CONVERSATION_REFERENCE)
            ->setStorefrontAssistantPageContext(
                $this->tester->createProductPageContext(static::PRODUCT_NAME, static::PRODUCT_SKU),
            );
    }
}
