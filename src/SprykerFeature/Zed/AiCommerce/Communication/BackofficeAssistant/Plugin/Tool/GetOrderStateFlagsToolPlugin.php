<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class GetOrderStateFlagsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public function getName(): string
    {
        return 'get_order_state_flags';
    }

    public function getDescription(): string
    {
        return 'Check state flags such as cancellable or reserved for the current states of an order.';
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                'orderReference',
                'string',
                'The order reference, for example DE--123',
                true,
            ),
        ];
    }

    public function execute(...$arguments): mixed
    {
        return $this->getFacade()->getOrderStateFlags((string)($arguments[0] ?? ''));
    }
}
