<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Emitter;

use SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType;

class SseEventEmitter implements SseEventEmitterInterface
{
    protected const string KEY_TYPE = 'type';

    /**
     * {@inheritDoc}
     */
    public function emit(BackofficeAssistantEventType $type, array $payload): void
    {
        $encoded = json_encode(array_merge([static::KEY_TYPE => $type->value], $payload));

        if ($encoded === false) {
            return;
        }

        echo sprintf("data: %s\n\n", $encoded);
        ob_flush();
        flush();
    }
}
