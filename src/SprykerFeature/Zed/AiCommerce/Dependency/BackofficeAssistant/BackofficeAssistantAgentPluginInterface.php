<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;

interface BackofficeAssistantAgentPluginInterface
{
    /**
     * Specification:
     * - Returns the unique agent name used for routing (e.g., "Product").
     * - This name is matched against IntentRouterResponseTransfer::agent.
     *
     * @api
     */
    public function getName(): string;

    /**
     * Specification:
     * - Returns a description of what this agent handles.
     * - Used by the intent router AI to decide which agent matches user intent.
     *
     * @api
     */
    public function getDescription(): string;

    /**
     * Specification:
     * - The caller executes the request via AiFoundationFacade::prompt().
     *
     * @api
     */
    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer;
}
