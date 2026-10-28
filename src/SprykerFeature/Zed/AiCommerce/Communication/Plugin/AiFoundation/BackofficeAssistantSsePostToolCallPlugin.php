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

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class BackofficeAssistantSsePostToolCallPlugin extends AbstractPlugin implements PostToolCallPluginInterface
{
    /**
     * {@inheritDoc}
     * - Emits the `tool-output-available` stream event with the tool result under `output.result` for backoffice assistant AI configurations after each tool call.
     * - Emits `tool-output-denied` with the tool call ID instead when the execution was not allowed.
     * - Uses the tool call ID the pre tool call plugin set, so the output pairs with its input.
     * - Emits nothing for other AI configurations.
     *
     * @api
     */
    public function postToolCall(AiToolCallTransfer $aiToolCallTransfer): void
    {
        $this->getFactory()->createToolCallEventStreamer()->streamToolOutput($aiToolCallTransfer);
    }
}
