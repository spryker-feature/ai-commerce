<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

use ArrayObject;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationTransfer;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage\ConversationStorageInterface;

class ConversationCollectionReader implements ConversationCollectionReaderInterface
{
    public function __construct(
        protected ConversationStorageInterface $conversationStorage,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    public function getConversationCollection(
        StorefrontAssistantConversationCollectionCriteriaTransfer $storefrontAssistantConversationCollectionCriteriaTransfer
    ): StorefrontAssistantConversationCollectionTransfer {
        $storefrontAssistantConversationCollectionTransfer = new StorefrontAssistantConversationCollectionTransfer();

        $storefrontAssistantConversationConditionsTransfer = $storefrontAssistantConversationCollectionCriteriaTransfer
            ->getStorefrontAssistantConversationConditions();

        $customerReference = trim((string)$storefrontAssistantConversationConditionsTransfer?->getCustomerReference());

        if ($customerReference === '') {
            return $storefrontAssistantConversationCollectionTransfer;
        }

        $limit = $this->resolveLimit($storefrontAssistantConversationConditionsTransfer);
        $conversationReferenceFilter = $this->resolveConversationReferenceFilter($storefrontAssistantConversationConditionsTransfer);

        $storefrontAssistantConversationTransfers = $conversationReferenceFilter === []
            ? $this->conversationStorage->getConversations($customerReference, $limit)
            : $this->findConversations($customerReference, $conversationReferenceFilter, $limit);

        foreach ($storefrontAssistantConversationTransfers as $storefrontAssistantConversationTransfer) {
            $storefrontAssistantConversationCollectionTransfer->addStorefrontAssistantConversation(
                $this->prepareConversation($storefrontAssistantConversationTransfer),
            );
        }

        return $storefrontAssistantConversationCollectionTransfer;
    }

    /**
     * @param array<int, string> $conversationReferences
     *
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationTransfer>
     */
    protected function findConversations(string $customerReference, array $conversationReferences, int $limit): array
    {
        $storefrontAssistantConversationTransfers = [];

        foreach ($conversationReferences as $conversationReference) {
            if (trim($conversationReference) === '') {
                continue;
            }

            $storefrontAssistantConversationTransfer = $this->conversationStorage->findConversation($customerReference, $conversationReference);

            if ($storefrontAssistantConversationTransfer !== null) {
                $storefrontAssistantConversationTransfers[] = $storefrontAssistantConversationTransfer;
            }
        }

        usort(
            $storefrontAssistantConversationTransfers,
            static fn (
                StorefrontAssistantConversationTransfer $left,
                StorefrontAssistantConversationTransfer $right
            ): int => ($right->getUpdatedAt() ?? 0) <=> ($left->getUpdatedAt() ?? 0),
        );

        return array_slice($storefrontAssistantConversationTransfers, 0, $limit);
    }

    protected function prepareConversation(
        StorefrontAssistantConversationTransfer $storefrontAssistantConversationTransfer
    ): StorefrontAssistantConversationTransfer {
        if (trim((string)$storefrontAssistantConversationTransfer->getName()) === '') {
            $storefrontAssistantConversationTransfer->setName(
                (string)$storefrontAssistantConversationTransfer->getConversationReference(),
            );
        }

        $storefrontAssistantConversationTransfer->setMessages(new ArrayObject());

        return $storefrontAssistantConversationTransfer;
    }

    protected function resolveLimit(?StorefrontAssistantConversationConditionsTransfer $storefrontAssistantConversationConditionsTransfer): int
    {
        $limit = $storefrontAssistantConversationConditionsTransfer?->getLimit();

        if ($limit !== null && $limit > 0) {
            return $limit;
        }

        return $this->aiCommerceConfig->getStorefrontAssistantConversationListLimit();
    }

    /**
     * @return array<int, string>
     */
    protected function resolveConversationReferenceFilter(
        ?StorefrontAssistantConversationConditionsTransfer $storefrontAssistantConversationConditionsTransfer
    ): array {
        $conversationReferences = $storefrontAssistantConversationConditionsTransfer?->getConversationReferences() ?? [];

        return array_values(array_unique(array_filter($conversationReferences, 'is_string')));
    }
}
