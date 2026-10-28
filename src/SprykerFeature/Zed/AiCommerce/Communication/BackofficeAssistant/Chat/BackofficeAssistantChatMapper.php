<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use SprykerFeature\Shared\AiCommerce\Conversation\RegeneratedPromptMarker;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptContextFormatterInterface;

class BackofficeAssistantChatMapper implements BackofficeAssistantChatMapperInterface
{
    protected const string PAYLOAD_KEY_MESSAGE = 'message';

    protected const string PAYLOAD_KEY_CONVERSATION_REFERENCE = 'conversationReference';

    protected const string PAYLOAD_KEY_CONVERSATION_REFERENCES = 'conversationReferences';

    protected const string PAYLOAD_KEY_SELECTED_AGENT = 'selectedAgent';

    protected const string PAYLOAD_KEY_ATTACHMENTS = 'attachments';

    protected const string PAYLOAD_KEY_PAGE_CONTEXT = 'pageContext';

    protected const string PAYLOAD_KEY_FORM_CONTEXT = 'formContext';

    protected const string ATTACHMENT_KEY_CONTENT = 'content';

    protected const string ATTACHMENT_KEY_MEDIA_TYPE = 'mediaType';

    // IntentRouter reads the page the user is on only under this key.
    protected const string CONTEXT_KEY_CURRENT_PAGE = 'current_page';

    protected const string RESPONSE_KEY_CONVERSATIONS = 'conversations';

    protected const string RESPONSE_KEY_AVAILABLE_AGENTS = 'availableAgents';

    protected const string RESPONSE_KEY_USER_SELECTED_AGENT = 'userSelectedAgent';

    protected const string RESPONSE_KEY_CONVERSATION_REFERENCE = 'conversationReference';

    protected const string RESPONSE_KEY_NAME = 'name';

    protected const string RESPONSE_KEY_AGENT = 'agent';

    protected const string RESPONSE_KEY_UPDATED_AT = 'updatedAt';

    protected const string RESPONSE_KEY_MESSAGES = 'messages';

    protected const string RESPONSE_KEY_ROLE = 'role';

    protected const string RESPONSE_KEY_CONTENT = 'content';

    protected const string RESPONSE_KEY_ATTACHMENTS = 'attachments';

    protected const string RESPONSE_KEY_TOOL_INVOCATIONS = 'toolInvocations';

    protected const string RESPONSE_KEY_RESULT = 'result';

    protected const string MESSAGE_TYPE_USER = 'user';

    protected const string MESSAGE_TYPE_TOOL_CALL = 'tool_call';

    protected const string MESSAGE_TYPE_TOOL_RESULT = 'tool_result';

    protected const string ROLE_USER = 'user';

    protected const string ROLE_ASSISTANT = 'ai';

    protected const string ROLE_TOOL_RESULT = 'tool_result';

    public function __construct(
        protected PromptContextFormatterInterface $promptContextFormatter,
        protected RegeneratedPromptMarker $regeneratedPromptMarker,
    ) {
    }

    public function mapPayloadToBackofficeAssistantPromptRequestTransfer(
        array $payload,
        string $currentPage,
        int $idUser,
    ): BackofficeAssistantPromptRequestTransfer {
        $selectedAgent = $payload[static::PAYLOAD_KEY_SELECTED_AGENT] ?? null;
        $context = $currentPage === '' ? [] : [static::CONTEXT_KEY_CURRENT_PAGE => $currentPage];

        return (new BackofficeAssistantPromptRequestTransfer())
            ->setPrompt($this->promptContextFormatter->formatPrompt(
                (string)$payload[static::PAYLOAD_KEY_MESSAGE],
                $payload[static::PAYLOAD_KEY_PAGE_CONTEXT] ?? null,
                $payload[static::PAYLOAD_KEY_FORM_CONTEXT] ?? null,
                ($payload[RegeneratedPromptMarker::PAYLOAD_KEY_REGENERATE] ?? false) === true,
            ))
            ->setConversationReference((string)($payload[static::PAYLOAD_KEY_CONVERSATION_REFERENCE] ?? ''))
            ->setSelectedAgent(is_string($selectedAgent) ? $selectedAgent : '')
            ->setContext($context)
            ->setRawAttachments($this->mapAttachments($payload[static::PAYLOAD_KEY_ATTACHMENTS] ?? null))
            ->setIdUser($idUser);
    }

    public function mapPayloadToBackofficeAssistantConversationCollectionDeleteCriteriaTransfer(
        array $payload,
        int $idUser,
    ): BackofficeAssistantConversationCollectionDeleteCriteriaTransfer {
        return (new BackofficeAssistantConversationCollectionDeleteCriteriaTransfer())
            ->setConversationReferences(array_values(array_unique($payload[static::PAYLOAD_KEY_CONVERSATION_REFERENCES])))
            ->setIdUser($idUser);
    }

    public function mapBackofficeAssistantConversationCollectionToArray(
        BackofficeAssistantConversationCollectionTransfer $backofficeAssistantConversationCollectionTransfer,
        array $availableAgents,
    ): array {
        $conversations = [];

        foreach ($backofficeAssistantConversationCollectionTransfer->getBackofficeAssistantConversations() as $backofficeAssistantConversationTransfer) {
            $conversationReference = (string)$backofficeAssistantConversationTransfer->getConversationReference();
            $conversations[] = [
                static::RESPONSE_KEY_CONVERSATION_REFERENCE => $conversationReference,
                static::RESPONSE_KEY_NAME => $backofficeAssistantConversationTransfer->getName() ?: $conversationReference,
                static::RESPONSE_KEY_AGENT => $backofficeAssistantConversationTransfer->getAgent() ?: null,
                // The conversation table keeps no timestamp; 0 renders no time.
                static::RESPONSE_KEY_UPDATED_AT => 0,
            ];
        }

        return [
            static::RESPONSE_KEY_CONVERSATIONS => $conversations,
            static::RESPONSE_KEY_AVAILABLE_AGENTS => $availableAgents,
            static::RESPONSE_KEY_USER_SELECTED_AGENT => null,
        ];
    }

    public function mapBackofficeAssistantConversationToMessagesArray(
        BackofficeAssistantConversationTransfer $backofficeAssistantConversationTransfer,
    ): array {
        $messages = [];
        $promptMessageTransfers = $this->regeneratedPromptMarker->removeReplacedExchanges(
            array_values($backofficeAssistantConversationTransfer->getMessages()->getArrayCopy()),
            fn (PromptMessageTransfer $promptMessageTransfer): ?string => $promptMessageTransfer->getType() === static::MESSAGE_TYPE_USER
                ? $this->promptContextFormatter->stripContext((string)$promptMessageTransfer->getContent())
                : null,
        );

        foreach ($promptMessageTransfers as $promptMessageTransfer) {
            $message = $this->mapPromptMessage($promptMessageTransfer);

            if ($message !== null) {
                $messages[] = $message;
            }
        }

        return [static::RESPONSE_KEY_MESSAGES => $messages];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function mapAttachments(mixed $attachments): array
    {
        if (!is_array($attachments)) {
            return [];
        }

        $rawAttachments = [];

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $rawAttachments[] = [
                static::ATTACHMENT_KEY_CONTENT => $attachment[static::ATTACHMENT_KEY_CONTENT] ?? null,
                static::ATTACHMENT_KEY_MEDIA_TYPE => $attachment[static::ATTACHMENT_KEY_MEDIA_TYPE] ?? null,
            ];
        }

        return $rawAttachments;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function mapPromptMessage(PromptMessageTransfer $promptMessageTransfer): ?array
    {
        $content = (string)$promptMessageTransfer->getContent();

        return match ($promptMessageTransfer->getType()) {
            static::MESSAGE_TYPE_TOOL_CALL => null,
            static::MESSAGE_TYPE_USER => [
                static::RESPONSE_KEY_ROLE => static::ROLE_USER,
                static::RESPONSE_KEY_CONTENT => $this->regeneratedPromptMarker->removeMarker($this->promptContextFormatter->stripContext($content)),
                static::RESPONSE_KEY_ATTACHMENTS => $this->mapStoredAttachments($promptMessageTransfer),
            ],
            static::MESSAGE_TYPE_TOOL_RESULT => [
                static::RESPONSE_KEY_ROLE => static::ROLE_TOOL_RESULT,
                static::RESPONSE_KEY_CONTENT => $content,
                static::RESPONSE_KEY_TOOL_INVOCATIONS => $this->mapToolInvocations($promptMessageTransfer, $content),
            ],
            default => $content === '' ? null : [
                static::RESPONSE_KEY_ROLE => static::ROLE_ASSISTANT,
                static::RESPONSE_KEY_CONTENT => $content,
            ],
        };
    }

    /**
     * @return list<array<string, string>>
     */
    protected function mapStoredAttachments(PromptMessageTransfer $promptMessageTransfer): array
    {
        $attachments = [];

        foreach ($promptMessageTransfer->getAttachments() as $attachmentTransfer) {
            $filename = (string)$attachmentTransfer->getFilename();

            if ($filename !== '') {
                $attachments[] = [static::RESPONSE_KEY_NAME => $filename];
            }
        }

        return $attachments;
    }

    /**
     * @return list<array<string, string>>
     */
    protected function mapToolInvocations(PromptMessageTransfer $promptMessageTransfer, string $content): array
    {
        $toolInvocations = [];

        foreach ($promptMessageTransfer->getToolInvocations() as $toolInvocationTransfer) {
            $toolInvocations[] = [
                static::RESPONSE_KEY_NAME => (string)$toolInvocationTransfer->getName(),
                static::RESPONSE_KEY_RESULT => (string)$toolInvocationTransfer->getResult(),
            ];
        }

        // An empty name shows the generic tool-result label.
        return $toolInvocations !== [] ? $toolInvocations : [[static::RESPONSE_KEY_NAME => '', static::RESPONSE_KEY_RESULT => $content]];
    }
}
