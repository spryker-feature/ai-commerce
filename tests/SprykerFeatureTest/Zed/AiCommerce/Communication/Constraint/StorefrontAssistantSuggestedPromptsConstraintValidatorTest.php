<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Constraint;

use Codeception\Test\Unit;
use Spryker\Shared\Translator\TranslatorConstants;
use Spryker\Zed\Locale\LocaleDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Constraint\StorefrontAssistantSuggestedPromptsConstraint;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceCommunicationTester;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group Constraint
 * @group StorefrontAssistantSuggestedPromptsConstraintValidatorTest
 */
class StorefrontAssistantSuggestedPromptsConstraintValidatorTest extends Unit
{
    protected const string LOCALE_NAME_DE = 'de_DE';

    protected AiCommerceCommunicationTester $tester;

    /**
     * @dataProvider provideValidValues
     */
    public function testGivenValidValueWhenValidatedThenNoViolationIsReported(string $value): void
    {
        // Act
        $constraintViolationList = $this->validate($value);

        // Assert
        $this->assertCount(0, $constraintViolationList);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideValidValues(): array
    {
        return [
            'empty value' => [''],
            'empty object' => ['{}'],
            'one available locale' => ['{"en_US": ["What is new?", "What is on sale?"]}'],
            'every available locale' => ['{"en_US": ["What is new?"], "de_DE": ["Was ist neu?"]}'],
            'empty list of a locale' => ['{"de_DE": []}'],
            'prompt with pipe character' => ['{"en_US": ["Compare A|B models"]}'],
        ];
    }

    /**
     * @dataProvider provideValuesInInvalidFormat
     */
    public function testGivenValueThatIsNotLocaleKeyedObjectWhenValidatedThenInvalidFormatIsReported(string $value): void
    {
        // Act
        $constraintViolationList = $this->validate($value);

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame((new StorefrontAssistantSuggestedPromptsConstraint())->invalidFormatMessage, $constraintViolationList->get(0)->getMessage());
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideValuesInInvalidFormat(): array
    {
        return [
            'plain prompts' => ['What is new?|What is on sale?'],
            'broken JSON' => ['{"en_US": "What is new?"'],
            'JSON list' => ['["What is new?"]'],
            'empty JSON list' => ['[]'],
            'JSON number' => ['42'],
            'JSON text' => ['"What is new?"'],
        ];
    }

    public function testGivenLocaleThatIsNotAssignedToAnyStoreWhenValidatedThenUnknownLocaleIsReported(): void
    {
        // Act
        $constraintViolationList = $this->validate('{"en_US": ["What is new?"], "xx_XX": ["What is new?"]}');

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame('The locale "xx_XX" is not assigned to any store.', $constraintViolationList->get(0)->getMessage());
    }

    /**
     * @dataProvider providePromptsThatAreNotListOfText
     */
    public function testGivenPromptsThatAreNotListOfTextWhenValidatedThenInvalidPromptsAreReported(string $value): void
    {
        // Act
        $constraintViolationList = $this->validate($value);

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame('Enter the prompts of the locale "de_DE" as a list of text values.', $constraintViolationList->get(0)->getMessage());
    }

    /**
     * @return array<string, array<string>>
     */
    public static function providePromptsThatAreNotListOfText(): array
    {
        return [
            'one text value' => ['{"de_DE": "Was ist neu?|Was ist im Angebot?"}'],
            'object' => ['{"de_DE": {"first": "Was ist neu?"}}'],
            'list with number' => ['{"de_DE": ["Was ist neu?", 42]}'],
            'list with list' => ['{"de_DE": [["Was ist neu?"]]}'],
        ];
    }

    public function testGivenGermanBackOfficeUserWhenValueWithUnknownLocaleIsValidatedThenGermanMessageIsReported(): void
    {
        // Arrange
        $this->configureModuleTranslations(static::LOCALE_NAME_DE);

        // Act
        $constraintViolationList = $this->validateWithTranslator('{"xx_XX": ["What is new?"]}');

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame('Das Locale "xx_XX" ist keinem Store zugewiesen.', $constraintViolationList->get(0)->getMessage());
    }

    public function testGivenGermanBackOfficeUserWhenValueInInvalidFormatIsValidatedThenGermanMessageIsReported(): void
    {
        // Arrange
        $this->configureModuleTranslations(static::LOCALE_NAME_DE);

        // Act
        $constraintViolationList = $this->validateWithTranslator('What is new?|What is on sale?');

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertStringStartsWith('Geben Sie ein JSON-Objekt', (string)$constraintViolationList->get(0)->getMessage());
    }

    protected function configureModuleTranslations(string $localeName): void
    {
        $this->tester->setDependency(LocaleDependencyProvider::LOCALE_CURRENT, $localeName);
        $this->tester->setConfig(TranslatorConstants::TRANSLATION_ZED_FILE_PATH_PATTERNS, [
            sprintf('%s/data/translation/Zed/[a-z][a-z]_[A-Z][A-Z].csv', dirname(__DIR__, 6)),
        ]);
        $this->tester->setConfig(TranslatorConstants::TRANSLATION_ZED_CACHE_DIRECTORY, codecept_output_dir('ai-commerce-translator'));
        $this->tester->setConfig(TranslatorConstants::TRANSLATION_ZED_DEBUG_ENABLED, true);
    }

    protected function validateWithTranslator(string $value): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()
            ->setTranslator($this->tester->getLocator()->translator()->facade())
            ->getValidator()
            ->validate($value, new StorefrontAssistantSuggestedPromptsConstraint());
    }

    protected function validate(string $value): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate($value, new StorefrontAssistantSuggestedPromptsConstraint());
    }
}
