<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business;

use Codeception\Test\Unit;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\CreateDiscountToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetDiscountDetailsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\UpdateDiscountToolPlugin;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group Facade
 * @group DiscountManagementToolPluginsTest
 */
class DiscountManagementToolPluginsTest extends Unit
{
    protected AiCommerceBusinessTester $tester;

    public function testGetDiscountDetailsToolPluginReturnsJsonWithDiscountStructure(): void
    {
        // Arrange
        $discountGeneralTransfer = $this->tester->haveDiscount();
        $idDiscount = $discountGeneralTransfer->getIdDiscountOrFail();

        // Act
        $result = (new GetDiscountDetailsToolPlugin())->execute(idDiscount: $idDiscount);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertTrue($decoded['found']);
        $this->assertArrayHasKey('general', $decoded);
        $this->assertArrayHasKey('calculator', $decoded);
        $this->assertArrayHasKey('condition', $decoded);
        $this->assertSame($idDiscount, $decoded['general']['idDiscount']);
    }

    public function testGetDiscountDetailsToolPluginReturnsNotFoundForUnknownDiscount(): void
    {
        // Act
        $result = (new GetDiscountDetailsToolPlugin())->execute(idDiscount: 999999);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertFalse($decoded['found']);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertStringContainsString('999999', $decoded['error']);
    }

    public function testCreateDiscountToolPluginReturnsSuccessWithIdDiscount(): void
    {
        // Arrange
        $discountData = [
            'displayName' => sprintf('Test Discount %s', uniqid()),
            'discountType' => 'cart_rule',
            'validFrom' => '2026-01-01 00:00:00',
            'validTo' => '2026-12-31 23:59:59',
            'isExclusive' => false,
            'calculatorPlugin' => 'PLUGIN_CALCULATOR_PERCENTAGE',
            'amount' => 1000,
            'collectorQueryString' => '',
            'minimumItemAmount' => 1,
        ];

        // Act
        $result = (new CreateDiscountToolPlugin())->execute(...$discountData);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertTrue($decoded['success']);
        $this->assertGreaterThan(0, $decoded['idDiscount']);
    }

    public function testCreateDiscountToolPluginResponseContainsExpectedJsonKeys(): void
    {
        // Arrange
        $discountData = [
            'displayName' => sprintf('Test Discount Keys %s', uniqid()),
            'discountType' => 'cart_rule',
            'validFrom' => '2026-01-01 00:00:00',
            'validTo' => '2026-12-31 23:59:59',
            'isExclusive' => false,
            'calculatorPlugin' => 'PLUGIN_CALCULATOR_PERCENTAGE',
            'amount' => 500,
            'collectorQueryString' => '',
            'minimumItemAmount' => 1,
        ];

        // Act
        $result = (new CreateDiscountToolPlugin())->execute(...$discountData);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertArrayHasKey('errors', $decoded);
        $this->assertTrue($decoded['success']);
        $this->assertArrayHasKey('idDiscount', $decoded);
    }

    public function testUpdateDiscountToolPluginReturnsSuccessForExistingDiscount(): void
    {
        // Arrange
        $discountGeneralTransfer = $this->tester->haveDiscount();
        $idDiscount = $discountGeneralTransfer->getIdDiscountOrFail();
        $updatedName = sprintf('Updated Discount %s', uniqid());

        // Act
        $result = (new UpdateDiscountToolPlugin())->execute(
            idDiscount: $idDiscount,
            displayName: $updatedName,
        );

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertTrue($decoded['success']);
        $this->assertEmpty($decoded['errors']);
    }

    public function testUpdateDiscountToolPluginReturnsErrorForNonExistentDiscount(): void
    {
        // Act
        $result = (new UpdateDiscountToolPlugin())->execute(
            idDiscount: 999999,
            displayName: 'Ghost',
        );

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertFalse($decoded['success']);
        $this->assertStringContainsString('not found', implode(' ', $decoded['errors']));
    }

    public function testCreateDiscountToolPluginReturnsErrorForDuplicateDisplayName(): void
    {
        // Arrange
        $existingDiscount = $this->tester->haveDiscount();
        $duplicateName = $existingDiscount->getDisplayNameOrFail();
        $discountData = [
            'displayName' => $duplicateName,
            'discountType' => 'cart_rule',
            'validFrom' => '2026-01-01 00:00:00',
            'validTo' => '2026-12-31 23:59:59',
            'isExclusive' => false,
            'calculatorPlugin' => 'PLUGIN_CALCULATOR_PERCENTAGE',
            'amount' => 1000,
            'minimumItemAmount' => 1,
        ];

        // Act
        $result = (new CreateDiscountToolPlugin())->execute(...$discountData);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertFalse($decoded['success']);
        $this->assertNotEmpty($decoded['errors']);
        $this->assertStringContainsString($duplicateName, implode(' ', $decoded['errors']));
    }
}
