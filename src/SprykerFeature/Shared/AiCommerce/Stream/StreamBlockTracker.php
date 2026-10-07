<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

class StreamBlockTracker implements StreamBlockTrackerInterface
{
    protected const string BLOCK_ID_FORMAT = '%s_%d';

    /**
     * Static because the factory builds a new tracker for the chat streamer and for every stream plugin call; one turn shares its open block through it, reset when the streamer starts a message and closed in its finally.
     */
    protected static ?StreamEventBlockType $openBlockType = null;

    protected static ?string $openBlockId = null;

    protected static int $sequence = 0;

    public function __construct(protected StreamEventEmitterInterface $streamEventEmitter)
    {
    }

    public function openBlockId(StreamEventBlockType $streamEventBlockType): string
    {
        if (static::$openBlockType === $streamEventBlockType && static::$openBlockId !== null) {
            return static::$openBlockId;
        }

        $this->closeOpenBlock();

        static::$openBlockType = $streamEventBlockType;
        static::$openBlockId = sprintf(static::BLOCK_ID_FORMAT, $streamEventBlockType->value, ++static::$sequence);

        $this->streamEventEmitter->emitEvent(
            $streamEventBlockType->startEventType(),
            [StreamEventKey::ID => static::$openBlockId],
        );

        return static::$openBlockId;
    }

    public function closeOpenBlock(): void
    {
        if (static::$openBlockType === null || static::$openBlockId === null) {
            return;
        }

        $openBlockType = static::$openBlockType;
        $openBlockId = static::$openBlockId;

        $this->resetBlocks();

        $this->streamEventEmitter->emitEvent(
            $openBlockType->endEventType(),
            [StreamEventKey::ID => $openBlockId],
        );
    }

    public function resetBlocks(): void
    {
        static::$openBlockType = null;
        static::$openBlockId = null;
    }
}
