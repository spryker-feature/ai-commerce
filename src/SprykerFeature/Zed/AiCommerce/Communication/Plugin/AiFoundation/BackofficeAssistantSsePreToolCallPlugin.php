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

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class BackofficeAssistantSsePreToolCallPlugin extends AbstractPlugin implements PreToolCallPluginInterface
{
    /**
     * {@inheritDoc}
     * - Emits the `tool-input-start` and `tool-input-available` stream events for backoffice assistant AI configurations before each tool call.
     * - Closes the open content block first, so the tool events do not interrupt it.
     * - Sets the resolved tool call ID on the returned transfer, so the post tool call plugin emits the output under the same ID.
     * - Emits nothing for other AI configurations.
     *
     * @api
     */
    public function preToolCall(AiToolCallTransfer $aiToolCallTransfer): AiToolCallTransfer
    {
        return $this->getFactory()->createToolCallEventStreamer()->streamToolInput($aiToolCallTransfer);
    }
}
