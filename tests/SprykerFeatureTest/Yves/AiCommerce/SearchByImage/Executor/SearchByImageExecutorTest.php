<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\SearchByImage\Executor;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PromptResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Shared\Kernel\StrategyResolver;
use SprykerFeature\Client\AiCommerce\AiCommerceClientInterface;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Executor\SearchByImageExecutor;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\FirstProductRedirectResolver;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\SearchResultsRedirectResolver;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group SearchByImage
 * @group Executor
 * @group SearchByImageExecutorTest
 */
class SearchByImageExecutorTest extends Unit
{
    protected const string AI_RESPONSE_CONTENT = 'leather wallet';

    protected const string EXPECTED_SEARCH_REDIRECT_URL_FRAGMENT = 'leather+wallet';

    protected const string EXPECTED_PRODUCT_URL = '/en/wallet-123';

    protected const string SUGGESTION_BY_TYPE_KEY = 'suggestionByType';

    protected const string SUGGESTION_TYPE_PRODUCT_ABSTRACT = 'product_abstract';

    protected const string SUGGESTION_PRODUCT_URL_KEY = 'url';

    protected AiCommerceYvesTester $tester;

    public function testGivenValidImageWhenRedirectTypeIsSearchResultsThenSearchUrlIsSetInResponse(): void
    {
        // Arrange
        $aiCommerceClient = $this->tester->createAiCommerceClient(
            $this->createAiFoundationClientMock($this->tester->createSuccessfulSearchByImagePromptResponse(static::AI_RESPONSE_CONTENT)),
        );
        $executor = $this->createSearchByImageExecutor($aiCommerceClient, true);

        // Act
        $response = $executor->execute($this->tester->createSearchByImageRequest());

        // Assert
        $this->assertTrue($response->getIsSuccessful());
        $this->assertStringContainsString(static::EXPECTED_SEARCH_REDIRECT_URL_FRAGMENT, $response->getRedirectUrl());
    }

    public function testGivenValidImageWhenRedirectTypeIsFirstProductThenProductUrlIsSetInResponse(): void
    {
        // Arrange
        $aiCommerceClient = $this->tester->createAiCommerceClient(
            $this->createAiFoundationClientMock($this->tester->createSuccessfulSearchByImagePromptResponse(static::AI_RESPONSE_CONTENT)),
        );
        $executor = $this->createSearchByImageExecutor($aiCommerceClient, true, AiCommerceConfig::SEARCH_BY_IMAGE_REDIRECT_TYPE_FIRST_PRODUCT);

        // Act
        $response = $executor->execute($this->tester->createSearchByImageRequest());

        // Assert
        $this->assertTrue($response->getIsSuccessful());
        $this->assertSame(static::EXPECTED_PRODUCT_URL, $response->getRedirectUrl());
    }

    public function testGivenSearchByImageIsDisabledWhenExecutingThenUnsuccessfulResponseIsReturnedWithoutCallingAi(): void
    {
        // Arrange
        $aiFoundationClientMock = $this->createMock(AiFoundationClientInterface::class);
        $aiFoundationClientMock->expects($this->never())->method('prompt');
        $aiCommerceClient = $this->tester->createAiCommerceClient($aiFoundationClientMock);
        $executor = $this->createSearchByImageExecutor($aiCommerceClient, false);

        // Act
        $response = $executor->execute($this->tester->createSearchByImageRequest());

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertNull($response->getRedirectUrl());
    }

    public function testGivenAiFailureWhenExecutingThenUnsuccessfulResponseWithoutRedirectUrlIsReturned(): void
    {
        // Arrange
        $aiCommerceClient = $this->tester->createAiCommerceClient(
            $this->createAiFoundationClientMock($this->tester->createFailedPromptResponse()),
        );
        $executor = $this->createSearchByImageExecutor($aiCommerceClient, true);

        // Act
        $response = $executor->execute($this->tester->createSearchByImageRequest());

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertNull($response->getRedirectUrl());
    }

    protected function createSearchByImageExecutor(
        AiCommerceClientInterface $aiCommerceClient,
        bool $isEnabled,
        string $redirectType = AiCommerceConfig::SEARCH_BY_IMAGE_REDIRECT_TYPE_SEARCH_RESULTS,
    ): SearchByImageExecutor {
        $routerMock = $this->createMock(UrlGeneratorInterface::class);
        $routerMock->method('generate')->willReturnCallback(
            fn (string $route, array $params) => sprintf('/%s?%s', $route, http_build_query($params)),
        );

        $catalogClientMock = $this->createMock(CatalogClientInterface::class);
        $catalogClientMock->method('catalogSuggestSearch')->willReturn(
            [static::SUGGESTION_BY_TYPE_KEY => [static::SUGGESTION_TYPE_PRODUCT_ABSTRACT => [[static::SUGGESTION_PRODUCT_URL_KEY => static::EXPECTED_PRODUCT_URL]]]],
        );

        $configMock = $this->createMock(AiCommerceConfig::class);
        $configMock->method('isSearchByImageEnabled')->willReturn($isEnabled);
        $configMock->method('getRedirectType')->willReturn($redirectType);

        $searchResultsRedirectResolver = new SearchResultsRedirectResolver($routerMock);

        return new SearchByImageExecutor(
            $aiCommerceClient,
            new StrategyResolver([
                AiCommerceConfig::SEARCH_BY_IMAGE_REDIRECT_TYPE_SEARCH_RESULTS => $searchResultsRedirectResolver,
                AiCommerceConfig::SEARCH_BY_IMAGE_REDIRECT_TYPE_FIRST_PRODUCT => new FirstProductRedirectResolver($catalogClientMock, $searchResultsRedirectResolver),
            ]),
            $configMock,
        );
    }

    protected function createAiFoundationClientMock(PromptResponseTransfer $response): AiFoundationClientInterface|MockObject
    {
        $mock = $this->createMock(AiFoundationClientInterface::class);
        $mock->method('prompt')->willReturn($response);

        return $mock;
    }
}
