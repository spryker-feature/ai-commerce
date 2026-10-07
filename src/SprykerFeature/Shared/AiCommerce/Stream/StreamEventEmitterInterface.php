<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

interface StreamEventEmitterInterface
{
    /**
     * @param array<string, mixed> $fields
     */
    public function emitEvent(StreamEventType $streamEventType, array $fields = []): void;

    /**
     * @param array<string, mixed> $data
     */
    public function emitDataEvent(string $name, array $data): void;

    public function emitDone(): void;
}
