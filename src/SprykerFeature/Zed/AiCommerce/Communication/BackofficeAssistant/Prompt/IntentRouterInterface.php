<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt;

use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Generated\Shared\Transfer\IntentRouterResponseTransfer;

interface IntentRouterInterface
{
    /**
     * Routes the user intent to the appropriate agent based on conversation history and context.
     *
     * @param array<string, mixed> $context
     */
    public function route(
        ConversationHistoryTransfer $conversationHistory,
        string $prompt,
        ?string $previousAgent,
        array $context,
    ): ?IntentRouterResponseTransfer;
}
