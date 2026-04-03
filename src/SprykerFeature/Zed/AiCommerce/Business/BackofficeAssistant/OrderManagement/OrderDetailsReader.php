<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement;

use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderConditionsTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;

class OrderDetailsReader implements OrderDetailsReaderInterface
{
    public function __construct(protected SalesFacadeInterface $salesFacade)
    {
    }

    public function getOrderDetailsById(int $idSalesOrder): string
    {
        $orderTransfer = $this->salesFacade->findOrderByIdSalesOrder($idSalesOrder);

        if ($orderTransfer === null) {
            return '{}';
        }

        return (string)json_encode($this->buildOrderDetailsData($orderTransfer), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
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

        // Use findOrderByIdSalesOrder to get a fully hydrated order
        $orderId = $orderCollectionTransfer->getOrders()->getIterator()->current()->getIdSalesOrder();

        $orderTransfer = $this->salesFacade->findOrderByIdSalesOrder($orderId);

        if ($orderTransfer === null) {
            return '{}';
        }

        return (string)json_encode($this->buildOrderDetailsData($orderTransfer), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOrderDetailsData(OrderTransfer $orderTransfer): array
    {
        return [
            'idSalesOrder' => $orderTransfer->getIdSalesOrder(),
            'orderReference' => $orderTransfer->getOrderReference(),
            'createdAt' => $orderTransfer->getCreatedAt(),
            'currency' => $orderTransfer->getCurrencyIsoCode(),
            'store' => $orderTransfer->getStore(),
            'priceMode' => $orderTransfer->getPriceMode(),
            'totals' => $this->buildTotalsData($orderTransfer->getTotals()),
            'payments' => $this->buildPaymentsData($orderTransfer),
            'expenses' => $this->buildExpensesData($orderTransfer),
            'itemCount' => $orderTransfer->getItems()->count(),
            'items' => $this->buildItemsData($orderTransfer),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildTotalsData(?TotalsTransfer $totalsTransfer): ?array
    {
        if ($totalsTransfer === null) {
            return null;
        }

        return [
            'grandTotal' => $totalsTransfer->getGrandTotal(),
            'subtotal' => $totalsTransfer->getSubtotal(),
            'discountTotal' => $totalsTransfer->getDiscountTotal(),
            'taxTotal' => $totalsTransfer->getTaxTotal()?->getAmount(),
            'expenseTotal' => $totalsTransfer->getExpenseTotal(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildPaymentsData(OrderTransfer $orderTransfer): array
    {
        $payments = [];

        foreach ($orderTransfer->getPayments() as $paymentTransfer) {
            $payments[] = $this->buildPaymentData($paymentTransfer);
        }

        return $payments;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPaymentData(PaymentTransfer $paymentTransfer): array
    {
        return [
            'paymentProvider' => $paymentTransfer->getPaymentProvider(),
            'paymentMethod' => $paymentTransfer->getPaymentMethod(),
            'amount' => $paymentTransfer->getAmount(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildExpensesData(OrderTransfer $orderTransfer): array
    {
        $expenses = [];

        foreach ($orderTransfer->getExpenses() as $expenseTransfer) {
            $expenses[] = $this->buildExpenseData($expenseTransfer);
        }

        return $expenses;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildExpenseData(ExpenseTransfer $expenseTransfer): array
    {
        return [
            'type' => $expenseTransfer->getType(),
            'name' => $expenseTransfer->getName(),
            'sumPrice' => $expenseTransfer->getSumPrice(),
            'taxRate' => $expenseTransfer->getTaxRate(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildItemsData(OrderTransfer $orderTransfer): array
    {
        $items = [];

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $items[] = $this->buildItemData($itemTransfer);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildItemData(ItemTransfer $itemTransfer): array
    {
        $shipment = $itemTransfer->getShipment();

        return [
            'name' => $itemTransfer->getName(),
            'sku' => $itemTransfer->getSku(),
            'quantity' => $itemTransfer->getQuantity(),
            'unitPrice' => $itemTransfer->getUnitPrice(),
            'sumPrice' => $itemTransfer->getSumPrice(),
            'unitTaxAmount' => $itemTransfer->getUnitTaxAmount(),
            'state' => $itemTransfer->getState()?->getName(),
            'idShipmentMethod' => $shipment?->getMethod()?->getIdShipmentMethod(),
            'shipmentMethodName' => $shipment?->getMethod()?->getName(),
        ];
    }
}
