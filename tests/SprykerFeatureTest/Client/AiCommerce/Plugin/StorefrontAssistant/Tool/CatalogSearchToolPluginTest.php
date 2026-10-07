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
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group CatalogSearchToolPluginTest
 */
class CatalogSearchToolPluginTest extends Unit
{
    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::VALID_ITEMS_PER_PAGE_OPTIONS
     *
     * @var list<int>
     */
    protected const array VALID_ITEMS_PER_PAGE_OPTIONS = [12, 24, 36];

    protected const string PARAMETER_QUERY = 'query';

    protected const string PARAMETER_SORT = 'sort';

    protected const string PARAMETER_CATEGORY = 'category';

    protected const string PARAMETER_RATING_MIN = 'ratingMin';

    protected const string PARAMETER_PAGE = 'page';

    protected const string PARAMETER_PRICE_MIN = 'priceMin';

    protected const string PARAMETER_PRICE_MAX = 'priceMax';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_TOTAL_RESULTS = 'totalResults';

    protected const string KEY_AVAILABLE_REFINEMENTS = 'availableRefinements';

    protected const string KEY_APPLIED_FILTERS = 'appliedFilters';

    protected const string KEY_IGNORED_ARGUMENTS = 'ignoredArguments';

    protected const string UNSUPPORTED_SORT = 'not_a_sort';

    protected const string CATEGORY_NAME_INSTEAD_OF_ID = 'Cameras & Camcorders';

    protected const string KEY_VALUE_TYPE = 'valueType';

    protected const string KEY_VALUES = 'values';

    protected const string KEY_LABEL = 'label';

    protected const string KEY_VALUE = 'value';

    protected const string VALUE_TYPE_CATEGORY_ID = 'categoryId';

    protected const int RATING_ABOVE_SCALE = 9;

    protected const int PRICE_MIN_ABOVE_MAX = 9000;

    protected const int PRICE_MAX_BELOW_MIN = 1000;

    protected const int PRICE_NEGATIVE = -100;

    protected const int PAGE_SECOND = 2;

    protected const string QUERY = 'laptop';

    protected const string PARAMETER_FILTERS = 'filters';

    protected const string FILTER_BRAND = 'brand';

    protected const string FILTER_COLOR = 'color';

    protected const string FILTER_UNKNOWN = 'not_a_facet';

    protected const string FILTER_VALUE_BRAND = 'Canon';

    protected const string FILTER_VALUE_COLOR = 'Black';

    protected const string KEY_HAS_MORE_RESULTS = 'hasMoreResults';

    protected const string KEY_FILTER_ARGUMENT = 'filterArgument';

    protected const string KEY_NAME = 'name';

    protected const string FILTER_ARGUMENT_BRAND = 'filters.brand';

    protected const string IGNORED_ARGUMENT_UNKNOWN_FILTER = 'filters.not_a_facet';

    protected const int TOTAL_RESULTS_BEYOND_WINDOW = 8;

    protected const string PARAMETER_LIMIT = 'limit';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string KEY_DID_YOU_MEAN = 'didYouMean';

    protected const int LIMIT_ABOVE_MAX = 99;

    protected const int LIMIT_WITHIN_RANGE = 8;

    protected const int TOTAL_RESULTS_NONE = 0;

    protected const string SPELLING_SUGGESTION = 'cameras';

    protected const int PAGE_THIRD = 3;

    protected const int MAX_RESULTS_DEFAULT = 5;

    protected const int RESULTS_SPANNING_PAGE_BOUNDARY = 24;

    protected const string KEY_ARGUMENT = 'argument';

    protected const string KEY_REASON = 'reason';

    protected AiCommerceClientTester $tester;

    public function testGivenBlankQueryWhenExecutedThenNothingIsSearched(): void
    {
        // Arrange
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => ' ']);

        // Assert
        $this->assertSame([], $result[static::KEY_PRODUCTS]);
        $this->assertSame(0, $result[static::KEY_TOTAL_RESULTS]);
    }

    public function testGivenSearchHitWhenExecutedThenItIsExpandedFromRealProductStorage(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResult($idProductAbstract));
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertCount(1, $result[static::KEY_PRODUCTS]);
        $this->assertSame($idProductAbstract, $result[static::KEY_PRODUCTS][0]['idProductAbstract']);
        $this->assertNotSame('', $result[static::KEY_PRODUCTS][0]['description']);
    }

    public function testGivenSearchHitWhenExecutedThenInternalFieldsNeverReachTheAgent(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $idProductAbstract = $this->tester->getIdProductAbstractFromStorage();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResult($idProductAbstract));
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertArrayNotHasKey('imageUrl', $result[static::KEY_PRODUCTS][0]);
        $this->assertArrayNotHasKey('cost_price', $result[static::KEY_PRODUCTS][0]);
    }

    public function testGivenUnsupportedSortWhenExecutedThenItIsReportedBackInsteadOfApplied(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult([]);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_SORT => static::UNSUPPORTED_SORT,
        ]);

        // Assert
        $this->assertIgnoredArgumentReported(static::PARAMETER_SORT, $result);
        $this->assertArrayNotHasKey(static::PARAMETER_SORT, $result[static::KEY_APPLIED_FILTERS]);
    }

    public function testGivenInvertedPriceRangeWhenExecutedThenBothBoundsAreReportedBack(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult([]);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_PRICE_MIN => static::PRICE_MIN_ABOVE_MAX,
            static::PARAMETER_PRICE_MAX => static::PRICE_MAX_BELOW_MIN,
        ]);

        // Assert
        $this->assertIgnoredArgumentReported(static::PARAMETER_PRICE_MIN, $result);
        $this->assertIgnoredArgumentReported(static::PARAMETER_PRICE_MAX, $result);
    }

    public function testGivenACategoryNameInsteadOfAnIdWhenExecutedThenItIsReportedBackInsteadOfSearched(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult([]);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_CATEGORY => static::CATEGORY_NAME_INSTEAD_OF_ID,
        ]);

        // Assert
        $this->assertIgnoredArgumentReported(static::PARAMETER_CATEGORY, $result);
        $this->assertArrayNotHasKey(static::PARAMETER_CATEGORY, $result[static::KEY_APPLIED_FILTERS]);
    }

    public function testGivenACategoryIdWhenExecutedThenItIsAppliedAsAFilter(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult([]);
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_CATEGORY => (string)$idCategoryNode,
        ]);

        // Assert
        $this->assertSame($idCategoryNode, $result[static::KEY_APPLIED_FILTERS][static::PARAMETER_CATEGORY]);
        $this->assertArrayNotHasKey(static::KEY_IGNORED_ARGUMENTS, $result);
    }

    public function testGivenACategoryFacetWhenExecutedThenEveryValueCarriesTheCategoryNameItStandsFor(): void
    {
        // Arrange
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithCategoryFacet([$idCategoryNode]),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $refinementValue = $result[static::KEY_AVAILABLE_REFINEMENTS][0][static::KEY_VALUES][0];

        $this->assertSame((string)$idCategoryNode, $refinementValue[static::KEY_VALUE]);
        $this->assertSame(
            $this->tester->getCategoryNodeNameFromStorage($idCategoryNode),
            $refinementValue[static::KEY_LABEL],
        );
    }

    public function testGivenACategoryFacetWhenExecutedThenTheRefinementDeclaresThatItsValueIsAnId(): void
    {
        // Arrange
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithCategoryFacet([$idCategoryNode]),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertSame(
            static::VALUE_TYPE_CATEGORY_ID,
            $result[static::KEY_AVAILABLE_REFINEMENTS][0][static::KEY_VALUE_TYPE],
        );
    }

    public function testGivenARatingOutsideTheStarScaleWhenExecutedThenItIsReportedBackInsteadOfApplied(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult([]);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_RATING_MIN => static::RATING_ABOVE_SCALE,
        ]);

        // Assert
        $this->assertIgnoredArgumentReported(static::PARAMETER_RATING_MIN, $result);
        $this->assertArrayNotHasKey(static::PARAMETER_RATING_MIN, $result[static::KEY_APPLIED_FILTERS]);
    }

    public function testGivenANegativePriceBoundWhenExecutedThenItIsReportedBackInsteadOfApplied(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult([]);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_PRICE_MIN => static::PRICE_NEGATIVE,
        ]);

        // Assert
        $this->assertIgnoredArgumentReported(static::PARAMETER_PRICE_MIN, $result);
    }

    public function testGivenAPageBeyondTheFirstWhenExecutedThenItIsNotEchoedAsAFilterTheCustomerChose(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult([]);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_PAGE => static::PAGE_SECOND,
        ]);

        // Assert
        $this->assertSame(static::PAGE_SECOND, $result['page']);
        $this->assertArrayNotHasKey(static::PARAMETER_PAGE, $result[static::KEY_APPLIED_FILTERS]);
    }

    public function testGivenThePluginWhenItsContractIsReadThenItExposesTheToolContractAiFoundationRegistersItBy(): void
    {
        // Arrange
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $parameters = $catalogSearchToolPlugin->getParameters();

        // Assert
        $this->assertInstanceOf(ToolPluginInterface::class, $catalogSearchToolPlugin);
        $this->assertSame(CatalogSearchToolPlugin::TOOL_NAME, $catalogSearchToolPlugin->getName());
        $this->assertContainsOnlyInstancesOf(ToolParameterInterface::class, $parameters);
    }

    /**
     * The shop decides which attributes are filterable, so a refinement for a configured facet has to
     * name the argument that applies it; without one the model is shown a value it cannot act on.
     */
    public function testGivenAConfiguredFacetWhenExecutedThenTheRefinementNamesTheArgumentThatAppliesIt(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FILTER_BRAND => false]);
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithFacet(static::FILTER_BRAND, [static::FILTER_VALUE_BRAND]),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $refinement = $this->findRefinement($result, static::FILTER_BRAND);
        $this->assertNotNull($refinement);
        $this->assertSame(static::FILTER_ARGUMENT_BRAND, $refinement[static::KEY_FILTER_ARGUMENT]);
    }

    public function testGivenAConfiguredFilterWhenExecutedThenItReachesTheSearchUnderItsRequestParameterName(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FILTER_BRAND => false]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_FILTERS => [static::FILTER_BRAND => static::FILTER_VALUE_BRAND],
        ]);

        // Assert
        $this->assertSame(static::FILTER_VALUE_BRAND, $capturedRequestParameters[static::FILTER_BRAND] ?? null);
    }

    public function testGivenAMultiValuedFilterWhenExecutedThenItReachesTheSearchAsAList(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FILTER_COLOR => true]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_FILTERS => [static::FILTER_COLOR => static::FILTER_VALUE_COLOR],
        ]);

        // Assert
        $this->assertSame([static::FILTER_VALUE_COLOR], $capturedRequestParameters[static::FILTER_COLOR] ?? null);
    }

    public function testGivenAFilterTheShopDoesNotConfigureWhenExecutedThenItIsReportedBackInsteadOfApplied(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FILTER_BRAND => false]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_FILTERS => [static::FILTER_UNKNOWN => static::FILTER_VALUE_BRAND],
        ]);

        // Assert
        $this->assertArrayNotHasKey(static::FILTER_UNKNOWN, $capturedRequestParameters);
        $this->assertIgnoredArgumentReported(static::IGNORED_ARGUMENT_UNKNOWN_FILTER, $result);
    }

    public function testGivenMerchantScopingWhenExecutedThenItIsNeverFilterable(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_FILTERS => ['merchant_name' => 'Video King'],
        ]);

        // Assert
        $this->assertArrayNotHasKey('merchant_name', $capturedRequestParameters);
        $this->assertIgnoredArgumentReported('filters.merchant_name', $result);
    }

    public function testGivenMoreMatchesThanTheAgentIsShownWhenExecutedThenTheRestAreReportedAsReachable(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_BEYOND_WINDOW),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertTrue($result[static::KEY_HAS_MORE_RESULTS]);
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>|null
     */
    protected function findRefinement(array $result, string $facetName): ?array
    {
        foreach ($result[static::KEY_AVAILABLE_REFINEMENTS] ?? [] as $refinement) {
            if (($refinement[static::KEY_NAME] ?? null) === $facetName) {
                return $refinement;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function assertIgnoredArgumentReported(string $argument, array $result): void
    {
        $reportedArguments = [];

        foreach ($result[static::KEY_IGNORED_ARGUMENTS] ?? [] as $ignoredArgument) {
            $reportedArguments[] = $ignoredArgument[static::KEY_ARGUMENT] ?? null;

            if (($ignoredArgument[static::KEY_ARGUMENT] ?? null) !== $argument) {
                continue;
            }

            $this->assertNotSame('', trim((string)($ignoredArgument[static::KEY_REASON] ?? '')));
        }

        $this->assertContains($argument, $reportedArguments);
    }

    public function testGivenNoMatchAndASpellingSuggestionWhenExecutedThenTheReplyTellsTheModelToRetryWithIt(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithSpellingSuggestion(
                static::TOTAL_RESULTS_NONE,
                static::SPELLING_SUGGESTION,
            ),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertSame(static::SPELLING_SUGGESTION, $result[static::KEY_DID_YOU_MEAN]);
        $this->assertStringContainsString(
            static::KEY_DID_YOU_MEAN,
            $result[static::KEY_SUGGESTED_NEXT_ACTION],
        );
    }

    public function testGivenNoMatchAndNoSpellingSuggestionWhenExecutedThenTheReplyTellsTheModelToRelaxAFilter(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_NONE),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_DID_YOU_MEAN, $result);
        $this->assertNotSame('', trim($result[static::KEY_SUGGESTED_NEXT_ACTION]));
    }

    public function testGivenMatchesWhenExecutedThenNoRecoveryIsSuggested(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_BEYOND_WINDOW),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_SUGGESTED_NEXT_ACTION, $result);
    }

    public function testGivenALimitOutsideTheAllowedRangeWhenExecutedThenItIsReportedBackInsteadOfApplied(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_BEYOND_WINDOW),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_LIMIT => static::LIMIT_ABOVE_MAX,
        ]);

        // Assert
        $this->assertIgnoredArgumentReported(static::PARAMETER_LIMIT, $result);
    }

    public function testGivenAWiderLimitWhenExecutedThenMoreProductsAreReturnedAndPagingFollowsIt(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithProducts(static::LIMIT_WITHIN_RANGE),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_LIMIT => static::LIMIT_WITHIN_RANGE,
        ]);

        // Assert
        $this->assertCount(static::LIMIT_WITHIN_RANGE, $result[static::KEY_PRODUCTS]);
    }

    public function testGivenAPageWhoseWindowStraddlesTheSearchPagingWhenExecutedThenItIsStillFull(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters(
            $this->tester->createCatalogSearchResultWithProducts(static::RESULTS_SPANNING_PAGE_BOUNDARY),
            $capturedRequestParameters,
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_PAGE => static::PAGE_THIRD,
        ]);

        // Assert
        $this->assertCount(static::MAX_RESULTS_DEFAULT, $result[static::KEY_PRODUCTS]);
        $this->assertContains($capturedRequestParameters['ipp'] ?? null, static::VALID_ITEMS_PER_PAGE_OPTIONS);
    }
}
