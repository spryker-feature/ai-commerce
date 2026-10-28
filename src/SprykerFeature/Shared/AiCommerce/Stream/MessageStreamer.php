<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

class MessageStreamer implements MessageStreamerInterface
{
    protected const string MESSAGE_ID_FORMAT = 'msg_%s';

    public function __construct(
        protected StreamEventEmitterInterface $streamEventEmitter,
        protected StreamBlockTrackerInterface $streamBlockTracker
    ) {
    }

    public function startTurn(): StreamMessageState
    {
        $this->streamBlockTracker->resetBlocks();

        return new StreamMessageState();
    }

    public function abortTurn(): void
    {
        $this->streamBlockTracker->resetBlocks();
    }

    /**
     * @param array<string, mixed> $messageMetadata
     */
    public function startMessage(StreamMessageState $streamMessageState, array $messageMetadata = []): void
    {
        if ($streamMessageState->isMessageStarted()) {
            return;
        }

        $streamMessageState->markMessageStarted();

        $fields = [StreamEventKey::MESSAGE_ID => sprintf(static::MESSAGE_ID_FORMAT, uniqid())];

        if ($messageMetadata !== []) {
            $fields[StreamEventKey::MESSAGE_METADATA] = $messageMetadata;
        }

        $this->streamEventEmitter->emitEvent(StreamEventType::Start, $fields);
    }

    public function ensureMessageStarted(StreamMessageState $streamMessageState): void
    {
        $this->startMessage($streamMessageState);
    }

    public function emitAgent(string $agentName): void
    {
        $this->streamEventEmitter->emitDataEvent(StreamDataEventName::AGENT, [
            StreamEventKey::NAME => $agentName,
        ]);
    }

    public function emitContentBlock(StreamEventBlockType $streamEventBlockType, string $content): void
    {
        $blockId = $this->streamBlockTracker->openBlockId($streamEventBlockType);

        $this->streamEventEmitter->emitEvent($streamEventBlockType->deltaEventType(), [
            StreamEventKey::ID => $blockId,
            StreamEventKey::DELTA => $content,
        ]);

        $this->streamBlockTracker->closeOpenBlock();
    }

    public function emitError(string $errorText): void
    {
        $this->streamBlockTracker->closeOpenBlock();

        $this->streamEventEmitter->emitEvent(StreamEventType::Error, [
            StreamEventKey::ERROR_TEXT => $errorText,
        ]);
    }

    public function finishMessage(StreamMessageState $streamMessageState): void
    {
        $this->ensureMessageStarted($streamMessageState);
        $this->streamBlockTracker->closeOpenBlock();
        $this->streamEventEmitter->emitEvent(StreamEventType::Finish);
        $this->streamEventEmitter->emitDone();
    }
}
