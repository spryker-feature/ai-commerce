<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation;

use Generated\Shared\Transfer\AiToolCallTransfer;
use Spryker\Zed\AiFoundation\Dependency\Plugin\PostToolCallPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class BackofficeAssistantSsePostToolCallPlugin extends AbstractPlugin implements PostToolCallPluginInterface
{
    protected const string KEY_NAME = 'name';

    protected const string KEY_RESULT = 'result';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function postToolCall(AiToolCallTransfer $aiToolCallTransfer): void
    {
        $configurationName = $aiToolCallTransfer->getPromptRequest()?->getAiConfigurationName();

        if (!in_array($configurationName, $this->getConfig()->getBackofficeAssistantSseAiConfigurationNames(), true)) {
            return;
        }

        $this->getFactory()->createSseEventEmitter()->emit(BackofficeAssistantEventType::ToolCallResult, [
            static::KEY_NAME => $aiToolCallTransfer->getToolName(),
            static::KEY_RESULT => $aiToolCallTransfer->getToolResult(),
        ]);
    }
}
