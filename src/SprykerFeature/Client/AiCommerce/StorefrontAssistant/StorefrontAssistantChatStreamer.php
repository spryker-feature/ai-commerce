<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant;

use ArrayObject;
use Exception;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\AgentSelectorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage\ConversationStorageInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Translation\StorefrontAssistantTranslatorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\StorefrontAssistantChatRequestValidationResult;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\StorefrontAssistantChatRequestValidatorInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTrackerInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitterInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;

class StorefrontAssistantChatStreamer implements StorefrontAssistantChatStreamerInterface
{
    use LoggerTrait;

    protected const string LOG_MESSAGE_TURN_FAILED = 'Storefront Assistant turn failed: %s';

    protected const string LOG_MESSAGE_TRANSLATION_FAILED = 'Storefront Assistant error message translation failed for "%s": %s';

    protected const int CONVERSATION_NAME_MAX_LENGTH = 60;

    protected const string MESSAGE_NO_AGENT_AVAILABLE = 'ai_commerce.storefront_assistant.error.no_agent_available';

    protected const string MESSAGE_SESSION_NOT_INITIALIZED = 'ai_commerce.storefront_assistant.error.session_not_initialized';

    protected const string MESSAGE_TURN_FAILED = 'ai_commerce.storefront_assistant.error.turn_failed';

    protected const string MESSAGE_ID_FORMAT = 'msg_%s';

    protected const string DATA_EVENT_AGENT = 'agent';

    protected const string DATA_PART_KEY_NAME = 'name';

    public function __construct(
        protected StorefrontAssistantChatRequestValidatorInterface $storefrontAssistantChatRequestValidator,
        protected ConversationStorageInterface $conversationStorage,
        protected AgentSelectorInterface $agentSelector,
        protected StreamEventEmitterInterface $streamEventEmitter,
        protected StreamBlockTrackerInterface $streamBlockTracker,
        protected StorefrontAssistantTranslatorInterface $storefrontAssistantTranslator
    ) {
    }

    public function validateStorefrontAssistantChatRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): StorefrontAssistantChatResponseTransfer {
        $storefrontAssistantChatResponseTransfer = (new StorefrontAssistantChatResponseTransfer())
            ->setIsSuccessful(true)
            ->setConversationReference($storefrontAssistantChatRequestTransfer->getConversationReference());

        $storefrontAssistantChatRequestValidationResult = $this->storefrontAssistantChatRequestValidator
            ->validate($storefrontAssistantChatRequestTransfer);

        if ($storefrontAssistantChatRequestValidationResult->isValid()) {
            return $storefrontAssistantChatResponseTransfer;
        }

        $storefrontAssistantChatResponseTransfer->setIsSuccessful(false);

        $localeName = $storefrontAssistantChatRequestTransfer->getLocaleName();

        foreach ($storefrontAssistantChatRequestValidationResult->getErrors() as $errorTransfer) {
            $storefrontAssistantChatResponseTransfer->addError(
                (new ErrorTransfer())->setMessage($this->translateError($errorTransfer, $localeName)),
            );
        }

        return $storefrontAssistantChatResponseTransfer;
    }

    public function executeStorefrontAssistantChat(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): void {
        $this->startMessage();

        try {
            $storefrontAssistantChatRequestValidationResult = $this->storefrontAssistantChatRequestValidator
                ->validate($storefrontAssistantChatRequestTransfer);

            if (!$storefrontAssistantChatRequestValidationResult->isValid()) {
                $this->emitValidationErrors($storefrontAssistantChatRequestValidationResult, $storefrontAssistantChatRequestTransfer->getLocaleName());

                return;
            }

            $storefrontAssistantChatRequestTransfer->setAttachments(
                new ArrayObject($storefrontAssistantChatRequestValidationResult->getAttachments()),
            );

            $this->executeTurn($storefrontAssistantChatRequestTransfer);
        } catch (Exception $exception) {
            $this->getLogger()->error(
                sprintf(static::LOG_MESSAGE_TURN_FAILED, $exception->getMessage()),
                ['exception' => $exception],
            );

            $this->emitErrorMessage(
                $this->translateWithGlossaryKeyFallback(static::MESSAGE_TURN_FAILED, $storefrontAssistantChatRequestTransfer->getLocaleName()),
            );
        } finally {
            $this->finishMessage();
        }
    }

    protected function emitValidationErrors(
        StorefrontAssistantChatRequestValidationResult $storefrontAssistantChatRequestValidationResult,
        ?string $localeName
    ): void {
        foreach ($storefrontAssistantChatRequestValidationResult->getErrors() as $errorTransfer) {
            $this->emitErrorMessage($this->translateError($errorTransfer, $localeName));
        }
    }

    protected function executeTurn(StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer): void
    {
        $storefrontAssistantAgentPlugin = $this->agentSelector->selectAgent($storefrontAssistantChatRequestTransfer);

        if ($storefrontAssistantAgentPlugin === null) {
            $this->emitError(static::MESSAGE_NO_AGENT_AVAILABLE, $storefrontAssistantChatRequestTransfer->getLocaleName());

            return;
        }

        $this->saveConversationMetadata(
            (string)$storefrontAssistantChatRequestTransfer->getCustomerReference(),
            (string)$storefrontAssistantChatRequestTransfer->getConversationReference(),
            (string)$storefrontAssistantChatRequestTransfer->getMessage(),
            $storefrontAssistantChatRequestTransfer->getSelectedAgent(),
            $storefrontAssistantAgentPlugin->getName(),
        );

        $this->streamEventEmitter->emitDataEvent(static::DATA_EVENT_AGENT, [
            static::DATA_PART_KEY_NAME => $storefrontAssistantAgentPlugin->getName(),
        ]);

        $promptResponseTransfer = $storefrontAssistantAgentPlugin->executeAgent($storefrontAssistantChatRequestTransfer);

        if ($promptResponseTransfer->getIsSuccessful() !== true) {
            $this->emitPromptResponseError($promptResponseTransfer, $storefrontAssistantChatRequestTransfer->getLocaleName());
        }
    }

    protected function emitPromptResponseError(
        PromptResponseTransfer $promptResponseTransfer,
        ?string $localeName
    ): void {
        if ($promptResponseTransfer->getErrors()->count() === 0) {
            $this->emitError(static::MESSAGE_SESSION_NOT_INITIALIZED, $localeName);

            return;
        }

        foreach ($promptResponseTransfer->getErrors() as $errorTransfer) {
            $this->getLogger()->error(sprintf(static::LOG_MESSAGE_TURN_FAILED, $errorTransfer->getMessage()), [
                'provider' => $promptResponseTransfer->getProvider(),
                'model' => $promptResponseTransfer->getModel(),
            ]);
        }

        $this->emitError(static::MESSAGE_TURN_FAILED, $localeName);
    }

    protected function translateError(ErrorTransfer $errorTransfer, ?string $localeName): string
    {
        return $this->storefrontAssistantTranslator->translate(
            (string)$errorTransfer->getMessage(),
            $localeName,
            $errorTransfer->getParameters(),
        );
    }

    protected function emitError(string $glossaryKey, ?string $localeName): void
    {
        $this->emitErrorMessage($this->storefrontAssistantTranslator->translate($glossaryKey, $localeName));
    }

    protected function translateWithGlossaryKeyFallback(string $glossaryKey, ?string $localeName): string
    {
        try {
            return $this->storefrontAssistantTranslator->translate($glossaryKey, $localeName);
        } catch (Exception $exception) {
            $this->getLogger()->error(
                sprintf(static::LOG_MESSAGE_TRANSLATION_FAILED, $glossaryKey, $exception->getMessage()),
                ['exception' => $exception],
            );

            return $glossaryKey;
        }
    }

    protected function emitErrorMessage(string $errorMessage): void
    {
        $this->streamBlockTracker->closeOpenBlock();

        $this->streamEventEmitter->emitEvent(StreamEventType::Error, [
            StreamEventKey::ERROR_TEXT => $errorMessage,
        ]);
    }

    protected function startMessage(): void
    {
        $this->streamBlockTracker->resetBlocks();

        $this->streamEventEmitter->emitEvent(StreamEventType::Start, [
            StreamEventKey::MESSAGE_ID => sprintf(static::MESSAGE_ID_FORMAT, uniqid()),
        ]);
    }

    protected function finishMessage(): void
    {
        $this->streamBlockTracker->closeOpenBlock();
        $this->streamEventEmitter->emitEvent(StreamEventType::Finish);
        $this->streamEventEmitter->emitDone();
    }

    protected function saveConversationMetadata(
        string $customerReference,
        string $conversationReference,
        string $userContent,
        ?string $userSelectedAgent,
        ?string $agentName
    ): void {
        if ($conversationReference === '') {
            return;
        }

        $storefrontAssistantConversationTransfer = $this->conversationStorage->findConversation(
            $customerReference,
            $conversationReference,
        ) ?? (new StorefrontAssistantConversationTransfer())
            ->setConversationReference($conversationReference)
            ->setCustomerReference($customerReference)
            ->setCreatedAt(time());

        $storefrontAssistantConversationTransfer
            ->setCustomerReference($customerReference)
            ->setUpdatedAt(time())
            ->setUserSelectedAgent($userSelectedAgent);

        if ($agentName !== null) {
            $storefrontAssistantConversationTransfer->setAgent($agentName);
        }

        if (trim((string)$storefrontAssistantConversationTransfer->getName()) === '') {
            $storefrontAssistantConversationTransfer->setName($this->deriveConversationName($userContent, $conversationReference));
        }

        $this->conversationStorage->saveConversation(
            $customerReference,
            $conversationReference,
            $storefrontAssistantConversationTransfer,
        );
    }

    protected function deriveConversationName(string $userContent, string $conversationReference): string
    {
        $userContent = trim($userContent);

        if ($userContent === '') {
            return $conversationReference;
        }

        return mb_substr($userContent, 0, static::CONVERSATION_NAME_MAX_LENGTH);
    }
}
