<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt;

use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Generated\Shared\Transfer\IntentRouterResponseTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

class IntentRouter implements IntentRouterInterface
{
    protected const string AGENT_GUARDRAIL = 'Guardrail';

    protected const string CONTEXT_KEY_CURRENT_PAGE = 'current_page';

    protected const string DEFAULT_CONTEXT_VALUE = 'unknown';

    protected const int CONVERSATION_HISTORY_LIMIT = 12;

    protected const string INTENT_ROUTER_PROMPT_TEMPLATE = '
# You are Spryker Backoffice Assistant — an intent router that selects the appropriate agent to handle user requests.

## User Context
- Current page: %s

## Available Agents
%s

## Current Agent previously selected
%s

## Instructions
1. Analyze the user\'s latest message in the context of the conversation history below.
2. Select the appropriate agent based on the user\'s intent. The intent must be very close to agent\'s responsibilities to be considered a match.
3. If the user\'s intent does not match any agent\'s responsibilities (e.g., off-topic, personal questions, coding help, non-Spryker topics), select "Guardrail" as the agent and write a brief, friendly clarification in reasoningMessage explaining what you can help with.
4. For follow-up messages, consider the conversation context and previously discussed topics to maintain continuity.

## Restrictions
- Never say about your purposes and that you are intent router

## Response Format
- agent: Exactly one of %s
- reasoningMessage: One sentence explaining your routing decision, or a clarification message if Guardrail is selected

## Conversation History
%s';

    /**
     * @param array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface> $agentPlugins
     */
    public function __construct(
        protected AiFoundationFacadeInterface $aiFoundationFacade,
        protected array $agentPlugins,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function route(
        ConversationHistoryTransfer $conversationHistory,
        string $prompt,
        ?string $previousAgent,
        array $context,
    ): ?IntentRouterResponseTransfer {
        $promptContent = $this->buildPrompt($conversationHistory, $prompt, $previousAgent, $context);

        return $this->sendPrompt($promptContent);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function buildPrompt(
        ConversationHistoryTransfer $conversationHistory,
        string $prompt,
        ?string $previousAgent,
        array $context,
    ): string {
        $conversationHistoryCopy = (new ConversationHistoryTransfer())
            ->fromArray($conversationHistory->toArray());

        $conversationHistoryCopy->addMessage(
            (new PromptMessageTransfer())
                ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                ->setContent($prompt),
        );

        $messages = array_slice($conversationHistoryCopy->getMessages()->getArrayCopy(), -static::CONVERSATION_HISTORY_LIMIT);

        $historyLines = [];

        foreach ($messages as $message) {
            $historyLines[] = sprintf('%s: %s', ucfirst((string)$message->getType()), $message->getContent());
        }

        $previousAgentContext = $previousAgent
            ? sprintf('Previously selected agent: "%s". Consider whether the user\'s new message changes the intent or continues with the same agent.', $previousAgent)
            : 'No agent has been selected yet (new conversation).';

        $agentLines = [];
        $agentNames = [];

        foreach ($this->agentPlugins as $agentPlugin) {
            $agentLines[] = sprintf('- "%s": %s', $agentPlugin->getName(), $agentPlugin->getDescription());
            $agentNames[] = sprintf('"%s"', $agentPlugin->getName());
        }

        $agentNames[] = sprintf('"%s"', static::AGENT_GUARDRAIL);

        return sprintf(
            static::INTENT_ROUTER_PROMPT_TEMPLATE,
            $context[static::CONTEXT_KEY_CURRENT_PAGE] ?? static::DEFAULT_CONTEXT_VALUE,
            implode("\n", $agentLines),
            $previousAgentContext,
            implode(', ', $agentNames),
            implode("\n", $historyLines),
        );
    }

    protected function sendPrompt(string $promptContent): ?IntentRouterResponseTransfer
    {
        $intentRouterRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName(AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER)
            ->setStructuredMessage(new IntentRouterResponseTransfer())
            ->setMaxRetries(2)
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($promptContent),
            );

        $intentResponse = $this->aiFoundationFacade->prompt($intentRouterRequest);

        if (!$intentResponse->getIsSuccessful()) {
            return null;
        }

        /** @var \Generated\Shared\Transfer\IntentRouterResponseTransfer */
        return $intentResponse->getStructuredMessage();
    }
}
