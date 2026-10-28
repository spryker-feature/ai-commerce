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

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class StorefrontAssistantSsePostToolCallPlugin extends AbstractPlugin implements PostToolCallPluginInterface
{
    use BundleConfigResolverAwareTrait;

    /**
     * {@inheritDoc}
     * - Emits the tool output event for storefront assistant AI configurations after each customer facing tool call.
     * - Emits a denied output event when the tool was not allowed to execute.
     * - Emits nothing for a tool internal to the agent, so its result never reaches the customer.
     * - Expands a displayed product result with refinement chips, active filters and a see-all link of the catalog search of this turn it came from.
     *
     * @api
     */
    public function postToolCall(AiToolCallTransfer $aiToolCallTransfer): void
    {
        $this->getFactory()->createToolCallEventStreamer()->streamToolOutput($aiToolCallTransfer);
    }
}
