<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage;

use ArrayObject;
use Generated\Shared\Transfer\StorefrontAssistantConversationTransfer;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Exception\StorefrontAssistantConversationPersistenceException;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Exception\StorefrontAssistantConversationScopeException;

class ConversationStorage implements ConversationStorageInterface
{
    use LoggerTrait;

    protected const string KEY_PATTERN_CONVERSATION = 'ai_commerce:storefront_assistant:conversation:%s:%s';

    protected const string KEY_PATTERN_INDEX = 'ai_commerce:storefront_assistant:index:%s';

    protected const string ERROR_MESSAGE_BLANK_CUSTOMER_REFERENCE = 'A storefront assistant conversation cannot be addressed without a customer reference.';

    protected const string ERROR_MESSAGE_BLANK_CONVERSATION_REFERENCE = 'A storefront assistant conversation cannot be addressed without a conversation reference.';

    protected const string LOG_MESSAGE_CONVERSATION_ENCODING_FAILED = 'Storefront Assistant conversation "%s" of customer "%s" was not persisted: %s';

    protected const string LOG_MESSAGE_INDEX_ENCODING_FAILED = 'Storefront Assistant conversation index of customer "%s" was not persisted: %s';

    protected const int ENCODING_FLAGS = JSON_INVALID_UTF8_SUBSTITUTE;

    public function __construct(
        protected StorageClientInterface $storageClient,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    public function findConversation(
        string $customerReference,
        string $conversationReference
    ): ?StorefrontAssistantConversationTransfer {
        $key = $this->buildConversationKey($customerReference, $conversationReference);

        $decodedConversation = $this->decodeStoredConversation($this->storageClient->get($key));

        if ($decodedConversation === null) {
            return null;
        }

        return (new StorefrontAssistantConversationTransfer())->fromArray($decodedConversation, true);
    }

    public function saveConversation(
        string $customerReference,
        string $conversationReference,
        StorefrontAssistantConversationTransfer $storefrontAssistantConversationTransfer
    ): void {
        $key = $this->buildConversationKey($customerReference, $conversationReference);

        $storefrontAssistantConversationTransfer->setMessages(new ArrayObject());

        $encodedConversation = json_encode($storefrontAssistantConversationTransfer->toArray(true, true), static::ENCODING_FLAGS);

        if ($encodedConversation === false) {
            $message = sprintf(
                static::LOG_MESSAGE_CONVERSATION_ENCODING_FAILED,
                $conversationReference,
                $customerReference,
                json_last_error_msg(),
            );

            $this->getLogger()->error($message);

            throw new StorefrontAssistantConversationPersistenceException($message);
        }

        $timeToLive = $this->aiCommerceConfig->getStorefrontAssistantConversationTimeToLive();

        $this->storageClient->set($key, $encodedConversation, $timeToLive);
        $this->saveIndex($customerReference, $conversationReference, $timeToLive);
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationTransfer>
     */
    public function getConversations(string $customerReference, int $limit): array
    {
        $conversationReferences = $this->readIndex($customerReference);

        if ($conversationReferences === []) {
            return [];
        }

        $conversationKeys = array_map(
            fn (string $conversationReference): string => $this->buildConversationKey($customerReference, $conversationReference),
            $conversationReferences,
        );

        // getMulti() re-keys its result by the storage-internal prefixed key, so the returned array is
        // consumed positionally (it preserves the input order) rather than by our own key.
        $storedConversations = array_values($this->storageClient->getMulti($conversationKeys));

        $storefrontAssistantConversationTransfers = [];
        $liveConversationReferences = [];

        foreach ($storedConversations as $position => $storedConversation) {
            $decodedConversation = $this->decodeStoredConversation($storedConversation);

            if ($decodedConversation === null) {
                continue;
            }

            $liveConversationReferences[] = $conversationReferences[$position];
            $storefrontAssistantConversationTransfers[] = (new StorefrontAssistantConversationTransfer())->fromArray($decodedConversation, true);
        }

        if ($liveConversationReferences !== $conversationReferences) {
            $this->writeIndex(
                $customerReference,
                $liveConversationReferences,
                $this->aiCommerceConfig->getStorefrontAssistantConversationTimeToLive(),
            );
        }

        usort(
            $storefrontAssistantConversationTransfers,
            static fn (
                StorefrontAssistantConversationTransfer $left,
                StorefrontAssistantConversationTransfer $right
            ): int => ($right->getUpdatedAt() ?? 0) <=> ($left->getUpdatedAt() ?? 0),
        );

        return array_slice($storefrontAssistantConversationTransfers, 0, max(0, $limit));
    }

    public function hasConversation(string $customerReference, string $conversationReference): bool
    {
        $key = $this->buildConversationKey($customerReference, $conversationReference);

        return $this->decodeStoredConversation($this->storageClient->get($key)) !== null;
    }

    public function deleteConversation(string $customerReference, string $conversationReference): bool
    {
        $key = $this->buildConversationKey($customerReference, $conversationReference);

        $existed = $this->decodeStoredConversation($this->storageClient->get($key)) !== null;

        $this->storageClient->delete($key);
        $this->removeFromIndex($customerReference, $conversationReference);

        return $existed;
    }

    protected function saveIndex(string $customerReference, string $conversationReference, int $timeToLive): void
    {
        $conversationReferences = array_values(
            array_filter(
                $this->readIndex($customerReference),
                static fn (string $storedConversationReference): bool => $storedConversationReference !== $conversationReference,
            ),
        );

        $conversationReferences[] = $conversationReference;

        $conversationReferences = array_slice(
            $conversationReferences,
            -max(1, $this->aiCommerceConfig->getStorefrontAssistantConversationIndexLimit()),
        );

        $this->writeIndex($customerReference, $conversationReferences, $timeToLive);
    }

    protected function removeFromIndex(string $customerReference, string $conversationReference): void
    {
        $conversationReferences = $this->readIndex($customerReference);

        $remainingConversationReferences = array_values(
            array_filter(
                $conversationReferences,
                static fn (string $storedConversationReference): bool => $storedConversationReference !== $conversationReference,
            ),
        );

        if ($remainingConversationReferences === $conversationReferences) {
            return;
        }

        $this->writeIndex(
            $customerReference,
            $remainingConversationReferences,
            $this->aiCommerceConfig->getStorefrontAssistantConversationTimeToLive(),
        );
    }

    /**
     * @return array<int, string>
     */
    protected function readIndex(string $customerReference): array
    {
        $indexKey = sprintf(static::KEY_PATTERN_INDEX, $customerReference);

        $conversationReferences = $this->decodeStoredConversation($this->storageClient->get($indexKey)) ?? [];

        return array_values(array_filter($conversationReferences, 'is_string'));
    }

    /**
     * @param array<int, string> $conversationReferences
     *
     * @throws \SprykerFeature\Client\AiCommerce\StorefrontAssistant\Exception\StorefrontAssistantConversationPersistenceException
     */
    protected function writeIndex(string $customerReference, array $conversationReferences, int $timeToLive): void
    {
        $indexKey = sprintf(static::KEY_PATTERN_INDEX, $customerReference);

        $encodedIndex = json_encode($conversationReferences, static::ENCODING_FLAGS);

        if ($encodedIndex === false) {
            $message = sprintf(
                static::LOG_MESSAGE_INDEX_ENCODING_FAILED,
                $customerReference,
                json_last_error_msg(),
            );

            $this->getLogger()->error($message);

            throw new StorefrontAssistantConversationPersistenceException($message);
        }

        $this->storageClient->set($indexKey, $encodedIndex, $timeToLive);
    }

    /**
     * @see \Spryker\Client\Storage\Redis\Service::get()
     *
     * @return array<mixed>|null
     */
    protected function decodeStoredConversation(mixed $storedValue): ?array
    {
        if (is_array($storedValue)) {
            return $storedValue;
        }

        if (!is_string($storedValue) || $storedValue === '') {
            return null;
        }

        $decodedValue = json_decode($storedValue, true);

        return is_array($decodedValue) ? $decodedValue : null;
    }

    /**
     * @throws \SprykerFeature\Client\AiCommerce\StorefrontAssistant\Exception\StorefrontAssistantConversationScopeException
     */
    protected function buildConversationKey(string $customerReference, string $conversationReference): string
    {
        if (trim($customerReference) === '') {
            throw new StorefrontAssistantConversationScopeException(static::ERROR_MESSAGE_BLANK_CUSTOMER_REFERENCE);
        }

        if (trim($conversationReference) === '') {
            throw new StorefrontAssistantConversationScopeException(static::ERROR_MESSAGE_BLANK_CONVERSATION_REFERENCE);
        }

        return sprintf(static::KEY_PATTERN_CONVERSATION, $customerReference, $conversationReference);
    }
}
