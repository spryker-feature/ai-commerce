<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant;

use Generated\Shared\Transfer\ConversationHistoryCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryCollectionResponseTransfer;
use Generated\Shared\Transfer\ConversationHistoryCollectionTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;

class RecordingTranscriptAiFoundationClient implements AiFoundationClientInterface
{
    protected const string KEY_ROLE = 'role';

    protected const string KEY_CONTENT = 'content';

    protected const string ROLE_USER = 'user';

    protected const string ROLE_ASSISTANT = 'ai';

    protected const string ERROR_MESSAGE_HISTORY_DELETE_FAILED = 'The conversation history could not be deleted.';

    /**
     * @var array<string, array<int, array<string, string>>>
     */
    protected array $transcripts = [];

    /**
     * @var array<int, string>
     */
    protected array $promptedConversationReferences = [];

    /**
     * @var array<int, string>
     */
    protected array $readConversationReferences = [];

    /**
     * @var array<int, string>
     */
    protected array $deletedConversationReferences = [];

    /**
     * @var list<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    protected array $promptedAttachments = [];

    protected bool $isConversationHistoryDeletionFailing = false;

    public function __construct(protected string $assistantMessage = '')
    {
    }

    public function streamPrompt(PromptRequestTransfer $promptRequestTransfer): PromptResponseTransfer
    {
        $conversationReference = (string)$promptRequestTransfer->getConversationReference();

        $this->promptedConversationReferences[] = $conversationReference;

        foreach ($promptRequestTransfer->getPromptMessage()?->getAttachments() ?? [] as $attachmentTransfer) {
            $this->promptedAttachments[] = $attachmentTransfer;
        }

        $loadedTranscript = $this->transcripts[$conversationReference] ?? [];

        $this->transcripts[$conversationReference] = [
            ...$loadedTranscript,
            [
                static::KEY_ROLE => static::ROLE_USER,
                static::KEY_CONTENT => (string)$promptRequestTransfer->getPromptMessage()?->getContent(),
            ],
            [
                static::KEY_ROLE => static::ROLE_ASSISTANT,
                static::KEY_CONTENT => $this->assistantMessage,
            ],
        ];

        return (new PromptResponseTransfer())
            ->setIsSuccessful(true)
            ->setStreamedContent($this->assistantMessage)
            ->setMessage(
                (new PromptMessageTransfer())->setType('assistant')->setContent($this->assistantMessage),
            );
    }

    public function getConversationHistoryCollection(
        ConversationHistoryCriteriaTransfer $conversationHistoryCriteriaTransfer
    ): ConversationHistoryCollectionTransfer {
        $conversationHistoryCollectionTransfer = new ConversationHistoryCollectionTransfer();

        $conversationReferences = $conversationHistoryCriteriaTransfer
            ->getConversationHistoryConditions()
            ?->getConversationReferences() ?? [];

        foreach ($conversationReferences as $conversationReference) {
            $this->readConversationReferences[] = $conversationReference;

            if (!isset($this->transcripts[$conversationReference])) {
                continue;
            }

            $conversationHistoryCollectionTransfer->addConversationHistory(
                (new ConversationHistoryTransfer())
                    ->setConversationReference($conversationReference)
                    ->setOriginalMessages((string)json_encode($this->transcripts[$conversationReference])),
            );
        }

        return $conversationHistoryCollectionTransfer;
    }

    public function deleteConversationHistoryCollection(
        ConversationHistoryCollectionDeleteCriteriaTransfer $conversationHistoryCollectionDeleteCriteriaTransfer
    ): ConversationHistoryCollectionResponseTransfer {
        if ($this->isConversationHistoryDeletionFailing) {
            return (new ConversationHistoryCollectionResponseTransfer())
                ->setIsSuccessful(false)
                ->addError((new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_HISTORY_DELETE_FAILED));
        }

        foreach ($conversationHistoryCollectionDeleteCriteriaTransfer->getConversationHistoryConditionsOrFail()->getConversationReferences() as $conversationReference) {
            $this->deletedConversationReferences[] = $conversationReference;

            unset($this->transcripts[$conversationReference]);
        }

        return (new ConversationHistoryCollectionResponseTransfer())->setIsSuccessful(true);
    }

    public function failConversationHistoryDeletion(): void
    {
        $this->isConversationHistoryDeletionFailing = true;
    }

    public function clearTranscripts(): void
    {
        $this->transcripts = [];
    }

    public function prompt(PromptRequestTransfer $promptRequest): PromptResponseTransfer
    {
        return $this->streamPrompt($promptRequest);
    }

    /**
     * @return array<int, string>
     */
    public function getPromptedConversationReferences(): array
    {
        return $this->promptedConversationReferences;
    }

    /**
     * @return array<int, string>
     */
    public function getReadConversationReferences(): array
    {
        return $this->readConversationReferences;
    }

    /**
     * @return array<int, string>
     */
    public function getDeletedConversationReferences(): array
    {
        return $this->deletedConversationReferences;
    }

    /**
     * @return array<int, string>
     */
    public function getStoredConversationReferences(): array
    {
        return array_keys($this->transcripts);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getTranscript(string $conversationReference): array
    {
        return $this->transcripts[$conversationReference] ?? [];
    }

    /**
     * @return list<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    public function getPromptedAttachments(): array
    {
        return $this->promptedAttachments;
    }
}
