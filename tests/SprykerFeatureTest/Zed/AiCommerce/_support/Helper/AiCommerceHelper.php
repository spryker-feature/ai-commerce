<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class AiCommerceHelper extends Module
{
    use DataCleanupHelperTrait;
    use LocatorHelperTrait;

    /**
     * @param array<string, mixed> $seed
     */
    public function haveConversation(array $seed = []): BackofficeAssistantConversationTransfer
    {
        $conversationTransfer = (new BackofficeAssistantConversationTransfer())
            ->fromArray(array_merge($this->getDefaultConversationSeed(), $seed));

        $collectionRequestTransfer = (new BackofficeAssistantConversationCollectionRequestTransfer())
            ->addBackofficeAssistantConversation($conversationTransfer);

        $responseTransfer = $this->getLocator()
            ->aiCommerce()
            ->facade()
            ->createBackofficeAssistantConversationCollection($collectionRequestTransfer);

        $createdConversation = $responseTransfer->getBackofficeAssistantConversations()->getIterator()->current();

        $this->getDataCleanupHelper()->addCleanup(function () use ($createdConversation): void {
            $this->deleteConversation($createdConversation->getConversationReferenceOrFail());
        });

        return $createdConversation;
    }

    protected function deleteConversation(string $conversationReference): void
    {
        $deleteCriteriaTransfer = (new BackofficeAssistantConversationCollectionDeleteCriteriaTransfer())
            ->addConversationReference($conversationReference);

        $this->getLocator()
            ->aiCommerce()
            ->facade()
            ->deleteBackofficeAssistantConversationCollection($deleteCriteriaTransfer);
    }

    /**
     * @return array<string, string>
     */
    protected function getDefaultConversationSeed(): array
    {
        return [
            BackofficeAssistantConversationTransfer::USER_UUID => uniqid('user-uuid-', true),
            BackofficeAssistantConversationTransfer::NAME => uniqid('Test Conversation ', true),
        ];
    }
}
