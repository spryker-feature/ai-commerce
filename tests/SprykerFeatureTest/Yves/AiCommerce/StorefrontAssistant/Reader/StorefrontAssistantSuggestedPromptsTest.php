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

    protected const string LOCALE_NAME_DE = 'de_DE';

    protected const string LOCALE_NAME_EN = 'en_US';

    protected const string LOCALE_NAME_FR = 'fr_FR';

    /**
     * @var list<string>
     */
    protected const array GERMAN_PROMPTS = ['Vergleiche diese für mich', 'Zeige mir günstigere Alternativen'];

    /**
     * @var list<string>
     */
    protected const array ENGLISH_PROMPTS = ['Compare these for me', 'Show me cheaper alternatives'];

    protected AiCommerceYvesTester $tester;

    public function testGivenLocalizedPromptsWhenSuggestedPromptsAreReadInGermanThenGermanPromptsAreReturned(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderWithLocalizedPrompts(static::LOCALE_NAME_DE);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(static::GERMAN_PROMPTS, $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    public function testGivenLocalizedPromptsWhenSuggestedPromptsAreReadInEnglishThenEnglishPromptsAreReturned(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderWithLocalizedPrompts(static::LOCALE_NAME_EN);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(static::ENGLISH_PROMPTS, $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    public function testGivenLocalizedPromptsWithoutCurrentLocaleWhenSuggestedPromptsAreReadThenGroupIsEmpty(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderWithLocalizedPrompts(static::LOCALE_NAME_FR);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame([], $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    /**
     * @dataProvider provideValuesWithoutPromptList
     */
    public function testGivenValueWithoutPromptListOfCurrentLocaleWhenSuggestedPromptsAreReadThenGroupIsEmpty(string $value): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning($value);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame([], $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideValuesWithoutPromptList(): array
    {
        return [
            'empty value' => [''],
            'old text value' => ['Compare these for me|Show me cheaper alternatives'],
            'prompts as one text value' => ['{"en_US": "Compare these for me|Show me cheaper alternatives"}'],
            'empty list' => ['{"en_US": []}'],
        ];
    }

    public function testGivenPromptsWhenSuggestedPromptsAreReadThenProductResultsGroupIsReturnedBesidePageTypes(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning($this->encodePrompts(static::ENGLISH_PROMPTS));

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(static::ENGLISH_PROMPTS, $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
        $this->assertArrayHasKey(StorefrontAssistantPageType::Default->value, $suggestedPrompts);
    }

    public function testGivenPromptWithPipeCharacterWhenSuggestedPromptsAreReadThenPromptIsNotSplit(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning($this->encodePrompts(['Compare A|B models']));

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(['Compare A|B models'], $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    public function testGivenBlankEntriesAndTooManyPromptsWhenSuggestedPromptsAreReadThenBlanksAreDroppedAndCountIsCapped(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning(
            $this->encodePrompts(['One', '', 'Two', '  ', 'Three', 'Four', 'Five']),
        );

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(['One', 'Two', 'Three', 'Four'], $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    public function testGivenEntriesThatAreNotTextWhenSuggestedPromptsAreReadThenOnlyTextEntriesAreReturned(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning('{"en_US": ["One", 2, null, ["Three"], "Four"]}');

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(['One', 'Four'], $suggestedPrompts[static::GROUP_PRODUCT_RESULTS]);
    }

    public function testGivenTooLongPromptWhenSuggestedPromptsAreReadThenItIsTruncated(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning($this->encodePrompts([str_repeat('a', 200)]));

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(120, mb_strlen($suggestedPrompts[static::GROUP_PRODUCT_RESULTS][0]));
    }

    protected function createReaderWithLocalizedPrompts(string $localeName): StorefrontAssistantSuggestedPromptReaderInterface
    {
        $value = json_encode([
            static::LOCALE_NAME_EN => static::ENGLISH_PROMPTS,
            static::LOCALE_NAME_DE => static::GERMAN_PROMPTS,
        ], JSON_THROW_ON_ERROR);

        return $this->tester->createStorefrontAssistantSuggestedPromptReader([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => $value,
        ], $localeName);
    }

    protected function createReaderReturning(string $value): StorefrontAssistantSuggestedPromptReaderInterface
    {
        return $this->tester->createStorefrontAssistantSuggestedPromptReader([
            static::CONFIGURATION_KEY_PRODUCT_RESULTS_PROMPTS => $value,
        ], static::LOCALE_NAME_EN);
    }

    /**
     * @param list<string> $prompts
     */
    protected function encodePrompts(array $prompts): string
    {
        return json_encode([static::LOCALE_NAME_EN => $prompts], JSON_THROW_ON_ERROR);
    }
}
