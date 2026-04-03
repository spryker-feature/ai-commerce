<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation;

use Generated\Shared\Transfer\AiToolCallTransfer;
use Spryker\Zed\AiFoundation\Dependency\Plugin\PreToolCallPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class BackofficeAssistantSsePreToolCallPlugin extends AbstractPlugin implements PreToolCallPluginInterface
{
    protected const string KEY_NAME = 'name';

    protected const string KEY_ARGUMENTS = 'arguments';

    /**
     * {@inheritDoc}
     * - Emits a tool call SSE event for backoffice assistant AI configurations before each tool call.
     *
     * @api
     */
    public function preToolCall(AiToolCallTransfer $aiToolCallTransfer): AiToolCallTransfer
    {
        $configurationName = $aiToolCallTransfer->getPromptRequest()?->getAiConfigurationName();

        if (!in_array($configurationName, $this->getConfig()->getBackofficeAssistantSseAiConfigurationNames(), true)) {
            return $aiToolCallTransfer;
        }

        $this->getFactory()->createSseEventEmitter()->emit(BackofficeAssistantEventType::ToolCall, [
            static::KEY_NAME => $aiToolCallTransfer->getToolName(),
            static::KEY_ARGUMENTS => $aiToolCallTransfer->getToolArguments(),
        ]);

        return $aiToolCallTransfer;
    }
}
