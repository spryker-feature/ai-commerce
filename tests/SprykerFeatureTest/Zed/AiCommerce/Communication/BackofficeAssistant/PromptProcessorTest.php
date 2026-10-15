<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\BackofficeAssistant;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use RuntimeException;
use Spryker\Shared\Translator\TranslatorConstants;
use Spryker\Zed\Locale\LocaleDependencyProvider;
use SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Emitter\SseEventEmitterInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\IntentRouterInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptProcessor;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptProcessorInterface;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group BackofficeAssistant
 * @group PromptProcessorTest
 * Add your own group annotations below this line
 */
class PromptProcessorTest extends Unit
{
    protected const string LOCALE_EN_US = 'en_US';

    protected const string LOCALE_DE_DE = 'de_DE';

    protected const string MODULE_ZED_TRANSLATION_FILE_PATTERN = '%s/data/translation/Zed/[a-z][a-z]_[A-Z][A-Z].csv';

    protected const string TRANSLATOR_CACHE_DIRECTORY = 'ai-commerce-translator';

    protected const string KEY_MESSAGE = 'message';

    protected const string MESSAGE_PROMPT_REQUIRED_EN_US = 'Prompt is required.';

    protected const string MESSAGE_USER_ID_REQUIRED_EN_US = 'User is not identified.';

    protected const string MESSAGE_AI_SERVICE_UNAVAILABLE_EN_US = 'AI service unavailable.';

    protected const string MESSAGE_PROMPT_REQUIRED_DE_DE = 'Eingabe ist erforderlich.';

    protected const string MESSAGE_USER_ID_REQUIRED_DE_DE = 'Benutzer ist nicht identifiziert.';

    protected const string MESSAGE_AI_SERVICE_UNAVAILABLE_DE_DE = 'KI-Dienst nicht verfügbar.';

    protected const string PROMPT = 'Show me the latest orders';

    protected AiCommerceCommunicationTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->configureTranslatorWithModuleTranslationFiles();
    }

    /**
     * @dataProvider provideValidationErrorMessagesByLocale
     */
    public function testProcessEmitsTranslatedValidationErrorsWhenPromptAndUserAreMissing(
        string $localeName,
        string $expectedPromptRequiredMessage,
        string $expectedUserIdRequiredMessage,
    ): void {
        // Arrange
        $this->tester->setDependency(LocaleDependencyProvider::LOCALE_CURRENT, $localeName);
        $emittedEvents = [];
        $promptProcessor = $this->createPromptProcessor(
            $this->createRecordingSseEventEmitter($emittedEvents),
            $this->createMock(IntentRouterInterface::class),
        );

        // Act
        $promptProcessor->process(new BackofficeAssistantPromptRequestTransfer());

        // Assert
        $this->assertSame([
            [BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => $expectedPromptRequiredMessage]],
            [BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => $expectedUserIdRequiredMessage]],
        ], $emittedEvents);
    }

    /**
     * @dataProvider provideAiServiceUnavailableMessageByLocale
     */
    public function testProcessEmitsTranslatedAiServiceUnavailableErrorWhenIntentRouterFails(
        string $localeName,
        string $expectedAiServiceUnavailableMessage,
    ): void {
        // Arrange
        $this->tester->setDependency(LocaleDependencyProvider::LOCALE_CURRENT, $localeName);
        $emittedEvents = [];
        $intentRouterMock = $this->createMock(IntentRouterInterface::class);
        $intentRouterMock->method('route')->willThrowException(new RuntimeException());

        $promptProcessor = $this->createPromptProcessor($this->createRecordingSseEventEmitter($emittedEvents), $intentRouterMock);

        // Act
        $promptProcessor->process($this->createPromptRequestTransfer());

        // Assert
        $this->assertSame(
            [[BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => $expectedAiServiceUnavailableMessage]]],
            $emittedEvents,
        );
    }

    /**
     * @dataProvider provideAiServiceUnavailableMessageByLocale
     */
    public function testProcessEmitsTranslatedAiServiceUnavailableErrorWhenIntentRouterReturnsNoResponse(
        string $localeName,
        string $expectedAiServiceUnavailableMessage,
    ): void {
        // Arrange
        $this->tester->setDependency(LocaleDependencyProvider::LOCALE_CURRENT, $localeName);
        $emittedEvents = [];
        $intentRouterMock = $this->createMock(IntentRouterInterface::class);
        $intentRouterMock->method('route')->willReturn(null);

        $promptProcessor = $this->createPromptProcessor($this->createRecordingSseEventEmitter($emittedEvents), $intentRouterMock);

        // Act
        $promptProcessor->process($this->createPromptRequestTransfer());

        // Assert
        $this->assertSame(
            [[BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => $expectedAiServiceUnavailableMessage]]],
            $emittedEvents,
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideValidationErrorMessagesByLocale(): array
    {
        return [
            static::LOCALE_EN_US => [static::LOCALE_EN_US, static::MESSAGE_PROMPT_REQUIRED_EN_US, static::MESSAGE_USER_ID_REQUIRED_EN_US],
            static::LOCALE_DE_DE => [static::LOCALE_DE_DE, static::MESSAGE_PROMPT_REQUIRED_DE_DE, static::MESSAGE_USER_ID_REQUIRED_DE_DE],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideAiServiceUnavailableMessageByLocale(): array
    {
        return [
            static::LOCALE_EN_US => [static::LOCALE_EN_US, static::MESSAGE_AI_SERVICE_UNAVAILABLE_EN_US],
            static::LOCALE_DE_DE => [static::LOCALE_DE_DE, static::MESSAGE_AI_SERVICE_UNAVAILABLE_DE_DE],
        ];
    }

    protected function configureTranslatorWithModuleTranslationFiles(): void
    {
        $this->tester->setConfig(
            TranslatorConstants::TRANSLATION_ZED_FILE_PATH_PATTERNS,
            [sprintf(static::MODULE_ZED_TRANSLATION_FILE_PATTERN, dirname(__DIR__, 6))],
        );
        $this->tester->setConfig(TranslatorConstants::TRANSLATION_ZED_CACHE_DIRECTORY, codecept_output_dir(static::TRANSLATOR_CACHE_DIRECTORY));
        $this->tester->setConfig(TranslatorConstants::TRANSLATION_ZED_DEBUG_ENABLED, true);
    }

    protected function createPromptProcessor(
        SseEventEmitterInterface $sseEventEmitter,
        IntentRouterInterface $intentRouter,
    ): PromptProcessorInterface {
        /** @var \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory $communicationFactory */
        $communicationFactory = $this->tester->getFactory();

        return new PromptProcessor(
            $this->tester->getLocator()->aiCommerce()->facade(),
            $communicationFactory->getAiFoundationFacade(),
            [],
            $communicationFactory->createAttachmentBuilder(),
            $sseEventEmitter,
            $intentRouter,
            $communicationFactory->createBackofficeAssistantPromptRequestValidator(),
            $communicationFactory->getTranslatorFacade(),
        );
    }

    /**
     * @param array<int, array{0: \SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType, 1: array<string, mixed>}> $emittedEvents
     */
    protected function createRecordingSseEventEmitter(array &$emittedEvents): SseEventEmitterInterface
    {
        $sseEventEmitterMock = $this->createMock(SseEventEmitterInterface::class);
        $sseEventEmitterMock->method('emit')->willReturnCallback(
            function (BackofficeAssistantEventType $type, array $payload) use (&$emittedEvents): void {
                $emittedEvents[] = [$type, $payload];
            },
        );

        return $sseEventEmitterMock;
    }

    protected function createPromptRequestTransfer(): BackofficeAssistantPromptRequestTransfer
    {
        return (new BackofficeAssistantPromptRequestTransfer())
            ->setPrompt(static::PROMPT)
            ->setIdUser($this->tester->haveUser()->getIdUserOrFail());
    }
}
