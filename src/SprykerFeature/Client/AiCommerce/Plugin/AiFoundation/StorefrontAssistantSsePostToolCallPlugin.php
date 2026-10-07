<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\AiFoundation;

use Generated\Shared\Transfer\AiToolCallTransfer;
use Spryker\Client\AiFoundation\Dependency\Plugin\PostToolCallPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\Kernel\BundleConfigResolverAwareTrait;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class StorefrontAssistantSsePostToolCallPlugin extends AbstractPlugin implements PostToolCallPluginInterface
{
    use BundleConfigResolverAwareTrait;

    protected const string OUTPUT_KEY_RESULT = 'result';

    /**
     * {@inheritDoc}
     * - Emits the tool output event for storefront assistant AI configurations after each customer facing tool call.
     * - Emits a denied output event when the tool was not allowed to execute.
     * - Emits nothing for a tool internal to the agent, so its result never reaches the customer.
     *
     * @api
     */
    public function postToolCall(AiToolCallTransfer $aiToolCallTransfer): void
    {
        $configurationName = $aiToolCallTransfer->getPromptRequest()?->getAiConfigurationName();

        if (!in_array($configurationName, $this->getConfig()->getStorefrontAssistantSseAiConfigurationNames(), true)) {
            return;
        }

        $toolName = (string)$aiToolCallTransfer->getToolName();

        if (!in_array($toolName, $this->getConfig()->getStorefrontAssistantCustomerFacingToolNames(), true)) {
            return;
        }

        $toolCallId = $this->getFactory()->createToolCallIdResolver()->resolveToolCallId(
            $toolName,
            $aiToolCallTransfer->getToolCallId(),
        );

        if ($aiToolCallTransfer->getIsExecutionAllowed() === false) {
            $this->getFactory()->createStreamEventEmitter()->emitEvent(StreamEventType::ToolOutputDenied, [
                StreamEventKey::TOOL_CALL_ID => $toolCallId,
            ]);

            return;
        }

        $this->getFactory()->createStreamEventEmitter()->emitEvent(StreamEventType::ToolOutputAvailable, [
            StreamEventKey::TOOL_CALL_ID => $toolCallId,
            StreamEventKey::OUTPUT => [
                static::OUTPUT_KEY_RESULT => $this->getFactory()
                    ->createToolResultProductEnricher()
                    ->enrich((string)$aiToolCallTransfer->getToolResult()),
            ],
        ]);
    }
}
