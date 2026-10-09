<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Config;

use Codeception\Stub;
use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Config
 * @group StorefrontAssistantDefaultSuggestedPromptsTest
 */
class StorefrontAssistantDefaultSuggestedPromptsTest extends Unit
{
    protected const string MODULE_CONFIGURATION_FILE = 'resources/configuration/ai_commerce.configuration.yml';

    protected const string FEATURE_KEY = 'ai_commerce';

    protected const string TAB_KEY = 'storefront_assistant';

    protected const string GROUP_KEY = 'suggested_prompts';

    protected const string SETTING_KEY = 'product_page_prompts';

    protected const string CONFIGURATION_KEY_PRODUCT_PAGE_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:product_page_prompts';

    protected const string UNANSWERABLE_CHIP_PATTERN = '/\b(specs?|specifications?|fit|before buying)\b/i';

    public function testGivenModuleDefaultsWhenProductPagePromptsAreReadThenNoChipAsksForSpecsFitOrBeforeBuying(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigWithModuleDefaultProductPagePrompts();

        // Act
        $productPagePrompts = $aiCommerceConfig->getStorefrontAssistantSuggestedPrompts()[StorefrontAssistantPageType::Product->value];

        // Assert
        foreach ($productPagePrompts as $productPagePrompt) {
            $this->assertDoesNotMatchRegularExpression(static::UNANSWERABLE_CHIP_PATTERN, $productPagePrompt);
        }
    }

    public function testGivenModuleDefaultsWhenProductPagePromptsAreReadThenAtLeastTwoChipsAreOffered(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigWithModuleDefaultProductPagePrompts();

        // Act
        $productPagePrompts = $aiCommerceConfig->getStorefrontAssistantSuggestedPrompts()[StorefrontAssistantPageType::Product->value];

        // Assert
        $this->assertGreaterThanOrEqual(2, count($productPagePrompts));
    }

    protected function createConfigWithModuleDefaultProductPagePrompts(): AiCommerceConfig
    {
        $configurationValues = [
            static::CONFIGURATION_KEY_PRODUCT_PAGE_PROMPTS => $this->getModuleDefaultProductPagePrompts(),
        ];

        /**
         * @var \SprykerFeature\Yves\AiCommerce\AiCommerceConfig $aiCommerceConfig
         */
        $aiCommerceConfig = Stub::construct(AiCommerceConfig::class, [], [
            'getModuleConfig' => function (string $key, mixed $default = null) use ($configurationValues): mixed {
                return $configurationValues[$key] ?? $default;
            },
        ]);

        return $aiCommerceConfig;
    }

    protected function getModuleDefaultProductPagePrompts(): string
    {
        $configuration = Yaml::parseFile(dirname(__DIR__, 6) . DIRECTORY_SEPARATOR . static::MODULE_CONFIGURATION_FILE);

        $feature = $this->findByKey($configuration['features'], static::FEATURE_KEY);
        $tab = $this->findByKey($feature['tabs'], static::TAB_KEY);
        $group = $this->findByKey($tab['groups'], static::GROUP_KEY);
        $setting = $this->findByKey($group['settings'], static::SETTING_KEY);

        $this->assertIsString($setting['default_value']);

        return $setting['default_value'];
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    protected function findByKey(array $items, string $key): array
    {
        foreach ($items as $item) {
            if (($item['key'] ?? null) === $key) {
                return $item;
            }
        }

        $this->fail(sprintf('Configuration entry "%s" is missing from the module defaults.', $key));
    }
}
