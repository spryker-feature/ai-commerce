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
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSuggestToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group CatalogSuggestToolPluginTest
 */
class CatalogSuggestToolPluginTest extends Unit
{
    protected const string PARAMETER_QUERY = 'query';

    protected const string PARAMETER_LIMIT = 'limit';

    protected const string KEY_MATCHING_TERMS = 'matchingTerms';

    protected const string KEY_RESULTS_BY_TYPE = 'resultsByType';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string KEY_NAME = 'name';

    protected const string KEY_URL = 'url';

    protected const string KEY_ID = 'id';

    protected const string TYPE_CMS_PAGE = 'cms_page';

    protected const string TYPE_CATEGORY = 'category';

    protected const string TYPE_PRODUCT_SET = 'product_set';

    protected const string QUERY = 'camera';

    protected const string COMPLETION_TERM = 'canon ixus 160';

    protected const string CMS_PAGE_NAME = 'Shipping and returns';

    protected const string CMS_PAGE_URL = '/en/shipping';

    protected const int CMS_PAGE_ID = 4;

    protected const int LIMIT_ONE = 1;

    protected AiCommerceClientTester $tester;

    public function testGivenBlankQueryWhenExecutedThenNothingIsLookedUp(): void
    {
        // Arrange
        $catalogSuggestToolPlugin = new CatalogSuggestToolPlugin();

        // Act
        $result = $catalogSuggestToolPlugin->execute([static::PARAMETER_QUERY => ' ']);

        // Assert
        $this->assertSame([], $result[static::KEY_MATCHING_TERMS]);
        $this->assertSame([], $result[static::KEY_RESULTS_BY_TYPE]);
    }

    public function testGivenCompletionTermsWhenExecutedThenTheyAreReturnedAsTheCataloguesVocabulary(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSuggestResult(
            $this->tester->createCatalogSuggestResult([static::COMPLETION_TERM], []),
        );
        $catalogSuggestToolPlugin = new CatalogSuggestToolPlugin();

        // Act
        $result = $catalogSuggestToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertContains(static::COMPLETION_TERM, $result[static::KEY_MATCHING_TERMS]);
    }

    public function testGivenAContentPageMatchesWhenExecutedThenItIsReturnedWithItsNameAndUrl(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSuggestResult(
            $this->tester->createCatalogSuggestResult([], [
                static::TYPE_CMS_PAGE => [
                    [
                        'id_cms_page' => static::CMS_PAGE_ID,
                        'name' => static::CMS_PAGE_NAME,
                        'url' => static::CMS_PAGE_URL,
                    ],
                ],
            ]),
        );
        $catalogSuggestToolPlugin = new CatalogSuggestToolPlugin();

        // Act
        $result = $catalogSuggestToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $cmsPage = $result[static::KEY_RESULTS_BY_TYPE][static::TYPE_CMS_PAGE][0];
        $this->assertSame(static::CMS_PAGE_NAME, $cmsPage[static::KEY_NAME]);
        $this->assertSame(static::CMS_PAGE_URL, $cmsPage[static::KEY_URL]);
        $this->assertSame(static::CMS_PAGE_ID, $cmsPage[static::KEY_ID]);
    }

    public function testGivenAHeavyEntityWhenExecutedThenOnlyTheCompactFieldsReachTheAgent(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSuggestResult(
            $this->tester->createCatalogSuggestResult([], [
                static::TYPE_PRODUCT_SET => [
                    [
                        'id_product_set' => 1,
                        'meta_title' => 'HP Product Set',
                        'url' => '/en/hp-set',
                        'image_sets' => ['default' => [['external_url_small' => 'https://example.test/a.png']]],
                        'id_product_abstracts' => [167, 170, 122],
                    ],
                ],
            ]),
        );
        $catalogSuggestToolPlugin = new CatalogSuggestToolPlugin();

        // Act
        $result = $catalogSuggestToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $productSet = $result[static::KEY_RESULTS_BY_TYPE][static::TYPE_PRODUCT_SET][0];
        $this->assertArrayNotHasKey('image_sets', $productSet);
        $this->assertArrayNotHasKey('id_product_abstracts', $productSet);
        $this->assertSame('HP Product Set', $productSet[static::KEY_NAME]);
    }

    public function testGivenALimitWhenExecutedThenEachTypeIsCappedSeparately(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSuggestResult(
            $this->tester->createCatalogSuggestResult([], [
                static::TYPE_CATEGORY => [
                    ['id_category' => 1, 'name' => 'Cameras', 'url' => '/en/cameras'],
                    ['id_category' => 2, 'name' => 'Camcorders', 'url' => '/en/camcorders'],
                ],
            ]),
        );
        $catalogSuggestToolPlugin = new CatalogSuggestToolPlugin();

        // Act
        $result = $catalogSuggestToolPlugin->execute([
            static::PARAMETER_QUERY => static::QUERY,
            static::PARAMETER_LIMIT => static::LIMIT_ONE,
        ]);

        // Assert
        $this->assertCount(static::LIMIT_ONE, $result[static::KEY_RESULTS_BY_TYPE][static::TYPE_CATEGORY]);
    }

    public function testGivenNothingMatchesWhenExecutedThenTheReplyTellsTheModelToRephrase(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $this->tester->setUpCatalogSuggestResult($this->tester->createCatalogSuggestResult([], []));
        $catalogSuggestToolPlugin = new CatalogSuggestToolPlugin();

        // Act
        $result = $catalogSuggestToolPlugin->execute([static::PARAMETER_QUERY => static::QUERY]);

        // Assert
        $this->assertNotSame('', trim($result[static::KEY_SUGGESTED_NEXT_ACTION]));
    }

    public function testGivenThePluginWhenItsContractIsReadThenItExposesTheToolContractAiFoundationRegistersItBy(): void
    {
        // Arrange
        $catalogSuggestToolPlugin = new CatalogSuggestToolPlugin();

        // Act
        $parameters = $catalogSuggestToolPlugin->getParameters();

        // Assert
        $this->assertInstanceOf(ToolPluginInterface::class, $catalogSuggestToolPlugin);
        $this->assertSame(CatalogSuggestToolPlugin::TOOL_NAME, $catalogSuggestToolPlugin->getName());
        $this->assertContainsOnlyInstancesOf(ToolParameterInterface::class, $parameters);
    }
}
