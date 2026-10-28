<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class BackofficeAssistantConversationDeleter implements BackofficeAssistantConversationDeleterInterface
{
    use TransactionTrait;

    protected const string ERROR_MESSAGE_CONVERSATION_NOT_FOUND = 'backoffice_assistant.error.conversation_not_found';

    public function __construct(
        protected AiCommerceEntityManagerInterface $entityManager,
        protected AiCommerceRepositoryInterface $repository,
    ) {
    }

    public function deleteCollection(
        BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        $responseTransfer = new BackofficeAssistantConversationCollectionResponseTransfer();

        if (!$this->isOwnedByUser($deleteCriteriaTransfer)) {
            return $responseTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_CONVERSATION_NOT_FOUND),
            );
        }

        return $this->getTransactionHandler()->handleTransaction(
            fn (): BackofficeAssistantConversationCollectionResponseTransfer => $this->executeDeleteCollectionTransaction($deleteCriteriaTransfer, $responseTransfer),
        );
    }

    protected function executeDeleteCollectionTransaction(
        BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer,
        BackofficeAssistantConversationCollectionResponseTransfer $responseTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        foreach ($deleteCriteriaTransfer->getConversationReferences() as $conversationReference) {
            $this->entityManager->deleteBackofficeAssistantConversation(
                (new BackofficeAssistantConversationTransfer())
                    ->setConversationReference($conversationReference),
            );
        }

        return $responseTransfer;
    }

    protected function isOwnedByUser(BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer): bool
    {
        $idUser = $deleteCriteriaTransfer->getIdUser();
        $conversationReferences = $deleteCriteriaTransfer->getConversationReferences();

        if ($idUser === null || $conversationReferences === []) {
            return true;
        }

        $backofficeAssistantConversationCollectionTransfer = $this->repository->getBackofficeAssistantConversationCollection(
            (new BackofficeAssistantConversationCriteriaTransfer())->setBackofficeAssistantConversationConditions(
                (new BackofficeAssistantConversationConditionsTransfer())
                    ->addIdUser($idUser)
                    ->setConversationReferences($conversationReferences),
            ),
        );

        $ownedConversationReferences = [];

        foreach ($backofficeAssistantConversationCollectionTransfer->getBackofficeAssistantConversations() as $backofficeAssistantConversationTransfer) {
            $ownedConversationReferences[] = $backofficeAssistantConversationTransfer->getConversationReference();
        }

        return array_diff($conversationReferences, $ownedConversationReferences) === [];
    }
}
