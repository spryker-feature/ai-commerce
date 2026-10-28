<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;

interface PromptProcessorInterface
{
    /**
     * Specification:
     * - Resolves or creates a conversation reference for the user.
     * - Routes the prompt to the appropriate agent via the intent router.
     * - Executes the selected agent and streams the turn as `StreamEventType` events.
     * - Opens with `start`, carrying the conversation reference and the agent known at turn start in `messageMetadata`.
     * - Emits `data-agent` when the agent changes during the turn, and closes with `finish` and `[DONE]`.
     */
    public function process(BackofficeAssistantPromptRequestTransfer $promptRequestTransfer): void;
}
