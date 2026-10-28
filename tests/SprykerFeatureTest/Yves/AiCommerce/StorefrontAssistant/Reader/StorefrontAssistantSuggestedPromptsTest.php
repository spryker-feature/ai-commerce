<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Reader;

use Codeception\Stub;
use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\AiCommerceFactory;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Reader\StorefrontAssistantSuggestedPromptReaderInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Reader
 * @group StorefrontAssistantSuggestedPromptsTest
 */
class StorefrontAssistantSuggestedPromptsTest extends Unit
{
    protected const string GROUP_PRODUCT_RESULTS = 'product_results';

    protected const string CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:product_results_prompts';

    public function testGivenProductResultsPromptsWhenSuggestedPromptsAreReadThenProductResultsGroupIsReturnedBesidePageTypes(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => 'Compare these for me|Show me cheaper alternatives',
        ]);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

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
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => '',
        ]);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame([], $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    public function testGivenBlankEntriesAndTooManyPromptsWhenSuggestedPromptsAreReadThenBlanksAreDroppedAndCountIsCapped(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => 'One||Two|  |Three|Four|Five',
        ]);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

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
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => $longPrompt,
        ]);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(120, mb_strlen($suggestedPrompts[static::GROUP_PRODUCT_RESULTS][0]));
    }

    /**
     * @param array<string, string> $configurationValues
     */
    protected function createReaderReturning(array $configurationValues): StorefrontAssistantSuggestedPromptReaderInterface
    {
        /**
         * @var \SprykerFeature\Yves\AiCommerce\AiCommerceConfig $aiCommerceConfig
         */
        $aiCommerceConfig = Stub::construct(AiCommerceConfig::class, [], [
            'getModuleConfig' => function (string $key, mixed $default = null) use ($configurationValues): mixed {
                return $configurationValues[$key] ?? $default;
            },
        ]);

        $aiCommerceFactory = new AiCommerceFactory();
        $aiCommerceFactory->setConfig($aiCommerceConfig);

        return $aiCommerceFactory->createStorefrontAssistantSuggestedPromptReader();
    }
}
