<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

use ArrayObject;
use Generated\Shared\Transfer\ConversationHistoryCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryCollectionResponseTransfer;
use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationDeleteConditionsTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Exception\StorefrontAssistantConversationPersistenceException;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage\ConversationStorageInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\ConversationReferenceValidatorInterface;

class ConversationCollectionDeleter implements ConversationCollectionDeleterInterface
{
    protected const string ERROR_MESSAGE_MISSING_OWNER = 'The Storefront Assistant requires an authenticated customer.';

    protected const string ERROR_MESSAGE_INVALID_CONVERSATION_REFERENCE = 'The Storefront Assistant conversation reference is invalid.';

    protected const string ERROR_MESSAGE_DELETE_FAILED = 'The Storefront Assistant conversations could not be deleted.';

    public function __construct(
        protected ConversationStorageInterface $conversationStorage,
        protected AiFoundationClientInterface $aiFoundationClient,
        protected ConversationReferenceDeriverInterface $conversationReferenceDeriver,
        protected ConversationReferenceValidatorInterface $conversationReferenceValidator
    ) {
    }

    public function deleteConversationCollection(
        StorefrontAssistantConversationCollectionDeleteCriteriaTransfer $storefrontAssistantConversationCollectionDeleteCriteriaTransfer
    ): StorefrontAssistantConversationCollectionResponseTransfer {
        $storefrontAssistantConversationCollectionResponseTransfer = (new StorefrontAssistantConversationCollectionResponseTransfer())
            ->setIsSuccessful(false)
            ->setDeletedCount(0);

        $storefrontAssistantConversationDeleteConditionsTransfer = $storefrontAssistantConversationCollectionDeleteCriteriaTransfer
            ->getStorefrontAssistantConversationDeleteConditions();

        $customerReference = trim((string)$storefrontAssistantConversationDeleteConditionsTransfer?->getCustomerReference());

        if ($customerReference === '') {
            return $storefrontAssistantConversationCollectionResponseTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_MISSING_OWNER),
            );
        }

        $conversationReferences = $this->resolveConversationReferences($storefrontAssistantConversationDeleteConditionsTransfer);

        if ($conversationReferences === []) {
            return $storefrontAssistantConversationCollectionResponseTransfer->setIsSuccessful(true);
        }

        if (!$this->areConversationReferencesValid($conversationReferences)) {
            return $storefrontAssistantConversationCollectionResponseTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_INVALID_CONVERSATION_REFERENCE),
            );
        }

        $conversationHistoryCollectionResponseTransfer = $this->deletePersistedChatHistory($customerReference, $conversationReferences);

        if (!$conversationHistoryCollectionResponseTransfer->getIsSuccessful()) {
            return $this->addErrors(
                $storefrontAssistantConversationCollectionResponseTransfer,
                $conversationHistoryCollectionResponseTransfer->getErrors(),
            );
        }

        $deletedCount = 0;

        try {
            foreach ($conversationReferences as $conversationReference) {
                if ($this->conversationStorage->deleteConversation($customerReference, $conversationReference)) {
                    $deletedCount++;
                }
            }
        } catch (StorefrontAssistantConversationPersistenceException) {
            return $storefrontAssistantConversationCollectionResponseTransfer
                ->setDeletedCount($deletedCount)
                ->addError((new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_DELETE_FAILED));
        }

        return $storefrontAssistantConversationCollectionResponseTransfer
            ->setIsSuccessful(true)
            ->setDeletedCount($deletedCount);
    }

    /**
     * @param array<int, string> $conversationReferences
     */
    protected function areConversationReferencesValid(array $conversationReferences): bool
    {
        foreach ($conversationReferences as $conversationReference) {
            if (!$this->conversationReferenceValidator->isValid($conversationReference)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $conversationReferences
     */
    protected function deletePersistedChatHistory(
        string $customerReference,
        array $conversationReferences
    ): ConversationHistoryCollectionResponseTransfer {
        $derivedConversationReferences = array_map(
            fn (string $conversationReference): string => $this->conversationReferenceDeriver
                ->deriveConversationReference($customerReference, $conversationReference),
            $conversationReferences,
        );

        return $this->aiFoundationClient->deleteConversationHistoryCollection(
            (new ConversationHistoryCollectionDeleteCriteriaTransfer())->setConversationHistoryConditions(
                (new ConversationHistoryConditionsTransfer())->setConversationReferences($derivedConversationReferences),
            ),
        );
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     */
    protected function addErrors(
        StorefrontAssistantConversationCollectionResponseTransfer $storefrontAssistantConversationCollectionResponseTransfer,
        ArrayObject $errorTransfers
    ): StorefrontAssistantConversationCollectionResponseTransfer {
        if ($errorTransfers->count() === 0) {
            return $storefrontAssistantConversationCollectionResponseTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_DELETE_FAILED),
            );
        }

        foreach ($errorTransfers as $errorTransfer) {
            $storefrontAssistantConversationCollectionResponseTransfer->addError($errorTransfer);
        }

        return $storefrontAssistantConversationCollectionResponseTransfer;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveConversationReferences(
        ?StorefrontAssistantConversationDeleteConditionsTransfer $storefrontAssistantConversationDeleteConditionsTransfer
    ): array {
        $conversationReferences = $storefrontAssistantConversationDeleteConditionsTransfer?->getConversationReferences() ?? [];

        $conversationReferences = array_filter(
            $conversationReferences,
            static fn (mixed $conversationReference): bool => is_string($conversationReference) && trim($conversationReference) !== '',
        );

        return array_values(array_unique($conversationReferences));
    }
}
