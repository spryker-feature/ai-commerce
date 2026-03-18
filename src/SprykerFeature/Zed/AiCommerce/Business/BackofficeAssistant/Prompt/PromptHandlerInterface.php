<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;

interface PromptHandlerInterface
{
    /**
     * Specification:
     * - Resolves or creates a conversation reference for the user.
     * - Routes the prompt to the appropriate agent via the intent router.
     * - Executes the selected agent and returns events describing what happened.
     */
    public function handle(BackofficeAssistantPromptRequestTransfer $promptRequestTransfer): BackofficeAssistantPromptResponseTransfer;
}
