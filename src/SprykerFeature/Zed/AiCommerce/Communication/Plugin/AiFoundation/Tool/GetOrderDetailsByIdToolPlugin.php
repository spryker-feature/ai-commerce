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
class GetOrderDetailsByIdToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    use LoggerTrait;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'get_order_details_by_id';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Get full order details by numeric sales order ID. Returns customer with reference, billing and shipping addresses, '
            . 'items with SKUs/quantities/prices/states, shipment method, payment method, totals, and expenses — enough to re-place a similar order.';
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
                'idSalesOrder',
                'integer',
                'The numeric sales order ID, for example 123',
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
            return $this->getBusinessFactory()->createOrderDetailsReader()->getOrderDetailsById((int)$arguments['idSalesOrder']);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(sprintf('GetOrderDetailsByIdToolPlugin::execute() failed: %s', $throwable->getMessage()), ['exception' => $throwable]);

            return json_encode(['error' => 'An error occurred while retrieving order details.']);
        }
    }
}
