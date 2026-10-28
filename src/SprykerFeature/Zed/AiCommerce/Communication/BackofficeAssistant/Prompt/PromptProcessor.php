<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt;

use ArrayObject;
use Error;
use Exception;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use SprykerFeature\Shared\AiCommerce\Stream\MessageStreamerInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventBlockType;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeature\Shared\AiCommerce\Stream\StreamMessageState;
use SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface;
use SprykerFeature\Zed\AiCommerce\Communication\Attachment\AttachmentBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface;

class PromptProcessor implements PromptProcessorInterface
{
    use LoggerTrait;

    protected const string AGENT_GUARDRAIL = 'Guardrail';

    protected const string MESSAGE_AI_SERVICE_UNAVAILABLE = 'backoffice_assistant.error.ai_service_unavailable';

    protected const string LOG_MESSAGE_PROMPT_FAILED = 'Backoffice assistant prompt handling failed: %s';

    protected const string LOG_MESSAGE_AGENT_NOT_FOUND = 'Backoffice assistant agent "%s" is not registered.';

    protected const string LOG_MESSAGE_AGENT_NOT_APPLICABLE = 'Backoffice assistant agent "%s" is not applicable to the request.';

    protected const string LOG_MESSAGE_AGENT_WITHOUT_MESSAGE = 'Backoffice assistant agent "%s" answered without a message.';

    protected const string LOG_MESSAGE_EMPTY_ANSWER = 'Backoffice assistant produced an empty answer.';

    protected const int CONVERSATION_NAME_MAX_LENGTH = 150;

    /**
     * @param array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface> $agentPlugins
     */
    public function __construct(
        protected AiCommerceFacadeInterface $aiCommerceFacade,
        protected AiFoundationFacadeInterface $aiFoundationFacade,
        protected array $agentPlugins,
        protected AttachmentBuilderInterface $attachmentBuilder,
        protected MessageStreamerInterface $messageStreamer,
        protected IntentRouterInterface $intentRouter,
        protected BackofficeAssistantPromptRequestValidatorInterface $promptRequestValidator,
        protected TranslatorFacadeInterface $translatorFacade,
        protected PromptContextFormatterInterface $promptContextFormatter,
    ) {
    }

    public function process(BackofficeAssistantPromptRequestTransfer $promptRequestTransfer): void
    {
        $streamMessageState = $this->messageStreamer->startTurn();

        try {
            $this->processPrompt($promptRequestTransfer, $streamMessageState);
        } catch (Exception $exception) {
            $this->getLogger()->error(
                sprintf(static::LOG_MESSAGE_PROMPT_FAILED, $exception->getMessage()),
                ['exception' => $exception],
            );

            $this->messageStreamer->ensureMessageStarted($streamMessageState);
            $this->emitErrorMessage($this->translatorFacade->trans(static::MESSAGE_AI_SERVICE_UNAVAILABLE));
        } catch (Error $error) {
            // An \Error ends the stream without `finish` and `[DONE]`, so the client reports a cut stream with a retry. It is not rethrown, as the Zed error handler would write an HTML error page into the open stream.
            $this->getLogger()->error(
                sprintf(static::LOG_MESSAGE_PROMPT_FAILED, $error->getMessage()),
                ['exception' => $error],
            );
            $this->messageStreamer->abortTurn();

            return;
        }

        $this->messageStreamer->finishMessage($streamMessageState);
    }

    protected function processPrompt(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
        StreamMessageState $streamMessageState
    ): void {
        $validationErrors = $this->promptRequestValidator->validate($promptRequestTransfer);

        if ($validationErrors !== []) {
            $this->messageStreamer->startMessage($streamMessageState);
            $this->emitValidationErrors($validationErrors);

            return;
        }

        $conversationReference = $this->resolveConversationReference(
            $promptRequestTransfer->getPromptOrFail(),
            (string)$promptRequestTransfer->getConversationReference(),
            $promptRequestTransfer->getIdUserOrFail(),
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

        $previousAgent = $this->resolveConversationAgent($conversationReference);

        if ($selectedAgent !== '') {
            $this->startMessage($streamMessageState, $conversationReference, $selectedAgent);
            $this->handleSelectedAgent($promptRequestTransfer, $conversationReference, $selectedAgent, $previousAgent);

            return;
        }

        $this->startMessage($streamMessageState, $conversationReference, $previousAgent);
        $this->handleIntentRouting($promptRequestTransfer, $conversationReference, $previousAgent);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function emitValidationErrors(array $validationErrors): void
    {
        foreach ($validationErrors as $errorTransfer) {
            $this->emitErrorMessage($this->translatorFacade->trans((string)$errorTransfer->getMessage()));
        }
    }

    protected function handleSelectedAgent(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
        string $conversationReference,
        string $selectedAgent,
        ?string $previousAgent,
    ): void {
        if ($previousAgent !== $selectedAgent) {
            $this->updateConversationAgent($conversationReference, $selectedAgent);
        }

        $this->executeSelectedAgent($promptRequestTransfer, $selectedAgent, $conversationReference);
    }

    protected function handleIntentRouting(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
        string $conversationReference,
        ?string $previousAgent,
    ): void {
        $intentRouterResponse = $this->intentRouter->route(
            $this->resolveConversationHistory($conversationReference),
            $promptRequestTransfer->getPromptOrFail(),
            $previousAgent,
            $promptRequestTransfer->getContext(),
        );

        if (!$intentRouterResponse) {
            $this->emitErrorMessage($this->translatorFacade->trans(static::MESSAGE_AI_SERVICE_UNAVAILABLE));

            return;
        }

        $selectedAgent = $intentRouterResponse->getAgent() ?: static::AGENT_GUARDRAIL;

        if ($selectedAgent !== $previousAgent) {
            $this->emitAgentChange($selectedAgent);
        }

        if ($selectedAgent === static::AGENT_GUARDRAIL) {
            $this->emitText((string)$intentRouterResponse->getReasoningMessage());

            return;
        }

        if ($selectedAgent !== $previousAgent) {
            $this->updateConversationAgent($conversationReference, $selectedAgent);
            $this->emitReasoning((string)$intentRouterResponse->getReasoningMessage());
        }

        $this->executeSelectedAgent($promptRequestTransfer, $selectedAgent, $conversationReference);
    }

    protected function updateConversationAgent(string $conversationReference, string $agent): void
    {
        $this->aiCommerceFacade->updateBackofficeAssistantConversationCollection(
            (new BackofficeAssistantConversationCollectionRequestTransfer())
                ->addBackofficeAssistantConversation(
                    (new BackofficeAssistantConversationTransfer())
                        ->setConversationReference($conversationReference)
                        ->setAgent($agent),
                ),
        );
    }

    protected function resolveConversationReference(string $prompt, string $requestedConversationReference, int $idUser): string
    {
        if ($requestedConversationReference && $this->hasBackofficeAssistantConversationForUser($idUser, $requestedConversationReference)) {
            return $requestedConversationReference;
        }

        return $this->createNewConversation($idUser, $prompt);
    }

    protected function createNewConversation(int $idUser, string $prompt): string
    {
        $response = $this->aiCommerceFacade->createBackofficeAssistantConversationCollection(
            (new BackofficeAssistantConversationCollectionRequestTransfer())
                ->addBackofficeAssistantConversation(
                    (new BackofficeAssistantConversationTransfer())
                        ->setIdUser($idUser)
                        ->setName(mb_substr($this->promptContextFormatter->stripContext($prompt), 0, static::CONVERSATION_NAME_MAX_LENGTH)),
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

    protected function hasBackofficeAssistantConversationForUser(int $idUser, string $conversationReference): bool
    {
        $criteria = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions(
                (new BackofficeAssistantConversationConditionsTransfer())
                    ->addConversationReference($conversationReference)
                    ->addIdUser($idUser),
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

    protected function executeSelectedAgent(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
        string $selectedAgent,
        string $conversationReference,
    ): void {
        $agentPlugin = $this->findAgentPlugin($selectedAgent);

        if ($agentPlugin === null) {
            $this->getLogger()->error(sprintf(static::LOG_MESSAGE_AGENT_NOT_FOUND, $selectedAgent));
            $this->emitErrorMessage($this->translatorFacade->trans(static::MESSAGE_AI_SERVICE_UNAVAILABLE));

            return;
        }

        $agentRequest = (new BackofficeAssistantPromptRequestTransfer())
            ->setPrompt($promptRequestTransfer->getPrompt())
            ->setConversationReference($conversationReference)
            ->setAttachments(new ArrayObject($this->attachmentBuilder->buildAttachmentTransfers($promptRequestTransfer->getRawAttachments())));

        if (!$agentPlugin->isApplicable($agentRequest)) {
            $this->getLogger()->error(sprintf(static::LOG_MESSAGE_AGENT_NOT_APPLICABLE, $selectedAgent));
            $this->emitErrorMessage($this->translatorFacade->trans(static::MESSAGE_AI_SERVICE_UNAVAILABLE));

            return;
        }

        $backofficeAssistantPromptResponseTransfer = $agentPlugin->executeAgent($agentRequest);
        $message = $backofficeAssistantPromptResponseTransfer->getMessage();

        if ($message === null) {
            $this->getLogger()->error(sprintf(static::LOG_MESSAGE_AGENT_WITHOUT_MESSAGE, $selectedAgent));
            $this->emitErrorMessage($this->translatorFacade->trans(static::MESSAGE_AI_SERVICE_UNAVAILABLE));

            return;
        }

        if ($backofficeAssistantPromptResponseTransfer->getAgent() === static::AGENT_GUARDRAIL) {
            $this->emitGuardrailTakeover($backofficeAssistantPromptResponseTransfer);
        }

        $this->emitText($message);
    }

    protected function findAgentPlugin(string $agentName): ?BackofficeAssistantAgentPluginInterface
    {
        foreach ($this->agentPlugins as $agentPlugin) {
            if ($agentPlugin->getName() === $agentName) {
                return $agentPlugin;
            }
        }

        return null;
    }

    protected function emitGuardrailTakeover(
        BackofficeAssistantPromptResponseTransfer $backofficeAssistantPromptResponseTransfer
    ): void {
        $this->emitAgentChange(static::AGENT_GUARDRAIL);
        $this->emitReasoning((string)$backofficeAssistantPromptResponseTransfer->getReasoningMessage());
    }

    protected function startMessage(StreamMessageState $streamMessageState, string $conversationReference, ?string $agent): void
    {
        $messageMetadata = [StreamEventKey::CONVERSATION_REFERENCE => $conversationReference];

        if ($agent !== null && $agent !== '') {
            $messageMetadata[StreamEventKey::AGENT] = $agent;
        }

        $this->messageStreamer->startMessage($streamMessageState, $messageMetadata);
    }

    protected function emitAgentChange(string $agent): void
    {
        $this->messageStreamer->emitAgent($agent);
    }

    protected function emitReasoning(string $reasoningMessage): void
    {
        if ($reasoningMessage === '') {
            return;
        }

        $this->messageStreamer->emitContentBlock(StreamEventBlockType::Reasoning, $reasoningMessage);
    }

    protected function emitText(string $message): void
    {
        if ($message === '') {
            $this->getLogger()->error(static::LOG_MESSAGE_EMPTY_ANSWER);
            $this->emitErrorMessage($this->translatorFacade->trans(static::MESSAGE_AI_SERVICE_UNAVAILABLE));

            return;
        }

        $this->messageStreamer->emitContentBlock(StreamEventBlockType::Text, $message);
    }

    protected function emitErrorMessage(string $errorText): void
    {
        $this->messageStreamer->emitError($errorText);
    }
}
