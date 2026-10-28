<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Stream;

use ArrayObject;
use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\Stream\MessageStreamer;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTracker;
use SprykerFeature\Shared\AiCommerce\Stream\StreamDataEventName;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventBlockType;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitterInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Stream
 * @group MessageStreamerTest
 */
class MessageStreamerTest extends Unit
{
    protected const string DONE_MARKER = '[DONE]';

    protected const string CONVERSATION_REFERENCE = 'conversation-reference-1';

    protected const string AGENT_NAME = 'General';

    protected const string CONTENT = 'Hello';

    protected const string ERROR_TEXT = 'The AI service is not available.';

    /**
     * @var \ArrayObject<int, array<string, mixed>|string>
     */
    protected ArrayObject $recordedEvents;

    protected StreamBlockTracker $streamBlockTracker;

    protected MessageStreamer $messageStreamer;

    protected function _before(): void
    {
        $this->recordedEvents = new ArrayObject();
        $streamEventEmitter = $this->createRecordingStreamEventEmitter();
        $this->streamBlockTracker = new StreamBlockTracker($streamEventEmitter);
        $this->messageStreamer = new MessageStreamer($streamEventEmitter, $this->streamBlockTracker);
    }

    protected function _after(): void
    {
        $this->streamBlockTracker->resetBlocks();
    }

    public function testGivenNoMetadataWhenTheMessageStartsThenTheStartEventCarriesOnlyAMessageId(): void
    {
        // Arrange
        $streamMessageState = $this->messageStreamer->startTurn();

        // Act
        $this->messageStreamer->startMessage($streamMessageState);

        // Assert
        $this->assertTrue($streamMessageState->isMessageStarted());
        $this->assertSame([StreamEventType::Start->value], $this->extractRecordedEventTypes());
        $this->assertArrayHasKey(StreamEventKey::MESSAGE_ID, $this->recordedEvents[0]);
        $this->assertArrayNotHasKey(StreamEventKey::MESSAGE_METADATA, $this->recordedEvents[0]);
    }

    public function testGivenMetadataWhenTheMessageStartsThenTheStartEventCarriesIt(): void
    {
        // Arrange
        $streamMessageState = $this->messageStreamer->startTurn();
        $messageMetadata = [StreamEventKey::CONVERSATION_REFERENCE => static::CONVERSATION_REFERENCE];

        // Act
        $this->messageStreamer->startMessage($streamMessageState, $messageMetadata);

        // Assert
        $this->assertSame($messageMetadata, $this->recordedEvents[0][StreamEventKey::MESSAGE_METADATA]);
    }

    public function testGivenAMessageThatNeverStartedWhenItFinishesThenItIsStartedBeforeTheFinish(): void
    {
        // Arrange
        $streamMessageState = $this->messageStreamer->startTurn();

        // Act
        $this->messageStreamer->finishMessage($streamMessageState);

        // Assert
        $this->assertSame(
            [StreamEventType::Start->value, StreamEventType::Finish->value, static::DONE_MARKER],
            $this->extractRecordedEventTypes(),
        );
    }

    public function testGivenAStartedMessageWhenItFinishesThenItIsNotStartedAgain(): void
    {
        // Arrange
        $streamMessageState = $this->messageStreamer->startTurn();
        $this->messageStreamer->startMessage($streamMessageState);

        // Act
        $this->messageStreamer->finishMessage($streamMessageState);

        // Assert
        $this->assertSame(
            [StreamEventType::Start->value, StreamEventType::Finish->value, static::DONE_MARKER],
            $this->extractRecordedEventTypes(),
        );
    }

    public function testGivenAStartedMessageWhenItStartsAgainThenNoSecondStartEventIsEmitted(): void
    {
        // Arrange
        $streamMessageState = $this->messageStreamer->startTurn();
        $this->messageStreamer->startMessage($streamMessageState);

        // Act
        $this->messageStreamer->startMessage($streamMessageState);

        // Assert
        $this->assertSame([StreamEventType::Start->value], $this->extractRecordedEventTypes());
    }

    public function testGivenAnOpenBlockWhenTheTurnIsAbortedThenTheNextFinishDoesNotCloseIt(): void
    {
        // Arrange
        $streamMessageState = $this->messageStreamer->startTurn();
        $this->messageStreamer->startMessage($streamMessageState);
        $this->streamBlockTracker->openBlockId(StreamEventBlockType::Reasoning);

        // Act
        $this->messageStreamer->abortTurn();
        $this->messageStreamer->finishMessage($streamMessageState);

        // Assert
        $this->assertSame(
            [StreamEventType::Start->value, StreamEventType::ReasoningStart->value, StreamEventType::Finish->value, static::DONE_MARKER],
            $this->extractRecordedEventTypes(),
        );
    }

    public function testGivenAContentBlockWhenItIsEmittedThenItIsOpenedFilledAndClosed(): void
    {
        // Arrange
        $this->messageStreamer->startTurn();

        // Act
        $this->messageStreamer->emitContentBlock(StreamEventBlockType::Text, static::CONTENT);

        // Assert
        $this->assertSame(
            [StreamEventType::TextStart->value, StreamEventType::TextDelta->value, StreamEventType::TextEnd->value],
            $this->extractRecordedEventTypes(),
        );
        $this->assertSame(static::CONTENT, $this->recordedEvents[1][StreamEventKey::DELTA]);
    }

    public function testGivenAnOpenBlockWhenAnErrorIsEmittedThenTheBlockIsClosedFirst(): void
    {
        // Arrange
        $this->messageStreamer->startTurn();
        $this->streamBlockTracker->openBlockId(StreamEventBlockType::Reasoning);

        // Act
        $this->messageStreamer->emitError(static::ERROR_TEXT);

        // Assert
        $this->assertSame(
            [StreamEventType::ReasoningStart->value, StreamEventType::ReasoningEnd->value, StreamEventType::Error->value],
            $this->extractRecordedEventTypes(),
        );
        $this->assertSame(static::ERROR_TEXT, $this->recordedEvents[2][StreamEventKey::ERROR_TEXT]);
    }

    public function testGivenAnAgentWhenItIsEmittedThenTheAgentDataEventCarriesItsName(): void
    {
        // Act
        $this->messageStreamer->emitAgent(static::AGENT_NAME);

        // Assert
        $this->assertSame([StreamDataEventName::AGENT], $this->extractRecordedEventTypes());
        $this->assertSame([StreamEventKey::NAME => static::AGENT_NAME], $this->recordedEvents[0][StreamEventKey::DATA]);
    }

    protected function createRecordingStreamEventEmitter(): StreamEventEmitterInterface
    {
        return new class ($this->recordedEvents, static::DONE_MARKER) implements StreamEventEmitterInterface {
            /**
             * @param \ArrayObject<int, array<string, mixed>|string> $recordedEvents
             */
            public function __construct(protected ArrayObject $recordedEvents, protected string $doneMarker)
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
                $this->recordedEvents->append($this->doneMarker);
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
