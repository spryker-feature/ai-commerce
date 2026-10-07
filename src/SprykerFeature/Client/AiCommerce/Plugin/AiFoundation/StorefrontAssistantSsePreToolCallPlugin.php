<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\AiFoundation;

use Generated\Shared\Transfer\AiToolCallTransfer;
use Spryker\Client\AiFoundation\Dependency\Plugin\PreToolCallPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\Kernel\BundleConfigResolverAwareTrait;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class StorefrontAssistantSsePreToolCallPlugin extends AbstractPlugin implements PreToolCallPluginInterface
{
    use BundleConfigResolverAwareTrait;

    /**
     * {@inheritDoc}
     * - Emits the tool input parts for storefront assistant AI configurations before each tool call.
     * - Closes the open content block first, so the tool parts do not interrupt it.
     * - Sets the resolved tool call ID on the returned transfer, so the post tool call plugin emits the output under the same ID.
     *
     * @api
     */
    public function preToolCall(AiToolCallTransfer $aiToolCallTransfer): AiToolCallTransfer
    {
        $configurationName = $aiToolCallTransfer->getPromptRequest()?->getAiConfigurationName();

        if (!in_array($configurationName, $this->getConfig()->getStorefrontAssistantSseAiConfigurationNames(), true)) {
            return $aiToolCallTransfer;
        }

        $toolName = (string)$aiToolCallTransfer->getToolName();
        $toolCallId = $this->getFactory()->createToolCallIdResolver()->resolveToolCallId(
            $toolName,
            $aiToolCallTransfer->getToolCallId(),
        );
        $aiToolCallTransfer->setToolCallId($toolCallId);

        $this->getFactory()->createStreamBlockTracker()->closeOpenBlock();

        $streamEventEmitter = $this->getFactory()->createStreamEventEmitter();

        $streamEventEmitter->emitEvent(StreamEventType::ToolInputStart, [
            StreamEventKey::TOOL_CALL_ID => $toolCallId,
            StreamEventKey::TOOL_NAME => $toolName,
        ]);

        $streamEventEmitter->emitEvent(StreamEventType::ToolInputAvailable, [
            StreamEventKey::TOOL_CALL_ID => $toolCallId,
            StreamEventKey::TOOL_NAME => $toolName,
            StreamEventKey::INPUT => $aiToolCallTransfer->getToolArguments(),
        ]);

        return $aiToolCallTransfer;
    }
}
