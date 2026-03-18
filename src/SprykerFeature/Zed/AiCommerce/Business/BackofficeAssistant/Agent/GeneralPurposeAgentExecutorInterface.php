<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Agent;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;

interface GeneralPurposeAgentExecutorInterface
{
    /**
     * Specification:
     * - Sends the prompt to AI foundation using the general-purpose AI configuration.
     * - Attaches any provided attachments to the prompt message.
     * - Returns a response transfer with the AI response text and conversation reference.
     */
    public function execute(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
    ): BackofficeAssistantPromptResponseTransfer;
}
