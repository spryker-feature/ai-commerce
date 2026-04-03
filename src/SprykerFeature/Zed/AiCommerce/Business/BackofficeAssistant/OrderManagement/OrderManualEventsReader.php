<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement;

use Generated\Shared\Transfer\OrderConditionsTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;

class OrderManualEventsReader implements OrderManualEventsReaderInterface
{
    public function __construct(
        protected OmsFacadeInterface $omsFacade,
        protected SalesFacadeInterface $salesFacade,
    ) {
    }

    public function getOrderManualEvents(string $orderReference): string
    {
        $idSalesOrder = $this->resolveIdSalesOrderByOrderReference($orderReference);

        if ($idSalesOrder === null) {
            return '{}';
        }

        $manualEvents = $this->omsFacade->getDistinctManualEventsByIdSalesOrder($idSalesOrder);

        return (string)json_encode([
            'orderReference' => $orderReference,
            'manualEvents' => $manualEvents,
        ], JSON_PRETTY_PRINT);
    }

    protected function resolveIdSalesOrderByOrderReference(string $orderReference): ?int
    {
        $orderCriteriaTransfer = (new OrderCriteriaTransfer())
            ->setOrderConditions(
                (new OrderConditionsTransfer())->addOrderReference($orderReference),
            );

        $orderCollectionTransfer = $this->salesFacade->getOrderCollection($orderCriteriaTransfer);

        if ($orderCollectionTransfer->getOrders()->count() === 0) {
            return null;
        }

        return $orderCollectionTransfer->getOrders()->getIterator()->current()->getIdSalesOrder();
    }
}
