<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant;

use Generated\Shared\Transfer\PromptResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;

interface StorefrontAssistantAgentPluginInterface
{
    /**
     * Specification:
     * - Returns the unique identifier for this agent.
     *
     * @api
     */
    public function getName(): string;

    /**
     * Specification:
     * - Returns a human-readable description of what this agent does.
     *
     * @api
     */
    public function getDescription(): string;

    /**
     * Specification:
     * - Returns whether this agent should handle the given customer chat request.
     * - Called only for enabled agents and only when no enabled agent matches the explicitly selected agent name.
     * - The first applicable agent in plugin stack order handles the turn, so stack order decides routing.
     *
     * @api
     */
    public function isApplicable(StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer): bool;

    /**
     * Specification:
     * - Returns the Configuration Management key of the per-agent enable toggle.
     * - Read fresh each turn so a Back Office change takes effect on the next turn.
     *
     * @api
     */
    public function getEnabledConfigurationKey(): string;

    /**
     * Specification:
     * - Executed once per chat turn, after validation, agent selection, the conversation metadata save and the `data-agent` stream event.
     * - Builds this agent's prompt request and runs it through `AiFoundationClientInterface::streamPrompt()` itself; the caller only reads the returned response.
     * - Text, reasoning and tool events reach the customer only when the request's AI configuration name is listed by `\SprykerFeature\Client\AiCommerce\AiCommerceConfig::getStorefrontAssistantSseAiConfigurationNames()`.
     * - Returns an unsuccessful response with errors when the turn fails; the caller turns it into a stream error event.
     *
     * @api
     */
    public function executeAgent(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): PromptResponseTransfer;
}
