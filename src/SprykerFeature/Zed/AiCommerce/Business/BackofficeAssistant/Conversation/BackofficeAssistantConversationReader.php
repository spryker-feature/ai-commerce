<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation;

use ArrayObject;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryCollectionTransfer;
use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class BackofficeAssistantConversationReader implements BackofficeAssistantConversationReaderInterface
{
    protected const string KEY_MESSAGE = 'message';

    public function __construct(
        protected AiCommerceRepositoryInterface $repository,
        protected AiFoundationFacadeInterface $aiFoundationFacade,
    ) {
    }

    public function getCollection(
        BackofficeAssistantConversationCriteriaTransfer $criteriaTransfer,
    ): BackofficeAssistantConversationCollectionTransfer {
        $collectionTransfer = $this->repository->getBackofficeAssistantConversationCollection($criteriaTransfer);

        $conditions = $criteriaTransfer->getBackofficeAssistantConversationConditions();

        if ($conditions && $conditions->getWithMessages()) {
            $this->populateMessages($collectionTransfer);
        }

        return $collectionTransfer;
    }

    protected function populateMessages(BackofficeAssistantConversationCollectionTransfer $collectionTransfer): void
    {
        $conversations = $collectionTransfer->getBackofficeAssistantConversations();

        if ($conversations->count() === 0) {
            return;
        }

        $conditions = $this->buildConversationHistoryConditions($conversations);
        $criteria = (new ConversationHistoryCriteriaTransfer())->setConversationHistoryConditions($conditions);

        $historiesIndexedByReference = $this->indexHistoriesByReference(
            $this->aiFoundationFacade->getConversationHistoryCollection($criteria),
        );

        foreach ($conversations as $conversation) {
            $history = $historiesIndexedByReference[(string)$conversation->getConversationReference()] ?? null;

            if ($history === null) {
                continue;
            }

            $conversation->setMessages($this->filterMessages($history->getMessages()));
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\BackofficeAssistantConversationTransfer> $conversations
     */
    protected function buildConversationHistoryConditions(ArrayObject $conversations): ConversationHistoryConditionsTransfer
    {
        $conditions = new ConversationHistoryConditionsTransfer();

        foreach ($conversations as $conversation) {
            $conditions->addConversationReference($conversation->getConversationReferenceOrFail());
        }

        return $conditions;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\ConversationHistoryTransfer>
     */
    protected function indexHistoriesByReference(ConversationHistoryCollectionTransfer $historyCollection): array
    {
        $historiesIndexedByReference = [];

        foreach ($historyCollection->getConversationHistories() as $history) {
            $historiesIndexedByReference[$history->getConversationReferenceOrFail()] = $history;
        }

        return $historiesIndexedByReference;
    }

    protected function extractStructuredContent(PromptMessageTransfer $message): void
    {
        if ($message->getType() !== AiFoundationConstants::MESSAGE_TYPE_ASSISTANT) {
            return;
        }

        $content = $message->getContent();

        if ($content === null || $content === '') {
            return;
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !isset($decoded[static::KEY_MESSAGE])) {
            return;
        }

        $message->setContent((string)$decoded[static::KEY_MESSAGE]);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PromptMessageTransfer> $messages
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\PromptMessageTransfer>
     */
    protected function filterMessages(ArrayObject $messages): ArrayObject
    {
        $filtered = new ArrayObject();

        foreach ($messages as $message) {
            $this->extractStructuredContent($message);

            $content = $message->getContent();
            $hasToolInvocations = $message->getToolInvocations()->count() > 0;

            if (($content === null || $content === '') && !$hasToolInvocations) {
                continue;
            }

            $filtered->append($message);
        }

        return $filtered;
    }
}
