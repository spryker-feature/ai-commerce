<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\AiFoundation;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AiToolCallTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptStreamChunkTransfer;
use Spryker\Shared\AiFoundation\Stream\PromptStreamChunkType;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSsePostToolCallPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSsePreToolCallPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSseStreamEventPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group AiFoundation
 * @group StorefrontAssistantSsePluginTest
 */
class StorefrontAssistantSsePluginTest extends Unit
{
    protected const string AI_CONFIGURATION_NAME = 'AI_COMMERCE:AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI';

    protected const string INTERNAL_TOOL_NAME = 'catalog_search';

    protected const string STREAMED_TEXT = 'Hello';

    protected const string PROVIDER_TOOL_CALL_ID = 'call_provider_generated_1';

    protected const string PROVIDER_TOOL_CALL_ID_SECOND = 'call_provider_generated_2';

    protected const string SYNTHETIC_TOOL_CALL_ID_PREFIX = 'call_';

    protected AiCommerceClientTester $tester;

    public function testGivenAnAllowlistedConfigurationWhenAToolCallStartsThenTheToolInputPartsAreEmitted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePreToolCallPlugin())->preToolCall($this->createAiToolCallTransfer());
        });

        // Assert
        $this->assertSame(
            [StreamEventType::ToolInputStart->value, StreamEventType::ToolInputAvailable->value],
            $this->tester->extractStreamedPartTypes($streamedParts),
        );
    }

    public function testGivenACustomerFacingToolWhenItCompletesThenTheToolOutputPartIsEmitted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePostToolCallPlugin())->postToolCall($this->createAiToolCallTransfer());
        });

        // Assert
        $this->assertSame(
            [StreamEventType::ToolOutputAvailable->value],
            $this->tester->extractStreamedPartTypes($streamedParts),
        );
    }

    public function testGivenAToolThatWasNotAllowedToRunWhenItCompletesThenTheDeniedPartIsEmitted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePostToolCallPlugin())->postToolCall(
                $this->createAiToolCallTransfer()->setIsExecutionAllowed(false),
            );
        });

        // Assert
        $this->assertSame(
            [StreamEventType::ToolOutputDenied->value],
            $this->tester->extractStreamedPartTypes($streamedParts),
        );
    }

    public function testGivenAToolInternalToTheAgentWhenItCompletesThenNothingReachesTheCustomer(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePostToolCallPlugin())->postToolCall(
                $this->createAiToolCallTransfer(static::INTERNAL_TOOL_NAME),
            );
        });

        // Assert
        $this->assertSame('', $streamedParts);
    }

    public function testGivenATextChunkWhenItIsStreamedThenTheBlockIsOpenedAndTheDeltaIsEmitted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSseStreamEventPlugin())->onStreamEvent(
                (new PromptStreamChunkTransfer())
                    ->setType(PromptStreamChunkType::Text->value)
                    ->setContent(static::STREAMED_TEXT),
                (new PromptRequestTransfer())->setAiConfigurationName(static::AI_CONFIGURATION_NAME),
            );
        });

        // Assert
        $this->assertContains(
            StreamEventType::TextDelta->value,
            $this->tester->extractStreamedPartTypes($streamedParts),
        );
    }

    public function testGivenAConfigurationOutsideTheAllowlistWhenAToolCallStartsThenNothingIsEmitted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePreToolCallPlugin())->preToolCall(
                $this->createAiToolCallTransfer()->setPromptRequest(
                    (new PromptRequestTransfer())->setAiConfigurationName('AI_COMMERCE:AI_CONFIGURATION_BACKOFFICE_ASSISTANT'),
                ),
            );
        });

        // Assert
        $this->assertSame('', $streamedParts);
    }

    public function testGivenAProviderToolCallIdWhenAToolCallStartsThenTheEmittedPartsCarryIt(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePreToolCallPlugin())->preToolCall(
                $this->createAiToolCallTransfer()->setToolCallId(static::PROVIDER_TOOL_CALL_ID),
            );
        });

        // Assert
        $this->assertSame(
            [static::PROVIDER_TOOL_CALL_ID, static::PROVIDER_TOOL_CALL_ID],
            $this->tester->extractStreamedToolCallIds($streamedParts),
        );
    }

    public function testGivenAProviderToolCallIdWhenTheToolCompletesThenTheOutputPartCarriesIt(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePostToolCallPlugin())->postToolCall(
                $this->createAiToolCallTransfer()->setToolCallId(static::PROVIDER_TOOL_CALL_ID),
            );
        });

        // Assert
        $this->assertSame(
            [static::PROVIDER_TOOL_CALL_ID],
            $this->tester->extractStreamedToolCallIds($streamedParts),
        );
    }

    public function testGivenTwoParallelCallsToTheSameToolWhenTheyCompleteThenEachPairKeepsItsOwnProviderToolCallId(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            $preToolCallPlugin = new StorefrontAssistantSsePreToolCallPlugin();
            $postToolCallPlugin = new StorefrontAssistantSsePostToolCallPlugin();

            $preToolCallPlugin->preToolCall(
                $this->createAiToolCallTransfer()->setToolCallId(static::PROVIDER_TOOL_CALL_ID),
            );
            $preToolCallPlugin->preToolCall(
                $this->createAiToolCallTransfer()->setToolCallId(static::PROVIDER_TOOL_CALL_ID_SECOND),
            );

            $postToolCallPlugin->postToolCall(
                $this->createAiToolCallTransfer()->setToolCallId(static::PROVIDER_TOOL_CALL_ID),
            );
            $postToolCallPlugin->postToolCall(
                $this->createAiToolCallTransfer()->setToolCallId(static::PROVIDER_TOOL_CALL_ID_SECOND),
            );
        });

        // Assert
        $this->assertSame(
            [
                static::PROVIDER_TOOL_CALL_ID,
                static::PROVIDER_TOOL_CALL_ID,
                static::PROVIDER_TOOL_CALL_ID_SECOND,
                static::PROVIDER_TOOL_CALL_ID_SECOND,
                static::PROVIDER_TOOL_CALL_ID,
                static::PROVIDER_TOOL_CALL_ID_SECOND,
            ],
            $this->tester->extractStreamedToolCallIds($streamedParts),
        );
    }

    public function testGivenNoProviderToolCallIdWhenAToolCallStartsThenASyntheticIdentifierIsEmitted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            (new StorefrontAssistantSsePreToolCallPlugin())->preToolCall($this->createAiToolCallTransfer());
        });

        // Assert
        $toolCallIds = $this->tester->extractStreamedToolCallIds($streamedParts);
        $this->assertCount(2, $toolCallIds);
        $this->assertSame($toolCallIds[0], $toolCallIds[1]);
        $this->assertStringStartsWith(static::SYNTHETIC_TOOL_CALL_ID_PREFIX, $toolCallIds[0]);
    }

    public function testGivenNoProviderToolCallIdWhenTheToolCompletesThenTheOutputPartCarriesTheSyntheticIdentifierOfItsInputParts(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();

        // Act
        $streamedParts = $this->tester->captureStreamedOutput(function (): void {
            $aiToolCallTransfer = (new StorefrontAssistantSsePreToolCallPlugin())->preToolCall($this->createAiToolCallTransfer());

            (new StorefrontAssistantSsePostToolCallPlugin())->postToolCall($aiToolCallTransfer);
        });

        // Assert
        $toolCallIds = $this->tester->extractStreamedToolCallIds($streamedParts);
        $this->assertCount(3, $toolCallIds);
        $this->assertSame(array_fill(0, 3, $toolCallIds[0]), $toolCallIds);
        $this->assertStringStartsWith(static::SYNTHETIC_TOOL_CALL_ID_PREFIX, $toolCallIds[0]);
    }

    protected function createAiToolCallTransfer(
        string $toolName = DisplayProductsToolPlugin::TOOL_NAME
    ): AiToolCallTransfer {
        return (new AiToolCallTransfer())
            ->setToolName($toolName)
            ->setToolArguments([])
            ->setToolResult('[]')
            ->setPromptRequest(
                (new PromptRequestTransfer())->setAiConfigurationName(static::AI_CONFIGURATION_NAME),
            );
    }
}
