<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group Facade
 * @group OrderManagementReadersTest
 */
class OrderManagementReadersTest extends Unit
{
    protected const string PROCESS_NAME = 'Nopayment01';

    protected const string ITEM_STATE = 'new';

    protected AiCommerceBusinessTester $tester;

    protected AiCommerceBusinessFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new AiCommerceBusinessFactory();
    }

    public function testGetOrderDetailsReturnsJsonWithOrderData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = $this->factory->createOrderDetailsReader()->getOrderDetails($orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame($orderReference, $decoded['orderReference']);
        $this->assertArrayHasKey('totals', $decoded);
        $this->assertArrayHasKey('items', $decoded);
        $this->assertArrayHasKey('itemCount', $decoded);
    }

    public function testGetOrderDetailsReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = $this->factory->createOrderDetailsReader()->getOrderDetails('NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOrderManualEventsReturnsJsonWithEventsData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = $this->factory->createOrderManualEventsReader()->getOrderManualEvents($orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame($orderReference, $decoded['orderReference']);
        $this->assertArrayHasKey('manualEvents', $decoded);
    }

    public function testGetOrderManualEventsReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = $this->factory->createOrderManualEventsReader()->getOrderManualEvents('NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOrderOmsTransitionsReturnsJsonWithTransitionsData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = $this->factory->createOrderOmsTransitionsReader()->getOrderOmsTransitions($orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame(static::PROCESS_NAME, $decoded['processName']);
        $this->assertArrayHasKey('currentStates', $decoded);
        $this->assertArrayHasKey('transitions', $decoded);
        $this->assertContains(static::ITEM_STATE, $decoded['currentStates']);
    }

    public function testGetOrderOmsTransitionsReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = $this->factory->createOrderOmsTransitionsReader()->getOrderOmsTransitions('NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOmsProcessDefinitionReturnsJsonWithDefinitionData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = $this->factory->createOmsProcessDefinitionReader()->getOmsProcessDefinition($orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame(static::PROCESS_NAME, $decoded['processName']);
        $this->assertArrayHasKey('states', $decoded);
        $this->assertArrayHasKey('transitions', $decoded);
        $this->assertArrayHasKey('subProcesses', $decoded);
    }

    public function testGetOmsProcessDefinitionReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = $this->factory->createOmsProcessDefinitionReader()->getOmsProcessDefinition('NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }

    public function testGetOrderStateFlagsReturnsJsonWithFlagsData(): void
    {
        // Arrange
        $orderReference = uniqid('TEST-ORDER-', true);
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $idSalesOrder = $this->tester->createOrder([OrderTransfer::ORDER_REFERENCE => $orderReference]);
        $this->tester->createSalesOrderItemForOrder($idSalesOrder, ['process' => static::PROCESS_NAME, 'state' => static::ITEM_STATE]);

        // Act
        $result = $this->factory->createOrderStateFlagsReader()->getOrderStateFlags($orderReference);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertSame(static::PROCESS_NAME, $decoded['processName']);
        $this->assertArrayHasKey('states', $decoded);
        $this->assertArrayHasKey(static::ITEM_STATE, $decoded['states']);
    }

    public function testGetOrderStateFlagsReturnsEmptyJsonForUnknownOrder(): void
    {
        // Act
        $result = $this->factory->createOrderStateFlagsReader()->getOrderStateFlags('NONEXISTENT-ORDER-REF-' . uniqid());

        // Assert
        $this->assertSame('{}', $result);
    }
}
