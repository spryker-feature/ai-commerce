<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\StorefrontAssistant;

enum StorefrontAssistantMessageRole: string
{
    case User = 'user';

    case Assistant = 'ai';

    case ToolResult = 'tool_result';
}
