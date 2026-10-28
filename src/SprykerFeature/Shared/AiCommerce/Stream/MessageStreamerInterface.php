<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

interface MessageStreamerInterface
{
    public function startTurn(): StreamMessageState;

    public function abortTurn(): void;

    /**
     * @param array<string, mixed> $messageMetadata
     */
    public function startMessage(StreamMessageState $streamMessageState, array $messageMetadata = []): void;

    public function ensureMessageStarted(StreamMessageState $streamMessageState): void;

    public function emitAgent(string $agentName): void;

    public function emitContentBlock(StreamEventBlockType $streamEventBlockType, string $content): void;

    public function emitError(string $errorText): void;

    public function finishMessage(StreamMessageState $streamMessageState): void;
}
