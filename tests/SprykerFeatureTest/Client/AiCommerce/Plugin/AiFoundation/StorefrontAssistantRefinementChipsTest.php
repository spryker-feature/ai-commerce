<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\AiFoundation;

use Codeception\Test\Unit;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group AiFoundation
 * @group StorefrontAssistantRefinementChipsTest
 */
class StorefrontAssistantRefinementChipsTest extends Unit
{
    protected const string FACET_BRAND = 'brand';

    protected const string FACET_COLOR = 'color';

    protected const string FACET_LABEL = 'label';

    protected const string KEY_REFINEMENT_CHIPS = 'refinementChips';

    protected const string KEY_ACTIVE_FILTERS = 'activeFilters';

    protected const string KEY_LABEL = 'label';

    protected const string KEY_FILTER_ARGUMENT = 'filterArgument';

    protected const string KEY_MATCH_COUNT = 'matchCount';

    protected const string FILTER_ARGUMENT_BRAND = 'filters.brand';

    protected const string REFINEMENT_TAP_GUIDANCE_FRAGMENT = 'tapped a refinement chip or removed a filter pill';

    protected const string NARROWING_ANSWER_TAP_GUIDANCE_FRAGMENT = 'tapped a narrowing answer';

    /**
     * @var list<int>
     */
    protected const array PRODUCT_ABSTRACT_IDS = [101, 102, 103];

    protected AiCommerceClientTester $tester;

    public function testGivenSearchWithRefinementsWhenProductsDisplayedThenAtMostSixChipsWithMatchCountsAreReturned(): void
    {
        // Arrange
        $this->tester->setUpFilterableFacetConfigs([static::FACET_BRAND => false, static::FACET_COLOR => false]);
        $this->haveSearchInTurn(98, [
            static::FACET_LABEL => ['SALE %' => 30, 'New' => 5],
            static::FACET_BRAND => ['Sony' => 30, 'Canon' => 28, 'Samsung' => 19],
            static::FACET_COLOR => ['Black' => 32, 'White' => 20, 'Red' => 7],
        ]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertSame(
            ['Sony' => 30, 'Canon' => 28, 'Black' => 32, 'White' => 20, 'SALE %' => 30, 'New' => 5],
            array_column($toolOutput[static::KEY_REFINEMENT_CHIPS], static::KEY_MATCH_COUNT, static::KEY_LABEL),
        );
    }

    public function testGivenRefinementValueMatchingEveryResultWhenProductsDisplayedThenItIsNotOfferedAsChip(): void
    {
        // Arrange
        $this->tester->setUpFilterableFacetConfigs([static::FACET_BRAND => false]);
        $this->haveSearchInTurn(10, [static::FACET_BRAND => ['Canon' => 10, 'Sony' => 4]]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertSame(['Sony'], array_column($toolOutput[static::KEY_REFINEMENT_CHIPS], static::KEY_LABEL));
    }

    public function testGivenAppliedBrandFilterWhenProductsDisplayedThenActiveFilterPillIsReturnedAndBrandIsNotOfferedAgain(): void
    {
        // Arrange
        $this->tester->setUpFilterableFacetConfigs([static::FACET_BRAND => false, static::FACET_COLOR => false]);
        $this->haveSearchInTurn(28, [
            static::FACET_BRAND => ['Sony' => 30, 'Canon' => 28],
            static::FACET_COLOR => ['Black' => 10],
        ], ['query' => 'camera', 'filters' => [static::FACET_BRAND => 'Canon']]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput(static::PRODUCT_ABSTRACT_IDS);

        // Assert
        $this->assertSame(
            [[static::KEY_LABEL => 'Canon', static::KEY_FILTER_ARGUMENT => static::FILTER_ARGUMENT_BRAND, 'value' => 'Canon']],
            $toolOutput[static::KEY_ACTIVE_FILTERS],
        );
        $this->assertNotContains(static::FILTER_ARGUMENT_BRAND, array_column($toolOutput[static::KEY_REFINEMENT_CHIPS], static::KEY_FILTER_ARGUMENT));
    }

    public function testGivenProductsNotFoundBySearchOfTheTurnWhenProductsDisplayedThenNoChipsAreReturned(): void
    {
        // Arrange
        $this->tester->setUpFilterableFacetConfigs([static::FACET_BRAND => false]);
        $this->haveSearchInTurn(98, [static::FACET_BRAND => ['Sony' => 30, 'Canon' => 28]]);

        // Act
        $toolOutput = $this->tester->streamDisplayedProductsToolOutput([static::PRODUCT_ABSTRACT_IDS[0], 104]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_REFINEMENT_CHIPS, $toolOutput);
    }

    public function testGivenBrowseModeSearchWhenExecutedThenItIsRegisteredForTheTurnWithAnEmptyQuery(): void
    {
        // Arrange
        $this->tester->setUpFilterableFacetConfigs([]);
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRows(static::PRODUCT_ABSTRACT_IDS));

        // Act
        $this->tester->executeCatalogSearchToolInNewTurn(['category' => 6]);

        // Assert
        $this->assertSame([''], array_column($this->tester->getCatalogSearchResultsOfTurn(), 'query'));
    }

    public function testGivenRefinementTapMessageWhenSystemPromptIsComposedThenRefinementGuidanceIsAdded(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPromptForMessage('Show only Canon [filters.brand=Canon]');

        // Assert
        $this->assertStringContainsString(static::REFINEMENT_TAP_GUIDANCE_FRAGMENT, $systemPrompt);
    }

    public function testGivenOrdinaryMessageWhenSystemPromptIsComposedThenRefinementGuidanceIsNotAdded(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPromptForMessage('Show me Canon cameras');

        // Assert
        $this->assertStringNotContainsString(static::REFINEMENT_TAP_GUIDANCE_FRAGMENT, $systemPrompt);
        $this->assertStringNotContainsString(static::NARROWING_ANSWER_TAP_GUIDANCE_FRAGMENT, $systemPrompt);
    }

    public function testGivenNarrowingAnswerWithMatchCountWhenSystemPromptIsComposedThenNarrowingAnswerGuidanceIsAdded(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPromptForMessage('Sony (14)');

        // Assert
        $this->assertStringContainsString(static::NARROWING_ANSWER_TAP_GUIDANCE_FRAGMENT, $systemPrompt);
        $this->assertStringNotContainsString(static::REFINEMENT_TAP_GUIDANCE_FRAGMENT, $systemPrompt);
    }

    public function testGivenGiftBudgetAnswerWithoutMatchCountWhenSystemPromptIsComposedThenNarrowingAnswerGuidanceIsNotAdded(): void
    {
        // Act
        $systemPrompt = $this->tester->composeProductDiscoverySystemPromptForMessage('€100–€200');

        // Assert
        $this->assertStringNotContainsString(static::NARROWING_ANSWER_TAP_GUIDANCE_FRAGMENT, $systemPrompt);
    }

    /**
     * @param array<string, array<string, int>> $matchCountsByFacetName
     * @param array<string, mixed> $catalogSearchArguments
     */
    protected function haveSearchInTurn(int $totalResults, array $matchCountsByFacetName, array $catalogSearchArguments = ['query' => 'camera']): void
    {
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithRowsAndFacetCounts(static::PRODUCT_ABSTRACT_IDS, $totalResults, $matchCountsByFacetName),
        );
        $this->tester->executeCatalogSearchToolInNewTurn($catalogSearchArguments);
    }
}
