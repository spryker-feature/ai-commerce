<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Reader;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Reader\StorefrontAssistantSuggestedPromptReaderInterface;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;
use Symfony\Component\Yaml\Yaml;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Reader
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

    protected const string LOCALE_NAME_EN = 'en_US';

    protected const string LOCALE_NAME_DE = 'de_DE';

    protected const string UNANSWERABLE_CHIP_PATTERN = '/\b(specs?|specifications?|fit|before buying)\b/i';

    protected AiCommerceYvesTester $tester;

    public function testGivenModuleDefaultsWhenProductPagePromptsAreReadThenNoChipAsksForSpecsFitOrBeforeBuying(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderWithModuleDefaultProductPagePrompts(static::LOCALE_NAME_EN);

        // Act
        $productPagePrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts()[StorefrontAssistantPageType::Product->value];

        // Assert
        foreach ($productPagePrompts as $productPagePrompt) {
            $this->assertDoesNotMatchRegularExpression(static::UNANSWERABLE_CHIP_PATTERN, $productPagePrompt);
        }
    }

    /**
     * @dataProvider provideDefaultLocaleNames
     */
    public function testGivenModuleDefaultsWhenProductPagePromptsAreReadThenAtLeastTwoChipsAreOffered(string $localeName): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderWithModuleDefaultProductPagePrompts($localeName);

        // Act
        $productPagePrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts()[StorefrontAssistantPageType::Product->value];

        // Assert
        $this->assertGreaterThanOrEqual(2, count($productPagePrompts));
    }

    public function testGivenModuleDefaultsWhenProductPagePromptsAreReadInGermanThenTheyDifferFromTheEnglishPrompts(): void
    {
        // Arrange
        $germanReader = $this->createReaderWithModuleDefaultProductPagePrompts(static::LOCALE_NAME_DE);
        $englishReader = $this->createReaderWithModuleDefaultProductPagePrompts(static::LOCALE_NAME_EN);

        // Act
        $germanPrompts = $germanReader->getSuggestedPrompts()[StorefrontAssistantPageType::Product->value];
        $englishPrompts = $englishReader->getSuggestedPrompts()[StorefrontAssistantPageType::Product->value];

        // Assert
        $this->assertNotSame($englishPrompts, $germanPrompts);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideDefaultLocaleNames(): array
    {
        return [
            static::LOCALE_NAME_EN => [static::LOCALE_NAME_EN],
            static::LOCALE_NAME_DE => [static::LOCALE_NAME_DE],
        ];
    }

    protected function createReaderWithModuleDefaultProductPagePrompts(string $localeName): StorefrontAssistantSuggestedPromptReaderInterface
    {
        return $this->tester->createStorefrontAssistantSuggestedPromptReader([
            static::CONFIGURATION_KEY_PRODUCT_PAGE_PROMPTS => json_encode($this->getModuleDefaultProductPagePrompts(), JSON_THROW_ON_ERROR),
        ], $localeName);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function getModuleDefaultProductPagePrompts(): array
    {
        $configuration = Yaml::parseFile(dirname(__DIR__, 6) . DIRECTORY_SEPARATOR . static::MODULE_CONFIGURATION_FILE);

        $feature = $this->findByKey($configuration['features'], static::FEATURE_KEY);
        $tab = $this->findByKey($feature['tabs'], static::TAB_KEY);
        $group = $this->findByKey($tab['groups'], static::GROUP_KEY);
        $setting = $this->findByKey($group['settings'], static::SETTING_KEY);

        $this->assertIsArray($setting['default_value']);

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
