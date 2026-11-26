<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\QuickOrderVisualAddToCart\Plugin\QuickOrderPage;

use Codeception\Test\Unit;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Plugin\QuickOrderPage\AiCommerceQuickOrderVisualAddToCartFormPlugin;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group QuickOrderVisualAddToCart
 * @group Plugin
 * @group QuickOrderPage
 * @group AiCommerceQuickOrderVisualAddToCartFormPluginTest
 */
class AiCommerceQuickOrderVisualAddToCartFormPluginTest extends Unit
{
    protected AiCommerceYvesTester $tester;

    /**
     * @dataProvider provideFeatureFlags
     */
    public function testGivenFeatureFlagWhenCheckingApplicabilityThenPluginFollowsTheFlag(bool $isEnabled): void
    {
        // Arrange
        $plugin = new AiCommerceQuickOrderVisualAddToCartFormPlugin();
        $plugin->setConfig($this->tester->createQuickOrderVisualAddToCartConfig([
            'isQuickOrderVisualAddToCartEnabled' => $isEnabled,
        ]));

        // Act
        $isApplicable = $plugin->isApplicable();

        // Assert
        $this->assertSame($isEnabled, $isApplicable);
    }

    /**
     * @return array<string, array<bool>>
     */
    public static function provideFeatureFlags(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
        ];
    }
}
