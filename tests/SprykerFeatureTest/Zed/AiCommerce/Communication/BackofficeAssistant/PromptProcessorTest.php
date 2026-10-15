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
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
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
    protected const string KEY_MESSAGE = 'message';

    protected const string GLOSSARY_KEY_PROMPT_REQUIRED = 'backoffice_assistant.validation.prompt_required';

    protected const string GLOSSARY_KEY_USER_ID_REQUIRED = 'backoffice_assistant.validation.user_id_required';

    protected const string GLOSSARY_KEY_AI_SERVICE_UNAVAILABLE = 'backoffice_assistant.error.ai_service_unavailable';

    protected const string MESSAGE_PROMPT_REQUIRED = 'Eingabe ist erforderlich.';

    protected const string MESSAGE_USER_ID_REQUIRED = 'Benutzer ist nicht identifiziert.';

    protected const string MESSAGE_AI_SERVICE_UNAVAILABLE = 'KI-Dienst nicht verfügbar.';

    protected const array TRANSLATIONS = [
        self::GLOSSARY_KEY_PROMPT_REQUIRED => self::MESSAGE_PROMPT_REQUIRED,
        self::GLOSSARY_KEY_USER_ID_REQUIRED => self::MESSAGE_USER_ID_REQUIRED,
        self::GLOSSARY_KEY_AI_SERVICE_UNAVAILABLE => self::MESSAGE_AI_SERVICE_UNAVAILABLE,
    ];

    protected const string PROMPT = 'Show me the latest orders';

    protected AiCommerceCommunicationTester $tester;

    public function testProcessEmitsTranslatedValidationErrorsWhenPromptAndUserAreMissing(): void
    {
        // Arrange
        $emittedEvents = [];
        $promptProcessor = $this->createPromptProcessor(
            $this->createRecordingSseEventEmitter($emittedEvents),
            $this->createMock(IntentRouterInterface::class),
        );

        // Act
        $promptProcessor->process(new BackofficeAssistantPromptRequestTransfer());

        // Assert
        $this->assertSame([
            [BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => static::MESSAGE_PROMPT_REQUIRED]],
            [BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => static::MESSAGE_USER_ID_REQUIRED]],
        ], $emittedEvents);
    }

    public function testProcessEmitsTranslatedAiServiceUnavailableErrorWhenIntentRouterFails(): void
    {
        // Arrange
        $emittedEvents = [];
        $intentRouterMock = $this->createMock(IntentRouterInterface::class);
        $intentRouterMock->method('route')->willThrowException(new RuntimeException());

        $promptProcessor = $this->createPromptProcessor($this->createRecordingSseEventEmitter($emittedEvents), $intentRouterMock);

        // Act
        $promptProcessor->process($this->createPromptRequestTransfer());

        // Assert
        $this->assertSame(
            [[BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => static::MESSAGE_AI_SERVICE_UNAVAILABLE]]],
            $emittedEvents,
        );
    }

    public function testProcessEmitsTranslatedAiServiceUnavailableErrorWhenIntentRouterReturnsNoResponse(): void
    {
        // Arrange
        $emittedEvents = [];
        $intentRouterMock = $this->createMock(IntentRouterInterface::class);
        $intentRouterMock->method('route')->willReturn(null);

        $promptProcessor = $this->createPromptProcessor($this->createRecordingSseEventEmitter($emittedEvents), $intentRouterMock);

        // Act
        $promptProcessor->process($this->createPromptRequestTransfer());

        // Assert
        $this->assertSame(
            [[BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => static::MESSAGE_AI_SERVICE_UNAVAILABLE]]],
            $emittedEvents,
        );
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
            $this->createTranslatorFacadeMock(),
        );
    }

    protected function createTranslatorFacadeMock(): TranslatorFacadeInterface
    {
        $translatorFacadeMock = $this->createMock(TranslatorFacadeInterface::class);
        $translatorFacadeMock->method('trans')->willReturnCallback(
            fn (string $id): string => static::TRANSLATIONS[$id] ?? $id,
        );

        return $translatorFacadeMock;
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
