<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderDetailsByIdToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderDetailsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderManualEventsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderOmsTransitionsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderStateFlagsToolPlugin;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group Facade
 * @group OrderManagementToolPluginsTest
 */
class OrderManagementToolPluginsTest extends Unit
{
    protected const string PROCESS_NAME = 'Nopayment01';

    protected const string ITEM_STATE = 'new';

    protected AiCommerceBusinessTester $tester;

    public function testGetOrderDetailsToolPluginReturnsJsonWithOrderData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = (new GetOrderDetailsToolPlugin())->execute(orderReference: $orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame($orderReference, $decoded['orderReference']);
        $this->assertArrayHasKey('idSalesOrder', $decoded);
        $this->assertArrayHasKey('totals', $decoded);
        $this->assertArrayHasKey('items', $decoded);
        $this->assertArrayHasKey('itemCount', $decoded);
        $this->assertArrayHasKey('customer', $decoded);
        $this->assertArrayHasKey('customerReference', $decoded['customer']);
        $this->assertArrayHasKey('billingAddress', $decoded);
        $this->assertArrayHasKey('payments', $decoded);
        $this->assertArrayHasKey('expenses', $decoded);
        $this->assertArrayHasKey('billingAddress', $decoded['items'][0]);
        $this->assertArrayHasKey('shippingAddress', $decoded['items'][0]);
    }

    public function testGetOrderDetailsToolPluginReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = (new GetOrderDetailsToolPlugin())->execute(orderReference: 'NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOrderDetailsByIdToolPluginReturnsJsonWithOrderData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = (new GetOrderDetailsByIdToolPlugin())->execute(idSalesOrder: $idSalesOrder);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame($orderReference, $decoded['orderReference']);
        $this->assertArrayHasKey('idSalesOrder', $decoded);
        $this->assertArrayHasKey('totals', $decoded);
        $this->assertArrayHasKey('items', $decoded);
        $this->assertArrayHasKey('itemCount', $decoded);
        $this->assertArrayHasKey('customer', $decoded);
        $this->assertArrayHasKey('customerReference', $decoded['customer']);
        $this->assertArrayHasKey('billingAddress', $decoded);
        $this->assertArrayHasKey('payments', $decoded);
        $this->assertArrayHasKey('expenses', $decoded);
        $this->assertArrayHasKey('billingAddress', $decoded['items'][0]);
        $this->assertArrayHasKey('shippingAddress', $decoded['items'][0]);
    }

    public function testGetOrderDetailsByIdToolPluginReturnsEmptyJsonForUnknownId(): void
    {
        // Act
        $result = (new GetOrderDetailsByIdToolPlugin())->execute(idSalesOrder: 999999);

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOrderManualEventsToolPluginReturnsJsonWithEventsData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = (new GetOrderManualEventsToolPlugin())->execute(orderReference: $orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame($orderReference, $decoded['orderReference']);
        $this->assertArrayHasKey('manualEvents', $decoded);
    }

    public function testGetOrderManualEventsToolPluginReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = (new GetOrderManualEventsToolPlugin())->execute(orderReference: 'NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOrderOmsTransitionsToolPluginReturnsJsonWithTransitionsData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = (new GetOrderOmsTransitionsToolPlugin())->execute(orderReference: $orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame(static::PROCESS_NAME, $decoded['processName']);
        $this->assertArrayHasKey('currentStates', $decoded);
        $this->assertArrayHasKey('transitions', $decoded);
        $this->assertContains(static::ITEM_STATE, $decoded['currentStates']);
    }

    public function testGetOrderOmsTransitionsToolPluginReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = (new GetOrderOmsTransitionsToolPlugin())->execute(orderReference: 'NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOrderStateFlagsToolPluginReturnsJsonWithFlagsData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = (new GetOrderStateFlagsToolPlugin())->execute(orderReference: $orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame(static::PROCESS_NAME, $decoded['processName']);
        $this->assertArrayHasKey('states', $decoded);
        $this->assertArrayHasKey(static::ITEM_STATE, $decoded['states']);
    }

    public function testGetOrderStateFlagsToolPluginReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = (new GetOrderStateFlagsToolPlugin())->execute(orderReference: 'NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }
}
