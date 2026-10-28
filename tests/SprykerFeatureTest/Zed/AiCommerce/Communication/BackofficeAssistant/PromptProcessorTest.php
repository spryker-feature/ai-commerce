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
use SprykerFeature\Shared\AiCommerce\Stream\MessageStreamer;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTracker;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitterInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;
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
        $emittedErrorTexts = [];
        $promptProcessor = $this->createPromptProcessor(
            $this->createErrorRecordingStreamEventEmitter($emittedErrorTexts),
            $this->createMock(IntentRouterInterface::class),
        );

        // Act
        $promptProcessor->process(new BackofficeAssistantPromptRequestTransfer());

        // Assert
        $this->assertSame([
            static::MESSAGE_PROMPT_REQUIRED,
            static::MESSAGE_USER_ID_REQUIRED,
        ], $emittedErrorTexts);
    }

    public function testProcessEmitsTranslatedAiServiceUnavailableErrorWhenIntentRouterFails(): void
    {
        // Arrange
        $emittedErrorTexts = [];
        $intentRouterMock = $this->createMock(IntentRouterInterface::class);
        $intentRouterMock->method('route')->willThrowException(new RuntimeException());

        $promptProcessor = $this->createPromptProcessor($this->createErrorRecordingStreamEventEmitter($emittedErrorTexts), $intentRouterMock);

        // Act
        $promptProcessor->process($this->createPromptRequestTransfer());

        // Assert
        $this->assertSame([static::MESSAGE_AI_SERVICE_UNAVAILABLE], $emittedErrorTexts);
    }

    public function testProcessEmitsTranslatedAiServiceUnavailableErrorWhenIntentRouterReturnsNoResponse(): void
    {
        // Arrange
        $emittedErrorTexts = [];
        $intentRouterMock = $this->createMock(IntentRouterInterface::class);
        $intentRouterMock->method('route')->willReturn(null);

        $promptProcessor = $this->createPromptProcessor($this->createErrorRecordingStreamEventEmitter($emittedErrorTexts), $intentRouterMock);

        // Act
        $promptProcessor->process($this->createPromptRequestTransfer());

        // Assert
        $this->assertSame([static::MESSAGE_AI_SERVICE_UNAVAILABLE], $emittedErrorTexts);
    }

    protected function createPromptProcessor(
        StreamEventEmitterInterface $streamEventEmitter,
        IntentRouterInterface $intentRouter,
    ): PromptProcessorInterface {
        /** @var \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory $communicationFactory */
        $communicationFactory = $this->tester->getFactory();

        return new PromptProcessor(
            $this->tester->getLocator()->aiCommerce()->facade(),
            $communicationFactory->getAiFoundationFacade(),
            [],
            $communicationFactory->createAttachmentBuilder(),
            new MessageStreamer($streamEventEmitter, new StreamBlockTracker($streamEventEmitter)),
            $intentRouter,
            $communicationFactory->createBackofficeAssistantPromptRequestValidator(),
            $this->createTranslatorFacadeMock(),
            $communicationFactory->createPromptContextFormatter(),
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
     * @param array<int, string> $emittedErrorTexts
     */
    protected function createErrorRecordingStreamEventEmitter(array &$emittedErrorTexts): StreamEventEmitterInterface
    {
        $streamEventEmitterMock = $this->createMock(StreamEventEmitterInterface::class);
        $streamEventEmitterMock->method('emitEvent')->willReturnCallback(
            function (StreamEventType $streamEventType, array $fields = []) use (&$emittedErrorTexts): void {
                if ($streamEventType !== StreamEventType::Error) {
                    return;
                }

                $emittedErrorTexts[] = $fields[StreamEventKey::ERROR_TEXT];
            },
        );

        return $streamEventEmitterMock;
    }

    protected function createPromptRequestTransfer(): BackofficeAssistantPromptRequestTransfer
    {
        return (new BackofficeAssistantPromptRequestTransfer())
            ->setPrompt(static::PROMPT)
            ->setIdUser($this->tester->haveUser()->getIdUserOrFail());
    }
}
