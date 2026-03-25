<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement;

use Generated\Shared\Transfer\OrderConditionsTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;

class OrderDetailsReader implements OrderDetailsReaderInterface
{
    public function __construct(protected SalesFacadeInterface $salesFacade)
    {
    }

    public function getOrderDetails(string $orderReference): string
    {
        $orderCriteriaTransfer = (new OrderCriteriaTransfer())
            ->setOrderConditions(
                (new OrderConditionsTransfer())->addOrderReference($orderReference),
            );

        $orderCollectionTransfer = $this->salesFacade->getOrderCollection($orderCriteriaTransfer);

        if ($orderCollectionTransfer->getOrders()->count() === 0) {
            return '{}';
        }

        $orderTransfer = $orderCollectionTransfer->getOrders()->getIterator()->current();

        return (string)json_encode($this->buildOrderDetailsData($orderTransfer), JSON_PRETTY_PRINT);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOrderDetailsData(OrderTransfer $orderTransfer): array
    {
        $totalsTransfer = $orderTransfer->getTotals();
        $customerTransfer = $orderTransfer->getCustomer();

        return [
            'orderReference' => $orderTransfer->getOrderReference(),
            'createdAt' => $orderTransfer->getCreatedAt(),
            'currency' => $orderTransfer->getCurrencyIsoCode(),
            'store' => $orderTransfer->getStore(),
            'totals' => [
                'grandTotal' => $totalsTransfer?->getGrandTotal(),
                'subtotal' => $totalsTransfer?->getSubtotal(),
                'discountTotal' => $totalsTransfer?->getDiscountTotal(),
            ],
            'customer' => [
                'firstName' => $customerTransfer?->getFirstName(),
                'lastName' => $customerTransfer?->getLastName(),
                'email' => $customerTransfer?->getEmail(),
            ],
            'itemCount' => $orderTransfer->getItems()->count(),
            'items' => $this->buildItemsData($orderTransfer),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildItemsData(OrderTransfer $orderTransfer): array
    {
        $items = [];

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $items[] = [
                'name' => $itemTransfer->getName(),
                'sku' => $itemTransfer->getSku(),
                'quantity' => $itemTransfer->getQuantity(),
                'state' => $itemTransfer->getState()?->getName(),
                'unitPrice' => $itemTransfer->getUnitPrice(),
            ];
        }

        return $items;
    }
}
