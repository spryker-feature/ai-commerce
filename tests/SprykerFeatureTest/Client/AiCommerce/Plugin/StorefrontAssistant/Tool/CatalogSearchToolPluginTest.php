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
use Spryker\Shared\AiFoundation\Tools\ToolParameterType;
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

    protected const string NEXT_ACTION_LONGEST_WORD = 'longest word';

    protected const int LIMIT_ABOVE_MAX = 99;

    protected const int LIMIT_WITHIN_RANGE = 8;

    protected const int TOTAL_RESULTS_NONE = 0;

    protected const string SPELLING_SUGGESTION = 'cameras';

    protected const int PAGE_THIRD = 3;

    protected const int MAX_RESULTS_DEFAULT = 5;

    protected const int RESULTS_SPANNING_PAGE_BOUNDARY = 24;

    protected const string KEY_ARGUMENT = 'argument';

    protected const string KEY_REASON = 'reason';

    protected const string REQUEST_PARAMETER_PRICE = 'price';

    protected const string RANGE_KEY_MIN = 'min';

    protected const string RANGE_KEY_MAX = 'max';

    protected const string KEY_PRICE = 'price';

    protected const string KEY_URL = 'url';

    protected const string KEY_PRICE_MODE = 'priceMode';

    protected const string KEY_CURRENCY_ISO_CODE = 'currencyIsoCode';

    protected const int BUDGET_UPPER = 100;

    protected const int BUDGET_WINDOW_LOWER = 100;

    protected const int BUDGET_WINDOW_UPPER = 200;

    protected const string BUDGET_DECIMAL = '99.99';

    protected const int SEARCH_HIT_PRICE = 12345;

    protected const int PRICE_PRECISION = 100;

    protected const string KEY_PRICE_RANGE = 'priceRange';

    protected const string KEY_RATING_RANGE = 'ratingRange';

    protected const string KEY_MIN_FORMATTED = 'minFormatted';

    protected const string KEY_MAX_FORMATTED = 'maxFormatted';

    protected const string FACET_NAME_PRICE = 'price-DEFAULT-EUR-GROSS_MODE';

    protected const string FACET_NAME_RATING = 'rating';

    protected const int PRICE_RANGE_MIN = 2500;

    protected const int PRICE_RANGE_MAX = 345700;

    protected const int RATING_RANGE_MIN = 3;

    protected const int RATING_RANGE_MAX = 5;

    protected const int RATING_RANGE_NONE = 0;

    protected const string PARAMETER_LABEL = 'label';

    protected const int TOTAL_RESULTS_WHOLE_CATALOG = 214;

    protected const string PARAMETER_MERCHANT = 'merchant';

    protected const string FACET_MERCHANT_NAME = 'merchant_name';

    protected const string FACET_MERCHANT_REFERENCE = 'merchant_reference';

    protected const string FILTER_ARGUMENT_MERCHANT_REFERENCE = 'filters.merchant_reference';

    protected const string MERCHANT_NAME = 'Video King';

    protected const string MERCHANT_NAME_OTHER = 'Spryker';

    protected const string MERCHANT_REFERENCE = 'MER000002';

    protected AiCommerceClientTester $tester;

    public function testGivenNoQueryAndACategoryWhenExecutedThenTheCategoryIsBrowsedWithoutAFreeTextWord(): void
    {
        // Arrange
        $idCategoryNode = $this->tester->getIdCategoryNodeFromStorage();
        $capturedRequestParameters = [];
        $capturedSearchString = null;
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters, $capturedSearchString);

        // Act
        (new CatalogSearchToolPlugin())->execute([static::PARAMETER_CATEGORY => (string)$idCategoryNode]);

        // Assert
        $this->assertSame('', $capturedSearchString);
        $this->assertSame($idCategoryNode, $capturedRequestParameters[static::PARAMETER_CATEGORY] ?? null);
    }

    public function testGivenNoArgumentsWhenExecutedThenTheWholeCatalogIsBrowsed(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedRequestParameters = [];
        $capturedSearchString = null;
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters(
            $this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_WHOLE_CATALOG),
            $capturedRequestParameters,
            $capturedSearchString,
        );

        // Act
        $result = (new CatalogSearchToolPlugin())->execute();

        // Assert
        $this->assertSame('', $capturedSearchString);
        $this->assertSame(static::TOTAL_RESULTS_WHOLE_CATALOG, $result[static::KEY_TOTAL_RESULTS]);
    }

    public function testGivenALabelKeyWhenExecutedThenTheSearchFiltersByItsLocalizedName(): void
    {
        // Arrange
        $productLabelDictionaryItemTransfer = $this->tester->getProductLabelDictionaryItemFromStorage();
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);

        // Act
        (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_LABEL => strtolower($productLabelDictionaryItemTransfer->getKeyOrFail()),
        ]);

        // Assert
        $this->assertSame([$productLabelDictionaryItemTransfer->getNameOrFail()], $capturedRequestParameters[static::PARAMETER_LABEL] ?? null);
    }

    public function testGivenAnAlreadyLocalizedLabelWhenExecutedThenItIsPassedUnchanged(): void
    {
        // Arrange
        $productLabelDictionaryItemTransfer = $this->tester->getProductLabelDictionaryItemFromStorage();
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);

        // Act
        (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_LABEL => [$productLabelDictionaryItemTransfer->getNameOrFail()],
        ]);

        // Assert
        $this->assertSame([$productLabelDictionaryItemTransfer->getNameOrFail()], $capturedRequestParameters[static::PARAMETER_LABEL] ?? null);
    }

    public function testGivenThePluginWhenItsContractIsReadThenTheQueryIsOptional(): void
    {
        // Arrange
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $parameters = $catalogSearchToolPlugin->getParameters();

        // Assert
        $queryParameter = $this->findParameter($parameters, static::PARAMETER_QUERY);
        $this->assertNotNull($queryParameter);
        $this->assertFalse($queryParameter->isRequired());
    }

    /**
     * @param array<\Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface> $parameters
     */
    protected function findParameter(array $parameters, string $name): ?ToolParameterInterface
    {
        foreach ($parameters as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }

        return null;
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
        $this->assertNotEmpty($parameters);
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

    public function testGivenMerchantReferenceScopingWhenExecutedThenItIsNeverFilterable(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_MERCHANT_REFERENCE => false]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_FILTERS => [static::FACET_MERCHANT_REFERENCE => static::MERCHANT_REFERENCE],
        ]);

        // Assert
        $this->assertArrayNotHasKey(static::FACET_MERCHANT_REFERENCE, $capturedRequestParameters);
        $this->assertIgnoredArgumentReported(static::FILTER_ARGUMENT_MERCHANT_REFERENCE, $result);
    }

    public function testGivenAMerchantWhenExecutedThenItReachesTheSearchAsTheMerchantNameFilter(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_MERCHANT_NAME => true]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_MERCHANT => static::MERCHANT_NAME,
        ]);

        // Assert
        $this->assertSame([static::MERCHANT_NAME], $capturedRequestParameters[static::FACET_MERCHANT_NAME] ?? null);
        $this->assertSame([static::MERCHANT_NAME], $result[static::KEY_APPLIED_FILTERS][static::FACET_MERCHANT_NAME] ?? null);
        $this->assertArrayNotHasKey(static::KEY_IGNORED_ARGUMENTS, $result);
    }

    public function testGivenSeveralMerchantsWhenExecutedThenTheSearchMatchesAnyOfThem(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_MERCHANT_NAME => true]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);

        // Act
        (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_MERCHANT => [static::MERCHANT_NAME, static::MERCHANT_NAME_OTHER, static::MERCHANT_NAME],
        ]);

        // Assert
        $this->assertSame(
            [static::MERCHANT_NAME, static::MERCHANT_NAME_OTHER],
            $capturedRequestParameters[static::FACET_MERCHANT_NAME] ?? null,
        );
    }

    public function testGivenAMerchantNameFilterWhenExecutedThenItIsAppliedLikeTheMerchantArgument(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_MERCHANT_NAME => true]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_FILTERS => [static::FACET_MERCHANT_NAME => static::MERCHANT_NAME],
        ]);

        // Assert
        $this->assertSame([static::MERCHANT_NAME], $capturedRequestParameters[static::FACET_MERCHANT_NAME] ?? null);
        $this->assertArrayNotHasKey(static::KEY_IGNORED_ARGUMENTS, $result);
    }

    public function testGivenAMerchantInAShopWithoutSellerFilteringWhenExecutedThenItIsReportedBackInsteadOfApplied(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FILTER_BRAND => false]);
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_MERCHANT => static::MERCHANT_NAME,
        ]);

        // Assert
        $this->assertArrayNotHasKey(static::FACET_MERCHANT_NAME, $capturedRequestParameters);
        $this->assertIgnoredArgumentReported(static::PARAMETER_MERCHANT, $result);
    }

    public function testGivenAMerchantFacetWhenExecutedThenTheRefinementNamesTheMerchantArgument(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_MERCHANT_NAME => true]);
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithFacet(
                static::FACET_MERCHANT_NAME,
                [static::MERCHANT_NAME, static::MERCHANT_NAME_OTHER],
            ),
        );

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $refinement = $this->findRefinement($result, static::FACET_MERCHANT_NAME);
        $this->assertNotNull($refinement);
        $this->assertSame(static::PARAMETER_MERCHANT, $refinement[static::KEY_FILTER_ARGUMENT]);
    }

    public function testGivenAFacetNoArgumentAppliesWhenExecutedThenItIsNotOfferedAsARefinement(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FILTER_BRAND => false]);
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithFacet(static::FACET_MERCHANT_NAME, [static::MERCHANT_NAME]),
        );

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertNull($this->findRefinement($result, static::FACET_MERCHANT_NAME));
    }

    public function testGivenAnySearchWhenExecutedThenEveryRefinementNamesAnArgumentTheSearchAccepts(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FILTER_BRAND => false, static::FACET_MERCHANT_NAME => true]);
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithFacets([
            $this->tester->createCatalogSearchFacet(static::FILTER_BRAND, [static::FILTER_VALUE_BRAND]),
            $this->tester->createCatalogSearchFacet(static::FACET_MERCHANT_NAME, [static::MERCHANT_NAME]),
            $this->tester->createCatalogSearchFacet(static::FILTER_UNKNOWN, [static::FILTER_VALUE_COLOR]),
        ]));
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();
        $acceptedArguments = array_map(
            static fn (ToolParameterInterface $parameter): string => $parameter->getName(),
            $catalogSearchToolPlugin->getParameters(),
        );

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertCount(2, $result[static::KEY_AVAILABLE_REFINEMENTS]);

        foreach ($result[static::KEY_AVAILABLE_REFINEMENTS] as $refinement) {
            $this->assertContains(explode('.', $refinement[static::KEY_FILTER_ARGUMENT])[0], $acceptedArguments);
        }
    }

    public function testGivenThePluginWhenItsContractIsReadThenItAcceptsAMerchant(): void
    {
        // Arrange
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $merchantParameter = $this->findParameter($catalogSearchToolPlugin->getParameters(), static::PARAMETER_MERCHANT);

        // Assert
        $this->assertNotNull($merchantParameter);
        $this->assertSame(ToolParameterType::Array, $merchantParameter->getType());
        $this->assertFalse($merchantParameter->isRequired());
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

    public function testGivenLastPageWhenSearchingThenHasMoreResultsIsFalse(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_BEYOND_WINDOW));
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY, static::PARAMETER_PAGE => static::PAGE_SECOND]);

        // Assert
        $this->assertFalse($result[static::KEY_HAS_MORE_RESULTS]);
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

    public function testGivenNoMatchAndNoSpellingSuggestionWhenExecutedThenTheReplyTellsTheModelToBroadenTheWords(): void
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
        $this->assertStringContainsString(static::NEXT_ACTION_LONGEST_WORD, $result[static::KEY_SUGGESTED_NEXT_ACTION]);
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

    public function testGivenAnUpperBudgetInMajorUnitsWhenExecutedThenItReachesTheSearchUnchanged(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_PRICE_MAX => static::BUDGET_UPPER,
        ]);

        // Assert
        $this->assertSame(
            [static::RANGE_KEY_MAX => (float)static::BUDGET_UPPER],
            $capturedRequestParameters[static::REQUEST_PARAMETER_PRICE] ?? null,
        );
        $this->assertArrayNotHasKey(static::KEY_IGNORED_ARGUMENTS, $result);
    }

    public function testGivenABudgetWindowWhenExecutedThenBothBoundsReachTheSearchInMajorUnits(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_PRICE_MIN => static::BUDGET_WINDOW_LOWER,
            static::PARAMETER_PRICE_MAX => static::BUDGET_WINDOW_UPPER,
        ]);

        // Assert
        $this->assertSame(
            [
                static::RANGE_KEY_MIN => (float)static::BUDGET_WINDOW_LOWER,
                static::RANGE_KEY_MAX => (float)static::BUDGET_WINDOW_UPPER,
            ],
            $capturedRequestParameters[static::REQUEST_PARAMETER_PRICE] ?? null,
        );
    }

    public function testGivenADecimalBudgetWhenExecutedThenItIsNotRounded(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedRequestParameters = [];
        $this->tester->setUpCatalogSearchResultCapturingRequestParameters([], $capturedRequestParameters);
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $catalogSearchToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_PRICE_MAX => static::BUDGET_DECIMAL,
        ]);

        // Assert
        $this->assertSame(
            (float)static::BUDGET_DECIMAL,
            $capturedRequestParameters[static::REQUEST_PARAMETER_PRICE][static::RANGE_KEY_MAX] ?? null,
        );
    }

    public function testGivenThePluginWhenItsPriceParametersAreReadThenTheyAreNumbersInNormalCurrencyUnits(): void
    {
        // Arrange
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $priceParameters = array_values(array_filter(
            $catalogSearchToolPlugin->getParameters(),
            static fn (ToolParameterInterface $toolParameter): bool => in_array(
                $toolParameter->getName(),
                [static::PARAMETER_PRICE_MIN, static::PARAMETER_PRICE_MAX],
                true,
            ),
        ));

        // Assert
        $this->assertCount(2, $priceParameters);

        foreach ($priceParameters as $priceParameter) {
            $this->assertSame(ToolParameterType::Number, $priceParameter->getType());
            $this->assertStringNotContainsString('smallest unit', $priceParameter->getDescription());
            $this->assertStringNotContainsString('5000', $priceParameter->getDescription());
        }

        $this->assertStringNotContainsString('smallest unit', $catalogSearchToolPlugin->getDescription());
    }

    public function testGivenThePluginWhenItsGuidanceIsReadThenNamedValuesAreFilteredAtOnceAndModelLookupsAreNotSorted(): void
    {
        // Arrange
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $toolParameterDescriptions = [];

        foreach ($catalogSearchToolPlugin->getParameters() as $toolParameter) {
            $toolParameterDescriptions[$toolParameter->getName()] = $toolParameter->getDescription();
        }

        // Assert
        $toolDescription = $catalogSearchToolPlugin->getDescription();
        $this->assertStringNotContainsString('once a refinement has shown it exists', $toolDescription);
        $this->assertStringContainsString('"Samsung Galaxy S5 mini"', $toolParameterDescriptions[static::PARAMETER_QUERY]);
        $this->assertStringContainsString('Never sort a lookup of a specific model', $toolParameterDescriptions[static::PARAMETER_SORT]);
        $this->assertStringContainsString('in the first call', $toolParameterDescriptions[static::PARAMETER_FILTERS]);
    }

    public function testGivenAPricedSearchHitWhenExecutedThenTheAgentReadsItsPriceInMajorUnits(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $idProductAbstract = $this->tester->getIdProductAbstractFromStorage();
        $expectedPrice = $this->tester->findDisplayedProductPrice($idProductAbstract) ?? static::SEARCH_HIT_PRICE;
        $this->tester->setUpCatalogSearchResult(
            $this->tester->createCatalogSearchResultWithPrice($idProductAbstract, static::SEARCH_HIT_PRICE),
        );
        $catalogSearchToolPlugin = new CatalogSearchToolPlugin();

        // Act
        $result = $catalogSearchToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $product = $result[static::KEY_PRODUCTS][0];
        $this->assertSame(round($expectedPrice / static::PRICE_PRECISION, 2), $product[static::KEY_PRICE]);
        $this->assertArrayNotHasKey(static::KEY_URL, $product);
        $this->assertArrayNotHasKey(static::KEY_PRICE_MODE, $product);
        $this->assertArrayNotHasKey(static::KEY_CURRENCY_ISO_CODE, $product);
    }

    public function testGivenAPriceFacetNamedAfterThePriceIdentifierWhenExecutedThenThePriceRangeIsReturnedInMajorUnits(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithFacets([
            $this->tester->createRangeFacet(static::FACET_NAME_PRICE, static::REQUEST_PARAMETER_PRICE, static::PRICE_RANGE_MIN, static::PRICE_RANGE_MAX),
        ]));

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertSame(round(static::PRICE_RANGE_MIN / static::PRICE_PRECISION, 2), $result[static::KEY_PRICE_RANGE][static::RANGE_KEY_MIN]);
        $this->assertSame(round(static::PRICE_RANGE_MAX / static::PRICE_PRECISION, 2), $result[static::KEY_PRICE_RANGE][static::RANGE_KEY_MAX]);
    }

    public function testGivenAPriceRangeWhenExecutedThenItCarriesBoundsFormattedInTheStoreCurrency(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithFacets([
            $this->tester->createRangeFacet(static::FACET_NAME_PRICE, static::REQUEST_PARAMETER_PRICE, static::PRICE_RANGE_MIN, static::PRICE_RANGE_MAX),
        ]));

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertSame($this->tester->formatAmountInCurrentCurrency(static::PRICE_RANGE_MIN), $result[static::KEY_PRICE_RANGE][static::KEY_MIN_FORMATTED]);
        $this->assertSame($this->tester->formatAmountInCurrentCurrency(static::PRICE_RANGE_MAX), $result[static::KEY_PRICE_RANGE][static::KEY_MAX_FORMATTED]);
    }

    public function testGivenARatingFacetWhenExecutedThenTheRatingRangeIsReturned(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithFacets([
            $this->tester->createRangeFacet(static::FACET_NAME_RATING, static::FACET_NAME_RATING, static::RATING_RANGE_MIN, static::RATING_RANGE_MAX),
        ]));

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertSame(
            [static::RANGE_KEY_MIN => static::RATING_RANGE_MIN, static::RANGE_KEY_MAX => static::RATING_RANGE_MAX],
            $result[static::KEY_RATING_RANGE] ?? null,
        );
    }

    public function testGivenNoRatedMatchWhenExecutedThenNoRatingRangeIsReturned(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithFacets([
            $this->tester->createRangeFacet(static::FACET_NAME_RATING, static::FACET_NAME_RATING, static::RATING_RANGE_NONE, static::RATING_RANGE_NONE),
        ]));

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_RATING_RANGE, $result);
    }

    public function testGivenRangeFacetsWhenExecutedThenTheyAreNotOfferedAsRefinements(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithFacets([
            $this->tester->createRangeFacet(static::FACET_NAME_PRICE, static::REQUEST_PARAMETER_PRICE, static::PRICE_RANGE_MIN, static::PRICE_RANGE_MAX),
            $this->tester->createRangeFacet(static::FACET_NAME_RATING, static::FACET_NAME_RATING, static::RATING_RANGE_MIN, static::RATING_RANGE_MAX),
        ]));

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertSame([], $result[static::KEY_AVAILABLE_REFINEMENTS]);
    }
}
