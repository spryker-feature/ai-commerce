<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

/**
 * The streamed content blocks of the protocol that are delimited by a start and an end event.
 *
 * @see https://ai-sdk.dev/docs/ai-sdk-ui/stream-protocol
 */
enum StreamEventBlockType: string
{
    case Text = 'text';
    case Reasoning = 'reasoning';

    public function startEventType(): StreamEventType
    {
        return match ($this) {
            static::Text => StreamEventType::TextStart,
            static::Reasoning => StreamEventType::ReasoningStart,
        };
    }

    public function deltaEventType(): StreamEventType
    {
        return match ($this) {
            static::Text => StreamEventType::TextDelta,
            static::Reasoning => StreamEventType::ReasoningDelta,
        };
    }

    public function endEventType(): StreamEventType
    {
        return match ($this) {
            static::Text => StreamEventType::TextEnd,
            static::Reasoning => StreamEventType::ReasoningEnd,
        };
    }
}
