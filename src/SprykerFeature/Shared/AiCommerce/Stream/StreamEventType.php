<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

/**
 * @see https://ai-sdk.dev/docs/ai-sdk-ui/stream-protocol
 */
enum StreamEventType: string
{
    case Start = 'start';
    case Finish = 'finish';
    case StartStep = 'start-step';
    case FinishStep = 'finish-step';
    case TextStart = 'text-start';
    case TextDelta = 'text-delta';
    case TextEnd = 'text-end';
    case ReasoningStart = 'reasoning-start';
    case ReasoningDelta = 'reasoning-delta';
    case ReasoningEnd = 'reasoning-end';
    case ToolInputStart = 'tool-input-start';
    case ToolInputAvailable = 'tool-input-available';
    case ToolOutputAvailable = 'tool-output-available';
    case ToolOutputDenied = 'tool-output-denied';
    case Error = 'error';
}
