<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\BackofficeAssistant;

enum BackofficeAssistantEventType: string
{
    case Error = 'error';
    case AgentSelected = 'agent_selected';
    case Reasoning = 'reasoning';
    case AiResponse = 'ai_response';
    case ToolCall = 'tool_call';
    case ToolCallResult = 'tool_call_result';
}
