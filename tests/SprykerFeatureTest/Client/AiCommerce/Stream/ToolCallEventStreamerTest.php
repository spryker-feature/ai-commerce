<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Stream;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AiToolCallTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTrackerInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventBlockType;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitterInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallEventStreamer;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallIdResolver;
use SprykerFeature\Shared\AiCommerce\Stream\ToolResultExpanderInterface;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Stream
 * @group ToolCallEventStreamerTest
 */
class ToolCallEventStreamerTest extends Unit
{
    protected const string STREAMED_AI_CONFIGURATION_NAME = 'AI_COMMERCE:STREAMED';

    protected const string OTHER_AI_CONFIGURATION_NAME = 'AI_COMMERCE:OTHER';

    protected const string TOOL_NAME = 'display_products';

    protected const string INTERNAL_TOOL_NAME = 'catalog_search';

    protected const string PROVIDER_TOOL_CALL_ID = 'call_provider_generated_1';

    protected const string TOOL_RESULT = '{"products":[]}';

    protected const string BLOCK_CLOSED = 'block-closed';

    protected const string FIRST_EXPANSION_SUFFIX = '+first';

    protected const string SECOND_EXPANSION_SUFFIX = '+second';

    /**
     * @var \ArrayObject<int, array<string, mixed>|string>
     */
    protected ArrayObject $recordedEvents;

    protected function _before(): void
    {
        $this->recordedEvents = new ArrayObject();
    }

    public function testGivenAStreamedConfigurationWhenAToolCallStartsThenTheOpenBlockIsClosedBeforeTheToolInputEvents(): void
    {
        // Arrange
        $toolCallEventStreamer = $this->createToolCallEventStreamer();

        // Act
        $toolCallEventStreamer->streamToolInput($this->createAiToolCallTransfer());

        // Assert
        $this->assertSame(
            [static::BLOCK_CLOSED, StreamEventType::ToolInputStart->value, StreamEventType::ToolInputAvailable->value],
            $this->extractRecordedEventTypes(),
        );
    }

    public function testGivenAStreamedConfigurationWhenAToolCallStartsThenTheResolvedToolCallIdIsSetOnTheTransfer(): void
    {
        // Arrange
        $toolCallEventStreamer = $this->createToolCallEventStreamer();

        // Act
        $aiToolCallTransfer = $toolCallEventStreamer->streamToolInput($this->createAiToolCallTransfer());

        // Assert
        $this->assertSame(static::PROVIDER_TOOL_CALL_ID, $aiToolCallTransfer->getToolCallId());
        $this->assertSame(static::PROVIDER_TOOL_CALL_ID, $this->recordedEvents[1][StreamEventKey::TOOL_CALL_ID]);
    }

    public function testGivenAConfigurationOutsideTheStreamedListWhenAToolCallStartsThenNothingIsEmitted(): void
    {
        // Arrange
        $toolCallEventStreamer = $this->createToolCallEventStreamer();

        // Act
        $toolCallEventStreamer->streamToolInput($this->createAiToolCallTransfer(static::OTHER_AI_CONFIGURATION_NAME));

        // Assert
        $this->assertSame([], $this->extractRecordedEventTypes());
    }

    public function testGivenNoVisibleToolListWhenAToolCompletesThenTheUnchangedResultIsEmitted(): void
    {
        // Arrange
        $toolCallEventStreamer = $this->createToolCallEventStreamer();

        // Act
        $toolCallEventStreamer->streamToolOutput($this->createAiToolCallTransfer());

        // Assert
        $this->assertSame([StreamEventType::ToolOutputAvailable->value], $this->extractRecordedEventTypes());
        $this->assertSame(static::TOOL_RESULT, $this->recordedEvents[0][StreamEventKey::OUTPUT][StreamEventKey::RESULT]);
    }

    public function testGivenAToolOutsideTheVisibleToolListWhenItCompletesThenNothingIsEmitted(): void
    {
        // Arrange
        $toolCallEventStreamer = $this->createToolCallEventStreamer([static::TOOL_NAME]);

        // Act
        $toolCallEventStreamer->streamToolOutput($this->createAiToolCallTransfer(toolName: static::INTERNAL_TOOL_NAME));

        // Assert
        $this->assertSame([], $this->extractRecordedEventTypes());
    }

    public function testGivenAToolThatWasNotAllowedToRunWhenItCompletesThenTheDeniedEventIsEmitted(): void
    {
        // Arrange
        $toolCallEventStreamer = $this->createToolCallEventStreamer();
        $aiToolCallTransfer = $this->createAiToolCallTransfer()->setIsExecutionAllowed(false);

        // Act
        $toolCallEventStreamer->streamToolOutput($aiToolCallTransfer);

        // Assert
        $this->assertSame([StreamEventType::ToolOutputDenied->value], $this->extractRecordedEventTypes());
    }

    public function testGivenToolResultExpandersWhenAVisibleToolCompletesThenTheExpandersRunInOrder(): void
    {
        // Arrange
        $toolCallEventStreamer = $this->createToolCallEventStreamer([static::TOOL_NAME], [
            $this->createSuffixToolResultExpander(static::FIRST_EXPANSION_SUFFIX),
            $this->createSuffixToolResultExpander(static::SECOND_EXPANSION_SUFFIX),
        ]);

        // Act
        $toolCallEventStreamer->streamToolOutput($this->createAiToolCallTransfer());

        // Assert
        $this->assertSame(
            sprintf('%s%s%s', static::TOOL_RESULT, static::FIRST_EXPANSION_SUFFIX, static::SECOND_EXPANSION_SUFFIX),
            $this->recordedEvents[0][StreamEventKey::OUTPUT][StreamEventKey::RESULT],
        );
    }

    /**
     * @param array<string>|null $visibleToolNames
     * @param array<\SprykerFeature\Shared\AiCommerce\Stream\ToolResultExpanderInterface> $toolResultExpanders
     */
    protected function createToolCallEventStreamer(?array $visibleToolNames = null, array $toolResultExpanders = []): ToolCallEventStreamer
    {
        return new ToolCallEventStreamer(
            $this->createRecordingStreamEventEmitter(),
            $this->createRecordingStreamBlockTracker(),
            new ToolCallIdResolver(),
            [static::STREAMED_AI_CONFIGURATION_NAME],
            $visibleToolNames,
            $toolResultExpanders,
        );
    }

    protected function createAiToolCallTransfer(
        string $aiConfigurationName = self::STREAMED_AI_CONFIGURATION_NAME,
        string $toolName = self::TOOL_NAME
    ): AiToolCallTransfer {
        return (new AiToolCallTransfer())
            ->setPromptRequest((new PromptRequestTransfer())->setAiConfigurationName($aiConfigurationName))
            ->setToolName($toolName)
            ->setToolCallId(static::PROVIDER_TOOL_CALL_ID)
            ->setToolArguments([])
            ->setToolResult(static::TOOL_RESULT);
    }

    protected function createRecordingStreamEventEmitter(): StreamEventEmitterInterface
    {
        return new class ($this->recordedEvents) implements StreamEventEmitterInterface {
            /**
             * @param \ArrayObject<int, array<string, mixed>|string> $recordedEvents
             */
            public function __construct(protected ArrayObject $recordedEvents)
            {
            }

            public function emitEvent(StreamEventType $streamEventType, array $fields = []): void
            {
                $this->recordedEvents->append([StreamEventKey::TYPE => $streamEventType->value] + $fields);
            }

            public function emitDataEvent(string $name, array $data): void
            {
                $this->recordedEvents->append([StreamEventKey::TYPE => $name, StreamEventKey::DATA => $data]);
            }

            public function emitDone(): void
            {
            }
        };
    }

    protected function createRecordingStreamBlockTracker(): StreamBlockTrackerInterface
    {
        return new class ($this->recordedEvents, static::BLOCK_CLOSED) implements StreamBlockTrackerInterface {
            /**
             * @param \ArrayObject<int, array<string, mixed>|string> $recordedEvents
             */
            public function __construct(protected ArrayObject $recordedEvents, protected string $blockClosedMarker)
            {
            }

            public function openBlockId(StreamEventBlockType $streamEventBlockType): string
            {
                return $streamEventBlockType->value;
            }

            public function closeOpenBlock(): void
            {
                $this->recordedEvents->append($this->blockClosedMarker);
            }

            public function resetBlocks(): void
            {
            }
        };
    }

    protected function createSuffixToolResultExpander(string $suffix): ToolResultExpanderInterface
    {
        return new class ($suffix) implements ToolResultExpanderInterface {
            public function __construct(protected string $suffix)
            {
            }

            public function expandToolResult(string $toolName, string $toolResult): string
            {
                return sprintf('%s%s', $toolResult, $this->suffix);
            }
        };
    }

    /**
     * @return list<string>
     */
    protected function extractRecordedEventTypes(): array
    {
        $recordedEventTypes = [];

        foreach ($this->recordedEvents as $recordedEvent) {
            $recordedEventTypes[] = is_string($recordedEvent) ? $recordedEvent : (string)$recordedEvent[StreamEventKey::TYPE];
        }

        return $recordedEventTypes;
    }
}
