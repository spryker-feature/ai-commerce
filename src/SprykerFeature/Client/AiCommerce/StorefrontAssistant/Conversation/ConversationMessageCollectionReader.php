<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation;

use ArrayObject;
use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductEnricherInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage\ConversationStorageInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\ConversationReferenceValidatorInterface;

class ConversationMessageCollectionReader implements ConversationMessageCollectionReaderInterface
{
    protected const string ERROR_MESSAGE_MISSING_OWNER = 'The Storefront Assistant requires an authenticated customer.';

    protected const string ERROR_MESSAGE_MISSING_CONVERSATION_REFERENCE = 'The Storefront Assistant requires a conversation reference.';

    protected const string ERROR_MESSAGE_INVALID_CONVERSATION_REFERENCE = 'The Storefront Assistant conversation reference is invalid.';

    protected const string ERROR_MESSAGE_CONVERSATION_NOT_FOUND = 'The requested conversation does not exist.';

    public function __construct(
        protected ConversationStorageInterface $conversationStorage,
        protected AiFoundationClientInterface $aiFoundationClient,
        protected ConversationMessageExtractorInterface $conversationMessageExtractor,
        protected ConversationReferenceDeriverInterface $conversationReferenceDeriver,
        protected ToolResultProductEnricherInterface $toolResultProductEnricher,
        protected ConversationReferenceValidatorInterface $conversationReferenceValidator,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    public function getConversationMessageCollection(
        StorefrontAssistantConversationMessageCollectionCriteriaTransfer $storefrontAssistantConversationMessageCollectionCriteriaTransfer
    ): StorefrontAssistantConversationMessageCollectionTransfer {
        $storefrontAssistantConversationMessageCollectionTransfer = (new StorefrontAssistantConversationMessageCollectionTransfer())
            ->setIsSuccessful(false);

        $storefrontAssistantConversationMessageConditionsTransfer = $storefrontAssistantConversationMessageCollectionCriteriaTransfer
            ->getStorefrontAssistantConversationMessageConditions();

        $customerReference = trim((string)$storefrontAssistantConversationMessageConditionsTransfer?->getCustomerReference());
        $conversationReference = trim((string)$storefrontAssistantConversationMessageConditionsTransfer?->getConversationReference());

        if ($customerReference === '') {
            return $storefrontAssistantConversationMessageCollectionTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_MISSING_OWNER),
            );
        }

        if ($conversationReference === '') {
            return $storefrontAssistantConversationMessageCollectionTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_MISSING_CONVERSATION_REFERENCE),
            );
        }

        if (!$this->conversationReferenceValidator->isValid($conversationReference)) {
            return $storefrontAssistantConversationMessageCollectionTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_INVALID_CONVERSATION_REFERENCE),
            );
        }

        $storefrontAssistantConversationMessageCollectionTransfer->setConversationReference($conversationReference);

        if (!$this->conversationStorage->hasConversation($customerReference, $conversationReference)) {
            return $storefrontAssistantConversationMessageCollectionTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_CONVERSATION_NOT_FOUND),
            );
        }

        $storefrontAssistantConversationMessageTransfers = $this->readMessages($customerReference, $conversationReference);

        return $storefrontAssistantConversationMessageCollectionTransfer
            ->setIsSuccessful(true)
            ->setMessages(new ArrayObject($this->enrichToolInvocations($storefrontAssistantConversationMessageTransfers)));
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer> $storefrontAssistantConversationMessageTransfers
     *
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer>
     */
    protected function enrichToolInvocations(array $storefrontAssistantConversationMessageTransfers): array
    {
        $customerFacingToolNames = $this->aiCommerceConfig->getStorefrontAssistantCustomerFacingToolNames();

        foreach ($storefrontAssistantConversationMessageTransfers as $storefrontAssistantConversationMessageTransfer) {
            $customerFacingToolInvocationTransfers = new ArrayObject();

            foreach ($storefrontAssistantConversationMessageTransfer->getToolInvocations() as $toolInvocationTransfer) {
                if (!in_array($toolInvocationTransfer->getName(), $customerFacingToolNames, true)) {
                    continue;
                }

                $toolInvocationTransfer->setResult(
                    $this->toolResultProductEnricher->enrich((string)$toolInvocationTransfer->getResult()),
                );

                $customerFacingToolInvocationTransfers->append($toolInvocationTransfer);
            }

            $storefrontAssistantConversationMessageTransfer->setToolInvocations($customerFacingToolInvocationTransfers);
        }

        return $storefrontAssistantConversationMessageTransfers;
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer>
     */
    protected function readMessages(string $customerReference, string $conversationReference): array
    {
        $derivedConversationReference = $this->conversationReferenceDeriver->deriveConversationReference(
            $customerReference,
            $conversationReference,
        );

        $conversationHistoryCollectionTransfer = $this->aiFoundationClient->getConversationHistoryCollection(
            (new ConversationHistoryCriteriaTransfer())->setConversationHistoryConditions(
                (new ConversationHistoryConditionsTransfer())->setConversationReferences([$derivedConversationReference]),
            ),
        );

        foreach ($conversationHistoryCollectionTransfer->getConversationHistories() as $conversationHistoryTransfer) {
            if ($conversationHistoryTransfer->getConversationReference() !== $derivedConversationReference) {
                continue;
            }

            return $this->conversationMessageExtractor->extractConversationMessagesFromConversationHistory(
                $conversationHistoryTransfer,
            );
        }

        return [];
    }
}
