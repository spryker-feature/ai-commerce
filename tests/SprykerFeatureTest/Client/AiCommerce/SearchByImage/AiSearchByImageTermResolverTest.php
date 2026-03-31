<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\SearchByImage;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PromptResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\SearchByImage\AiSearchByImageTermResolver;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group SearchByImage
 * @group AiSearchByImageTermResolverTest
 */
class AiSearchByImageTermResolverTest extends Unit
{
    protected const string SEARCH_TERM = 'running shoes';

    protected const string ERROR_MESSAGE = 'ai_commerce.search_by_image.error.unavailable';

    protected AiCommerceClientTester $tester;

    public function testGivenValidImageWhenAiRespondsWithStructuredMessageThenSearchTermIsReturned(): void
    {
        // Arrange
        $resolver = $this->createResolver($this->createAiClientMock($this->tester->createSuccessfulSearchByImagePromptResponse(static::SEARCH_TERM)));

        // Act
        $response = $resolver->getSearchTermFromImage($this->tester->createSearchByImageRequest());

        // Assert
        $this->assertTrue($response->getIsSuccessful());
        $this->assertSame(static::SEARCH_TERM, $response->getSearchTerm());
    }

    public function testGivenValidImageWhenAiFailsThenErrorsAreForwardedInResponse(): void
    {
        // Arrange
        $resolver = $this->createResolver($this->createAiClientMock($this->tester->createFailedPromptResponse(static::ERROR_MESSAGE)));

        // Act
        $response = $resolver->getSearchTermFromImage($this->tester->createSearchByImageRequest());

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE, $response->getErrors()->offsetGet(0)->getMessageOrFail());
    }

    public function testGivenAiReturnsSuccessWithNoStructuredMessageWhenResolvingThenNullValueExceptionIsThrown(): void
    {
        // Arrange
        $resolver = $this->createResolver($this->createAiClientMock((new PromptResponseTransfer())->setIsSuccessful(true)));

        // Act
        $response = $resolver->getSearchTermFromImage($this->tester->createSearchByImageRequest());

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE, $response->getErrors()->offsetGet(0)->getMessageOrFail());
    }

    protected function createResolver(AiFoundationClientInterface $aiFoundationClient): AiSearchByImageTermResolver
    {
        return new AiSearchByImageTermResolver($aiFoundationClient, new AiCommerceConfig());
    }

    protected function createAiClientMock(PromptResponseTransfer $response): AiFoundationClientInterface|MockObject
    {
        $mock = $this->createMock(AiFoundationClientInterface::class);
        $mock->method('prompt')->willReturn($response);

        return $mock;
    }
}
