<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\ToolSet;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Tool\GetOmsProcessDefinitionToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Tool\GetOrderDetailsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Tool\GetOrderManualEventsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Tool\GetOrderOmsTransitionsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Tool\GetOrderStateFlagsToolPlugin;

class OrderManagementToolSetPlugin implements ToolSetPluginInterface
{
    public function getName(): string
    {
        return AiCommerceConstants::TOOL_SET_ORDER_MANAGEMENT;
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface>
     */
    public function getTools(): array
    {
        return [
            new GetOrderOmsTransitionsToolPlugin(),
            new GetOrderDetailsToolPlugin(),
            new GetOrderManualEventsToolPlugin(),
            new GetOmsProcessDefinitionToolPlugin(),
            new GetOrderStateFlagsToolPlugin(),
        ];
    }
}
