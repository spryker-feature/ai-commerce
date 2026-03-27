<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Emitter;

use SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType;

interface SseEventEmitterInterface
{
    /**
     * Emits an SSE event payload immediately to the output stream.
     *
     * @param array<string, mixed> $payload
     */
    public function emit(BackofficeAssistantEventType $type, array $payload): void;
}
