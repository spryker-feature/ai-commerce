<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group AiCommerceConfigTest
 * Add your own group annotations below this line
 */
class AiCommerceConfigTest extends Unit
{
    public function testGivenModuleDefaultsWhenTheAiConfigurationNameIsReadThenItIsEmptyUntilTheProjectResolvesIt(): void
    {
        // Arrange
        $aiCommerceConfig = new AiCommerceConfig();

        // Act
        $aiConfigurationName = $aiCommerceConfig->getStorefrontAssistantAiConfigurationName();

        // Assert
        $this->assertSame('', $aiConfigurationName);
    }

    public function testGivenModuleDefaultsWhenAgentToolNamesAreReadThenNoToolIsEnabledUntilTheProjectListsThem(): void
    {
        // Arrange
        $aiCommerceConfig = new AiCommerceConfig();

        // Act
        $productDiscoveryAgentToolNames = $aiCommerceConfig->getProductDiscoveryAgentToolNames();

        // Assert
        $this->assertSame([], $productDiscoveryAgentToolNames);
    }
}
