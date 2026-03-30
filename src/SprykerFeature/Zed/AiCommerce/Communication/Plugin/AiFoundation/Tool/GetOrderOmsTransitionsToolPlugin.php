<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Throwable;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 */
class GetOrderOmsTransitionsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    use LoggerTrait;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'get_order_oms_transitions';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Get current OMS state and available transitions for an order by its reference. Returns transition details including events, conditions, and targets.';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
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

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param mixed ...$arguments
     */
    public function execute(...$arguments): mixed
    {
        try {
            return $this->getBusinessFactory()->createOrderOmsTransitionsReader()->getOrderOmsTransitions((string)$arguments['orderReference']);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(sprintf('GetOrderOmsTransitionsToolPlugin::execute() failed: %s', $throwable->getMessage()), ['exception' => $throwable]);

            return json_encode(['error' => 'An error occurred while retrieving order OMS transitions.']);
        }
    }
}
