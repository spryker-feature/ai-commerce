<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;

class BackofficeAssistantConversationDeleter implements BackofficeAssistantConversationDeleterInterface
{
    public function __construct(
        protected AiCommerceEntityManagerInterface $entityManager,
    ) {
    }

    public function deleteCollection(
        BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        $responseTransfer = new BackofficeAssistantConversationCollectionResponseTransfer();

        foreach ($deleteCriteriaTransfer->getConversationReferences() as $conversationReference) {
            $this->entityManager->deleteBackofficeAssistantConversation(
                (new BackofficeAssistantConversationTransfer())
                    ->setConversationReference($conversationReference),
            );
        }

        return $responseTransfer;
    }
}
