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
interface StreamEventKey
{
    public const string TYPE = 'type';

    public const string ID = 'id';

    public const string MESSAGE_ID = 'messageId';

    public const string DELTA = 'delta';

    public const string TOOL_CALL_ID = 'toolCallId';

    public const string TOOL_NAME = 'toolName';

    public const string INPUT = 'input';

    public const string OUTPUT = 'output';

    public const string ERROR_TEXT = 'errorText';

    public const string DATA = 'data';
}
