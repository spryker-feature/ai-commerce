<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;
use SprykerFeature\Service\AiCommerce\AiCommerceServiceInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;

class BackofficeAssistantConversationCreator implements BackofficeAssistantConversationCreatorInterface
{
    public function __construct(
        protected AiCommerceEntityManagerInterface $entityManager,
        protected AiCommerceServiceInterface $aiCommerceService,
    ) {
    }

    public function createCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        $responseTransfer = new BackofficeAssistantConversationCollectionResponseTransfer();

        foreach ($collectionRequestTransfer->getBackofficeAssistantConversations() as $conversationTransfer) {
            $conversationReference = $this->aiCommerceService->generateConversationReference(
                (string)$conversationTransfer->getUserUuid(),
            );

            $conversationTransfer->setConversationReference($conversationReference);

            $createdTransfer = $this->entityManager->createBackofficeAssistantConversation($conversationTransfer);

            $responseTransfer->addBackofficeAssistantConversation($createdTransfer);
        }

        return $responseTransfer;
    }
}
