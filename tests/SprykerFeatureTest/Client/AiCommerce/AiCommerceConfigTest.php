<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce;

use Codeception\Stub;
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
    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_AI_CONFIGURATION = 'ai_commerce:storefront_assistant:ai_vendor:ai_configuration';

    protected const string AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI';

    protected const string AI_CONFIGURATION_STOREFRONT_ASSISTANT_AWS = 'AI_COMMERCE:AI_CONFIGURATION_STOREFRONT_ASSISTANT_AWS';

    public function testGivenAVendorSelectedInConfigurationManagementWhenTheAiConfigurationIsResolvedThenTheSelectedOneIsReturned(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigWithStoredVendor(static::AI_CONFIGURATION_STOREFRONT_ASSISTANT_AWS);

        // Act
        $aiConfigurationName = $aiCommerceConfig->getStorefrontAssistantAiConfigurationName();

        // Assert
        $this->assertSame(static::AI_CONFIGURATION_STOREFRONT_ASSISTANT_AWS, $aiConfigurationName);
    }

    public function testGivenNoVendorSelectedWhenTheAiConfigurationIsResolvedThenTheOpenAiDefaultIsReturned(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigWithStoredVendor(null);

        // Act
        $aiConfigurationName = $aiCommerceConfig->getStorefrontAssistantAiConfigurationName();

        // Assert
        $this->assertSame(static::AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI, $aiConfigurationName);
    }

    public function testGivenABlankVendorStoredWhenTheAiConfigurationIsResolvedThenTheOpenAiDefaultIsReturned(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigWithStoredVendor(' ');

        // Act
        $aiConfigurationName = $aiCommerceConfig->getStorefrontAssistantAiConfigurationName();

        // Assert
        $this->assertSame(static::AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI, $aiConfigurationName);
    }

    protected function createConfigWithStoredVendor(?string $storedAiConfigurationName): AiCommerceConfig
    {
        return Stub::make(AiCommerceConfig::class, [
            'getModuleConfig' => fn (string $key, mixed $default = null): mixed => $key === static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_AI_CONFIGURATION
                ? ($storedAiConfigurationName ?? $default)
                : $default,
        ]);
    }
}
