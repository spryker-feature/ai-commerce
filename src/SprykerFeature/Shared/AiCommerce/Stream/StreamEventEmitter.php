<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

use JsonException;
use Spryker\Shared\Log\LoggerTrait;

class StreamEventEmitter implements StreamEventEmitterInterface
{
    use LoggerTrait;

    protected const string SSE_FRAME_FORMAT = "data: %s\n\n";

    protected const string DATA_EVENT_TYPE_PREFIX = 'data-';

    protected const string DONE_SENTINEL = '[DONE]';

    protected const int JSON_ENCODE_FLAGS = JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

    protected const string ERROR_TEXT_ENCODE_FAILED = 'Failed to encode stream event.';

    protected const string LOG_MESSAGE_ENCODE_FAILED = 'Storefront Assistant stream event could not be JSON-encoded and was replaced by an error event.';

    public function emitEvent(StreamEventType $streamEventType, array $fields = []): void
    {
        $this->write([StreamEventKey::TYPE => $streamEventType->value] + $fields);
    }

    public function emitDataEvent(string $name, array $data): void
    {
        $this->write([
            StreamEventKey::TYPE => static::DATA_EVENT_TYPE_PREFIX . $name,
            StreamEventKey::DATA => $data,
        ]);
    }

    public function emitDone(): void
    {
        $this->flushFrame(static::DONE_SENTINEL);
    }

    /**
     * @param array<string, mixed> $part
     */
    protected function write(array $part): void
    {
        try {
            $encoded = (string)json_encode($part, static::JSON_ENCODE_FLAGS);
        } catch (JsonException $jsonException) {
            $this->getLogger()->error(static::LOG_MESSAGE_ENCODE_FAILED, [
                'exception' => $jsonException,
                StreamEventKey::TYPE => $part[StreamEventKey::TYPE] ?? null,
            ]);

            $encoded = (string)json_encode([
                StreamEventKey::TYPE => StreamEventType::Error->value,
                StreamEventKey::ERROR_TEXT => static::ERROR_TEXT_ENCODE_FAILED,
            ]);
        }

        $this->flushFrame($encoded);
    }

    protected function flushFrame(string $payload): void
    {
        echo sprintf(static::SSE_FRAME_FORMAT, $payload);

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
