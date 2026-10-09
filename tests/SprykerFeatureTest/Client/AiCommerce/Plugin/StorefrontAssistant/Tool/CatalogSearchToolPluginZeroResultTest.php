<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group CatalogSearchToolPluginZeroResultTest
 */
class CatalogSearchToolPluginZeroResultTest extends Unit
{
    protected const string PARAMETER_QUERY = 'query';

    protected const string PARAMETER_CATEGORY = 'category';

    protected const string PARAMETER_FILTERS = 'filters';

    protected const string PARAMETER_MERCHANT = 'merchant';

    protected const string PARAMETER_LABEL = 'label';

    protected const string PARAMETER_PRICE_MAX = 'priceMax';

    protected const string PARAMETER_RATING_MIN = 'ratingMin';

    protected const string KEY_EMPTIED_BY = 'emptiedBy';

    protected const string KEY_ARGUMENT = 'argument';

    protected const string KEY_REQUESTED = 'requested';

    protected const string KEY_AVAILABLE_VALUES = 'availableValues';

    protected const string KEY_AVAILABLE_RANGE = 'availableRange';

    protected const string KEY_VALUE = 'value';

    protected const string KEY_MATCH_COUNT = 'matchCount';

    protected const string KEY_DID_YOU_MEAN = 'didYouMean';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string KEY_MIN = 'min';

    protected const string KEY_MAX = 'max';

    protected const string FACET_CATEGORY = 'category';

    protected const string FACET_COLOR = 'color';

    protected const string FACET_LABEL = 'label';

    protected const string FACET_MERCHANT_NAME = 'merchant_name';

    protected const string FACET_NAME_PRICE = 'price-DEFAULT-EUR-GROSS_MODE';

    protected const string FACET_NAME_RATING = 'rating';

    protected const string REQUEST_PARAMETER_PRICE = 'price';

    protected const string REQUEST_PARAMETER_RATING = 'rating';

    protected const string ARGUMENT_FILTER_COLOR = 'filters.color';

    protected const string COLOR_REQUESTED = 'Red';

    protected const string COLOR_AVAILABLE_FIRST = 'Orange';

    protected const string COLOR_AVAILABLE_SECOND = 'Black';

    protected const int ID_CATEGORY_REQUESTED = 12;

    protected const string ID_CATEGORY_OTHER = '5';

    protected const string QUERY_WITH_FILTERS = 'camera case';

    protected const string SPELLING_SUGGESTION_UNRELATED = 'cameras cable';

    protected const string QUERY_MISSPELLED = 'camerass';

    protected const string SPELLING_SUGGESTION = 'cameras';

    protected const string QUERY = 'camera';

    protected const string MERCHANT_UNKNOWN = 'Camera World';

    protected const array MERCHANTS_AVAILABLE = [
        'Video King',
        'Spryker',
        'Budget Cameras',
        'Sony Experts',
        'Computer Experts',
        'Office King',
        'Mercedes-Benz',
    ];

    protected const int MAX_AVAILABLE_VALUES = 5;

    protected const string LABEL_REQUESTED = 'SALE';

    protected const string LABEL_AVAILABLE = 'New';

    protected const int PRICE_MAX_BELOW_RANGE = 10;

    protected const int PRICE_RANGE_MIN = 2500;

    protected const int PRICE_RANGE_MAX = 345700;

    protected const int PRICE_PRECISION = 100;

    protected const int RATING_REQUESTED = 5;

    protected const int RATING_RANGE_MIN = 1;

    protected const int RATING_RANGE_MAX = 3;

    protected const int TOTAL_RESULTS_QUERY_ONLY = 3;

    protected const string LONGEST_WORD_ADVICE = 'longest word';

    protected const array COLORS_AVAILABLE = ['Black', 'White', 'Gold', 'Blue', 'Silver', 'Green', 'Orange'];

    protected const int RATING_RANGE_ROUNDED_UP = 5;

    protected const int TOTAL_RESULTS_WITH_MATCHES = 63;

    protected AiCommerceClientTester $tester;

    public function testGivenNoMatchCausedByAFilterWhenExecutedThenTheReplyNamesThatFilterAndItsAvailableValues(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_COLOR => true]);
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createCatalogSearchFacet(static::FACET_COLOR, [static::COLOR_AVAILABLE_FIRST, static::COLOR_AVAILABLE_SECOND]),
                $this->tester->createCatalogSearchFacet(static::FACET_CATEGORY, [static::ID_CATEGORY_OTHER]),
            ]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
            static::PARAMETER_FILTERS => [static::FACET_COLOR => static::COLOR_REQUESTED],
        ]);

        // Assert
        $this->assertCount(1, $result[static::KEY_EMPTIED_BY]);
        $emptiedBy = $result[static::KEY_EMPTIED_BY][0];
        $this->assertSame(static::ARGUMENT_FILTER_COLOR, $emptiedBy[static::KEY_ARGUMENT]);
        $this->assertSame([static::COLOR_REQUESTED], $emptiedBy[static::KEY_REQUESTED]);
        $this->assertSame(
            [static::COLOR_AVAILABLE_FIRST, static::COLOR_AVAILABLE_SECOND],
            array_column($emptiedBy[static::KEY_AVAILABLE_VALUES], static::KEY_VALUE),
        );
        $this->assertStringContainsString(static::ARGUMENT_FILTER_COLOR, $result[static::KEY_SUGGESTED_NEXT_ACTION]);
    }

    public function testGivenNoMatchCausedByAFilterWhenExecutedThenNoSpellingRetryIsSuggested(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets(
                [$this->tester->createCatalogSearchFacet(static::FACET_CATEGORY, [static::ID_CATEGORY_OTHER])],
                static::SPELLING_SUGGESTION_UNRELATED,
            ),
            $this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_QUERY_ONLY),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_QUERY => static::QUERY_WITH_FILTERS,
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
        ]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_DID_YOU_MEAN, $result);
        $this->assertStringNotContainsString(static::KEY_DID_YOU_MEAN, $result[static::KEY_SUGGESTED_NEXT_ACTION]);
        $this->assertSame(static::PARAMETER_CATEGORY, $result[static::KEY_EMPTIED_BY][0][static::KEY_ARGUMENT]);
        $this->assertCount(2, $capturedCalls);
        $this->assertSame(static::QUERY_WITH_FILTERS, $capturedCalls[1][AiCommerceClientTester::CAPTURED_CALL_KEY_SEARCH_STRING]);
        $this->assertArrayNotHasKey(
            static::FACET_CATEGORY,
            $capturedCalls[1][AiCommerceClientTester::CAPTURED_CALL_KEY_REQUEST_PARAMETERS],
        );
    }

    public function testGivenNoMatchForTheWordsAloneWhenExecutedThenTheSpellingSuggestionIsStillOffered(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([], static::SPELLING_SUGGESTION),
            $this->tester->createZeroResultCatalogSearchResultWithFacets([], static::SPELLING_SUGGESTION),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_QUERY => static::QUERY_MISSPELLED,
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
        ]);

        // Assert
        $this->assertSame(static::SPELLING_SUGGESTION, $result[static::KEY_DID_YOU_MEAN]);
        $this->assertStringContainsString(static::KEY_DID_YOU_MEAN, $result[static::KEY_SUGGESTED_NEXT_ACTION]);
        $this->assertArrayNotHasKey(static::KEY_EMPTIED_BY, $result);
        $this->assertCount(2, $capturedCalls);
    }

    public function testGivenNoMatchWithoutConstraintsWhenExecutedThenNoProbeIsRun(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([], static::SPELLING_SUGGESTION),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY_MISSPELLED]);

        // Assert
        $this->assertCount(1, $capturedCalls);
        $this->assertSame(static::SPELLING_SUGGESTION, $result[static::KEY_DID_YOU_MEAN]);
        $this->assertArrayNotHasKey(static::KEY_EMPTIED_BY, $result);
    }

    public function testGivenNoMatchInBrowseModeWhenExecutedThenTheConstraintIsBlamedWithoutAProbe(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createCatalogSearchFacet(static::FACET_LABEL, [static::LABEL_AVAILABLE]),
                $this->tester->createCatalogSearchFacet(static::FACET_CATEGORY, [static::ID_CATEGORY_OTHER]),
            ]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
            static::PARAMETER_LABEL => static::LABEL_REQUESTED,
        ]);

        // Assert
        $this->assertCount(1, $capturedCalls);
        $this->assertCount(1, $result[static::KEY_EMPTIED_BY]);
        $this->assertSame(static::PARAMETER_LABEL, $result[static::KEY_EMPTIED_BY][0][static::KEY_ARGUMENT]);
        $this->assertSame(
            [static::LABEL_AVAILABLE],
            array_column($result[static::KEY_EMPTIED_BY][0][static::KEY_AVAILABLE_VALUES], static::KEY_VALUE),
        );
    }

    public function testGivenAnUnknownMerchantWhenExecutedThenTheReplyOffersAtMostFiveSellersThatDoMatch(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_MERCHANT_NAME => true]);
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createCatalogSearchFacet(static::FACET_MERCHANT_NAME, static::MERCHANTS_AVAILABLE),
            ]),
            $this->tester->createCatalogSearchResultWithTotal(static::TOTAL_RESULTS_QUERY_ONLY),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_MERCHANT => static::MERCHANT_UNKNOWN,
        ]);

        // Assert
        $emptiedBy = $result[static::KEY_EMPTIED_BY][0];
        $this->assertSame(static::PARAMETER_MERCHANT, $emptiedBy[static::KEY_ARGUMENT]);
        $this->assertSame([static::MERCHANT_UNKNOWN], $emptiedBy[static::KEY_REQUESTED]);
        $this->assertCount(static::MAX_AVAILABLE_VALUES, $emptiedBy[static::KEY_AVAILABLE_VALUES]);

        foreach ($emptiedBy[static::KEY_AVAILABLE_VALUES] as $availableValue) {
            $this->assertContains($availableValue[static::KEY_VALUE], static::MERCHANTS_AVAILABLE);
            $this->assertArrayHasKey(static::KEY_MATCH_COUNT, $availableValue);
        }

        $this->assertStringContainsString('`' . static::PARAMETER_MERCHANT . '`', $result[static::KEY_SUGGESTED_NEXT_ACTION]);
        $this->assertArrayNotHasKey(static::KEY_DID_YOU_MEAN, $result);
    }

    public function testGivenNoMatchCausedByThePriceWindowWhenExecutedThenTheReplyNamesTheAvailablePriceRange(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createRangeFacet(
                    static::FACET_NAME_PRICE,
                    static::REQUEST_PARAMETER_PRICE,
                    static::PRICE_RANGE_MIN,
                    static::PRICE_RANGE_MAX,
                ),
            ]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
            static::PARAMETER_PRICE_MAX => static::PRICE_MAX_BELOW_RANGE,
        ]);

        // Assert
        $this->assertCount(1, $result[static::KEY_EMPTIED_BY]);
        $emptiedBy = $result[static::KEY_EMPTIED_BY][0];
        $this->assertSame(static::PARAMETER_PRICE_MAX, $emptiedBy[static::KEY_ARGUMENT]);
        $this->assertEquals(static::PRICE_MAX_BELOW_RANGE, $emptiedBy[static::KEY_REQUESTED][static::KEY_MAX]);
        $this->assertEquals(static::PRICE_RANGE_MIN / static::PRICE_PRECISION, $emptiedBy[static::KEY_AVAILABLE_RANGE][static::KEY_MIN]);
        $this->assertEquals(static::PRICE_RANGE_MAX / static::PRICE_PRECISION, $emptiedBy[static::KEY_AVAILABLE_RANGE][static::KEY_MAX]);
    }

    public function testGivenNoMatchCausedByTheRatingWhenExecutedThenTheReplyNamesTheAvailableRatingRange(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createRangeFacet(
                    static::FACET_NAME_RATING,
                    static::REQUEST_PARAMETER_RATING,
                    static::RATING_RANGE_MIN,
                    static::RATING_RANGE_MAX,
                ),
            ]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
            static::PARAMETER_RATING_MIN => static::RATING_REQUESTED,
        ]);

        // Assert
        $this->assertCount(1, $result[static::KEY_EMPTIED_BY]);
        $emptiedBy = $result[static::KEY_EMPTIED_BY][0];
        $this->assertSame(static::PARAMETER_RATING_MIN, $emptiedBy[static::KEY_ARGUMENT]);
        $this->assertSame(static::RATING_REQUESTED, $emptiedBy[static::KEY_REQUESTED]);
        $this->assertSame(
            [static::KEY_MIN => static::RATING_RANGE_MIN, static::KEY_MAX => static::RATING_RANGE_MAX],
            $emptiedBy[static::KEY_AVAILABLE_RANGE],
        );
    }

    public function testGivenNoConstraintThatAloneEmptiesTheResultWhenExecutedThenEveryConstraintIsNamed(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_COLOR => true, static::FACET_MERCHANT_NAME => true]);
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_MERCHANT => static::MERCHANT_UNKNOWN,
            static::PARAMETER_FILTERS => [static::FACET_COLOR => static::COLOR_REQUESTED],
        ]);

        // Assert
        $arguments = array_column($result[static::KEY_EMPTIED_BY], static::KEY_ARGUMENT);
        $this->assertEqualsCanonicalizing([static::ARGUMENT_FILTER_COLOR, static::PARAMETER_MERCHANT], $arguments);

        foreach ($arguments as $argument) {
            $this->assertStringContainsString($argument, $result[static::KEY_SUGGESTED_NEXT_ACTION]);
        }
    }

    public function testGivenASellerAndAnAttributeWithoutMatchWhenExecutedThenBothAreNamedSoTheSellerIsNotSilentlyDropped(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_COLOR => true, static::FACET_MERCHANT_NAME => true]);
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createCatalogSearchFacet(static::FACET_COLOR, [static::COLOR_AVAILABLE_SECOND]),
                $this->tester->createCatalogSearchFacet(static::FACET_MERCHANT_NAME, static::MERCHANTS_AVAILABLE),
            ]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_MERCHANT => static::MERCHANT_UNKNOWN,
            static::PARAMETER_FILTERS => [static::FACET_COLOR => static::COLOR_REQUESTED],
        ]);

        // Assert
        $this->assertSame(
            [static::ARGUMENT_FILTER_COLOR, static::PARAMETER_MERCHANT],
            array_column($result[static::KEY_EMPTIED_BY], static::KEY_ARGUMENT),
        );
        $this->assertStringContainsString('`' . static::PARAMETER_MERCHANT . '`', $result[static::KEY_SUGGESTED_NEXT_ACTION]);
    }

    public function testGivenNoMatchForTheWordsAndNoSpellingSuggestionWhenExecutedThenTheReplyAdvisesDroppingTheLongestWord(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([static::PARAMETER_QUERY => static::QUERY_WITH_FILTERS]);

        // Assert
        $this->assertStringContainsString(static::LONGEST_WORD_ADVICE, $result[static::KEY_SUGGESTED_NEXT_ACTION]);
        $this->assertArrayNotHasKey(static::KEY_EMPTIED_BY, $result);
    }

    public function testGivenNoMatchCausedByAnAttributeWhenExecutedThenEveryValueTheRefinementOffersIsAvailable(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpFilterableFacetConfigs([static::FACET_COLOR => true]);
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createCatalogSearchFacet(static::FACET_COLOR, static::COLORS_AVAILABLE),
            ]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
            static::PARAMETER_FILTERS => [static::FACET_COLOR => static::COLOR_REQUESTED],
        ]);

        // Assert
        $this->assertSame(
            static::COLORS_AVAILABLE,
            array_column($result[static::KEY_EMPTIED_BY][0][static::KEY_AVAILABLE_VALUES], static::KEY_VALUE),
        );
    }

    public function testGivenNoMatchForARatingTheRoundedRangeSeemsToReachWhenExecutedThenTheAvailableRangeStaysBelowTheRequestedRating(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createZeroResultCatalogSearchResultWithFacets([
                $this->tester->createRangeFacet(
                    static::FACET_NAME_RATING,
                    static::REQUEST_PARAMETER_RATING,
                    static::RATING_RANGE_ROUNDED_UP,
                    static::RATING_RANGE_ROUNDED_UP,
                ),
            ]),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
            static::PARAMETER_RATING_MIN => static::RATING_REQUESTED,
        ]);

        // Assert
        $this->assertCount(1, $result[static::KEY_EMPTIED_BY]);
        $availableRange = $result[static::KEY_EMPTIED_BY][0][static::KEY_AVAILABLE_RANGE];
        $this->assertLessThan(static::RATING_REQUESTED, $availableRange[static::KEY_MAX]);
        $this->assertLessThanOrEqual($availableRange[static::KEY_MAX], $availableRange[static::KEY_MIN]);
    }

    public function testGivenMatchesAndASpellingSuggestionWhenExecutedThenNoSpellingSuggestionIsReturned(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $capturedCalls = [];
        $this->tester->setUpCatalogSearchResultsInCallOrder([
            $this->tester->createCatalogSearchResultWithSpellingSuggestion(
                static::TOTAL_RESULTS_WITH_MATCHES,
                static::SPELLING_SUGGESTION_UNRELATED,
            ),
        ], $capturedCalls);

        // Act
        $result = (new CatalogSearchToolPlugin())->execute([
            static::PARAMETER_QUERY => static::QUERY_WITH_FILTERS,
            static::PARAMETER_CATEGORY => static::ID_CATEGORY_REQUESTED,
        ]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_DID_YOU_MEAN, $result);
        $this->assertArrayNotHasKey(static::KEY_EMPTIED_BY, $result);
        $this->assertCount(1, $capturedCalls);
    }
}
