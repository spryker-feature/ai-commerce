<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt;

use ArrayObject;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use SprykerFeature\Shared\AiCommerce\BackofficeAssistant\BackofficeAssistantEventType;
use SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Attachment\AttachmentBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Emitter\SseEventEmitterInterface;
use Throwable;

class PromptProcessor implements PromptProcessorInterface
{
    use LoggerTrait;

    protected const string AGENT_GUARDRAIL = 'Guardrail';

    protected const string KEY_TYPE = 'type';

    protected const string KEY_AGENT = 'agent';

    protected const string KEY_CONVERSATION_REFERENCE = 'conversation_reference';

    protected const string KEY_MESSAGE = 'message';

    protected const string MESSAGE_AI_SERVICE_UNAVAILABLE = 'backoffice_assistant.error.ai_service_unavailable';

    /**
     * @param array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface> $agentPlugins
     */
    public function __construct(
        protected AiCommerceFacadeInterface $aiCommerceFacade,
        protected AiFoundationFacadeInterface $aiFoundationFacade,
        protected array $agentPlugins,
        protected AttachmentBuilderInterface $attachmentBuilder,
        protected SseEventEmitterInterface $eventEmitter,
        protected IntentRouterInterface $intentRouter,
        protected BackofficeAssistantPromptRequestValidatorInterface $promptRequestValidator,
        protected GlossaryFacadeInterface $glossaryFacade,
    ) {
    }

    public function handle(BackofficeAssistantPromptRequestTransfer $promptRequestTransfer): void
    {
        try {
            $validationErrors = $this->promptRequestValidator->validate($promptRequestTransfer);

            if ($validationErrors !== []) {
                foreach ($validationErrors as $errorTransfer) {
                    $this->eventEmitter->emit(BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => $this->glossaryFacade->translate((string)$errorTransfer->getMessage())]);
                }

                return;
            }

            $conversationReference = $this->resolveConversationReference(
                $promptRequestTransfer->getPromptOrFail(),
                (string)$promptRequestTransfer->getConversationReference(),
                $promptRequestTransfer->getUserUuidOrFail(),
            );

            $selectedAgent = (string)$promptRequestTransfer->getSelectedAgent();

            $this->aiCommerceFacade->updateBackofficeAssistantConversationCollection(
                (new BackofficeAssistantConversationCollectionRequestTransfer())
                    ->addBackofficeAssistantConversation(
                        (new BackofficeAssistantConversationTransfer())
                            ->setConversationReference($conversationReference)
                            ->setUserSelectedAgent($selectedAgent ?: null),
                    ),
            );

            if ($selectedAgent) {
                $this->handleSelectedAgent($promptRequestTransfer, $conversationReference, $selectedAgent);

                return;
            }

            $this->handleIntentRouting($promptRequestTransfer, $conversationReference);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                sprintf('Backoffice assistant prompt handling failed: %s', $throwable->getMessage()),
                ['exception' => $throwable],
            );

            $this->eventEmitter->emit(BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => $this->glossaryFacade->translate(static::MESSAGE_AI_SERVICE_UNAVAILABLE)]);
        }
    }

    protected function handleSelectedAgent(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
        string $conversationReference,
        string $selectedAgent,
    ): void {
        $previousAgent = $this->emitPreviousAgentEvent($conversationReference);

        if ($previousAgent !== $selectedAgent) {
            $this->aiCommerceFacade->updateBackofficeAssistantConversationCollection(
                (new BackofficeAssistantConversationCollectionRequestTransfer())
                    ->addBackofficeAssistantConversation(
                        (new BackofficeAssistantConversationTransfer())
                            ->setConversationReference($conversationReference)
                            ->setAgent($selectedAgent),
                    ),
            );
        }

        $this->eventEmitter->emit(BackofficeAssistantEventType::AgentSelected, [
            static::KEY_AGENT => $selectedAgent,
            static::KEY_CONVERSATION_REFERENCE => $conversationReference,
        ]);

        $this->executeSelectedAgent($promptRequestTransfer, $selectedAgent, $conversationReference);
    }

    protected function handleIntentRouting(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
        string $conversationReference,
    ): void {
        $previousAgent = $this->resolveConversationAgent($conversationReference);

        $conversationHistory = $this->resolveConversationHistory($conversationReference);

        $intentRouterResponse = $this->intentRouter->route(
            $conversationHistory,
            $promptRequestTransfer->getPromptOrFail(),
            $previousAgent,
            $promptRequestTransfer->getContext(),
        );

        if (!$intentRouterResponse) {
            $this->eventEmitter->emit(BackofficeAssistantEventType::Error, [static::KEY_MESSAGE => $this->glossaryFacade->translate(static::MESSAGE_AI_SERVICE_UNAVAILABLE)]);

            return;
        }

        $selectedAgent = $intentRouterResponse->getAgent() ?: static::AGENT_GUARDRAIL;

        if ($selectedAgent !== static::AGENT_GUARDRAIL && $previousAgent !== $selectedAgent) {
            $this->aiCommerceFacade->updateBackofficeAssistantConversationCollection(
                (new BackofficeAssistantConversationCollectionRequestTransfer())
                    ->addBackofficeAssistantConversation(
                        (new BackofficeAssistantConversationTransfer())
                            ->setConversationReference($conversationReference)
                            ->setAgent($selectedAgent),
                    ),
            );
        }

        $this->eventEmitter->emit(BackofficeAssistantEventType::AgentSelected, [
            static::KEY_AGENT => $selectedAgent,
            static::KEY_CONVERSATION_REFERENCE => $conversationReference,
        ]);

        if ($selectedAgent !== $previousAgent && $selectedAgent !== static::AGENT_GUARDRAIL) {
            $this->eventEmitter->emit(BackofficeAssistantEventType::Reasoning, [
                static::KEY_MESSAGE => $intentRouterResponse->getReasoningMessage(),
            ]);
        }

        if ($selectedAgent === static::AGENT_GUARDRAIL) {
            $this->eventEmitter->emit(BackofficeAssistantEventType::AiResponse, [
                static::KEY_MESSAGE => $intentRouterResponse->getReasoningMessage(),
                static::KEY_CONVERSATION_REFERENCE => $conversationReference,
            ]);

            return;
        }

        $this->executeSelectedAgent($promptRequestTransfer, $selectedAgent, $conversationReference);
    }

    protected function resolveConversationReference(string $prompt, string $requestedConversationReference, string $userUuid): string
    {
        if ($requestedConversationReference && $this->hasBackofficeAssistantConversationForUser($userUuid, $requestedConversationReference)) {
            return $requestedConversationReference;
        }

        return $this->createNewConversation($userUuid, $prompt);
    }

    protected function createNewConversation(string $userUuid, string $prompt): string
    {
        $response = $this->aiCommerceFacade->createBackofficeAssistantConversationCollection(
            (new BackofficeAssistantConversationCollectionRequestTransfer())
                ->addBackofficeAssistantConversation(
                    (new BackofficeAssistantConversationTransfer())
                        ->setUserUuid($userUuid)
                        ->setName(mb_substr($prompt, 0, 150)),
                ),
        );

        return (string)$response->getBackofficeAssistantConversations()->offsetGet(0)->getConversationReference();
    }

    protected function resolveConversationAgent(string $conversationReference): ?string
    {
        $criteria = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions(
                (new BackofficeAssistantConversationConditionsTransfer())->addConversationReference($conversationReference),
            );

        $conversations = $this->aiCommerceFacade
            ->getBackofficeAssistantConversationCollection($criteria)
            ->getBackofficeAssistantConversations();

        if ($conversations->count() === 0) {
            return null;
        }

        return $conversations->offsetGet(0)->getAgent();
    }

    protected function hasBackofficeAssistantConversationForUser(string $userUuid, string $conversationReference): bool
    {
        $criteria = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions(
                (new BackofficeAssistantConversationConditionsTransfer())
                    ->addConversationReference($conversationReference)
                    ->addUserUuid($userUuid),
            );

        return $this->aiCommerceFacade
            ->getBackofficeAssistantConversationCollection($criteria)
            ->getBackofficeAssistantConversations()
            ->count() > 0;
    }

    protected function resolveConversationHistory(string $conversationReference): ConversationHistoryTransfer
    {
        $criteria = (new ConversationHistoryCriteriaTransfer())
            ->setConversationHistoryConditions(
                (new ConversationHistoryConditionsTransfer())->addConversationReference($conversationReference),
            );

        $conversationHistories = $this->aiFoundationFacade
            ->getConversationHistoryCollection($criteria)
            ->getConversationHistories();

        if ($conversationHistories->count() > 0) {
            /** @var \Generated\Shared\Transfer\ConversationHistoryTransfer $conversationHistory */
            $conversationHistory = $conversationHistories->offsetGet(0);

            return $conversationHistory;
        }

        return new ConversationHistoryTransfer();
    }

    protected function emitPreviousAgentEvent(string $conversationReference): ?string
    {
        $previousAgent = $this->resolveConversationAgent($conversationReference);

        if (!$previousAgent) {
            return null;
        }

        $this->eventEmitter->emit(BackofficeAssistantEventType::AgentSelected, [
            static::KEY_AGENT => $previousAgent,
            static::KEY_CONVERSATION_REFERENCE => $conversationReference,
        ]);

        return $previousAgent;
    }

    protected function executeSelectedAgent(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
        string $selectedAgent,
        string $conversationReference,
    ): void {
        $attachments = $this->attachmentBuilder->buildAttachmentTransfers($promptRequestTransfer->getRawAttachments());

        $agentRequest = (new BackofficeAssistantPromptRequestTransfer())
            ->setPrompt($promptRequestTransfer->getPrompt())
            ->setConversationReference($conversationReference)
            ->setAttachments(new ArrayObject($attachments));

        foreach ($this->agentPlugins as $agentPlugin) {
            if ($agentPlugin->getName() !== $selectedAgent) {
                continue;
            }

            if (!$agentPlugin->isApplicable($agentRequest)) {
                $this->eventEmitter->emit(BackofficeAssistantEventType::Error, [
                    static::KEY_MESSAGE => $this->glossaryFacade->translate(static::MESSAGE_AI_SERVICE_UNAVAILABLE),
                ]);

                return;
            }

            $backofficeAssistantPromptResponse = $agentPlugin->executeAgent($agentRequest);

            if ($backofficeAssistantPromptResponse->getMessage() === null) {
                $this->eventEmitter->emit(BackofficeAssistantEventType::Error, [
                    static::KEY_MESSAGE => $this->glossaryFacade->translate(static::MESSAGE_AI_SERVICE_UNAVAILABLE),
                ]);

                break;
            }

            if ($backofficeAssistantPromptResponse->getAgent() === static::AGENT_GUARDRAIL) {
                $this->eventEmitter->emit(BackofficeAssistantEventType::AgentSelected, [
                    static::KEY_AGENT => static::AGENT_GUARDRAIL,
                    static::KEY_CONVERSATION_REFERENCE => $conversationReference,
                ]);

                if ($backofficeAssistantPromptResponse->getReasoningMessage()) {
                    $this->eventEmitter->emit(BackofficeAssistantEventType::Reasoning, [
                        static::KEY_MESSAGE => $backofficeAssistantPromptResponse->getReasoningMessage(),
                        static::KEY_CONVERSATION_REFERENCE => $conversationReference,
                    ]);
                }
            }

            $this->eventEmitter->emit(BackofficeAssistantEventType::AiResponse, [
                static::KEY_MESSAGE => $backofficeAssistantPromptResponse->getMessage(),
                static::KEY_CONVERSATION_REFERENCE => $conversationReference,
            ]);

            break;
        }
    }
}
