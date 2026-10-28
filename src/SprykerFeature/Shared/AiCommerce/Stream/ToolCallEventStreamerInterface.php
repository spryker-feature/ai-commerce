<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

use Generated\Shared\Transfer\AiToolCallTransfer;

interface ToolCallEventStreamerInterface
{
    public function streamToolInput(AiToolCallTransfer $aiToolCallTransfer): AiToolCallTransfer;

    public function streamToolOutput(AiToolCallTransfer $aiToolCallTransfer): void;
}
