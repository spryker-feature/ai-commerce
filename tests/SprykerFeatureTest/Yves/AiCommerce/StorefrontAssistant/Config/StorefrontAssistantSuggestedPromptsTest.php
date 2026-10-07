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

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Config
 * @group StorefrontAssistantSuggestedPromptsTest
 */
class StorefrontAssistantSuggestedPromptsTest extends Unit
{
    protected const string GROUP_PRODUCT_RESULTS = 'product_results';

    protected const string CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:product_results_prompts';

    public function testGivenProductResultsPromptsWhenSuggestedPromptsAreReadThenProductResultsGroupIsReturnedBesidePageTypes(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => 'Compare these for me|Show me cheaper alternatives',
        ]);

        // Act
        $suggestedPrompts = $aiCommerceConfig->getStorefrontAssistantSuggestedPrompts();

        // Assert
        $this->assertArrayHasKey(static::GROUP_PRODUCT_RESULTS, $suggestedPrompts);
        $this->assertSame(
            ['Compare these for me', 'Show me cheaper alternatives'],
            $suggestedPrompts[static::GROUP_PRODUCT_RESULTS],
        );
        $this->assertArrayHasKey(StorefrontAssistantPageType::Default->value, $suggestedPrompts);
    }

    public function testGivenBlankProductResultsValueWhenSuggestedPromptsAreReadThenProductResultsGroupIsEmpty(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => '',
        ]);

        // Act
        $suggestedPrompts = $aiCommerceConfig->getStorefrontAssistantSuggestedPrompts();

        // Assert
        $this->assertSame([], $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    public function testGivenBlankEntriesAndTooManyPromptsWhenSuggestedPromptsAreReadThenBlanksAreDroppedAndCountIsCapped(): void
    {
        // Arrange
        $aiCommerceConfig = $this->createConfigReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => 'One||Two|  |Three|Four|Five',
        ]);

        // Act
        $suggestedPrompts = $aiCommerceConfig->getStorefrontAssistantSuggestedPrompts();

        // Assert
        $this->assertSame(
            ['One', 'Two', 'Three', 'Four'],
            $suggestedPrompts[static::GROUP_PRODUCT_RESULTS],
        );
    }

    public function testGivenTooLongPromptWhenSuggestedPromptsAreReadThenItIsTruncated(): void
    {
        // Arrange
        $longPrompt = str_repeat('a', 200);
        $aiCommerceConfig = $this->createConfigReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => $longPrompt,
        ]);

        // Act
        $suggestedPrompts = $aiCommerceConfig->getStorefrontAssistantSuggestedPrompts();

        // Assert
        $this->assertSame(120, mb_strlen($suggestedPrompts[static::GROUP_PRODUCT_RESULTS][0]));
    }

    /**
     * @param array<string, string> $configurationValues
     */
    protected function createConfigReturning(array $configurationValues): AiCommerceConfig
    {
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
}
