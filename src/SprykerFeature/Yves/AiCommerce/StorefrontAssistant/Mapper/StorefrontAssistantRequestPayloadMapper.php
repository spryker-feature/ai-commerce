<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationDeleteConditionsTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageConditionsTransfer;
use Generated\Shared\Transfer\StorefrontAssistantPageContextTransfer;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Sanitizer\StorefrontAssistantPageContextSanitizerInterface;

class StorefrontAssistantRequestPayloadMapper implements StorefrontAssistantRequestPayloadMapperInterface
{
    protected const string KEY_MESSAGE = 'message';

    protected const string KEY_CONVERSATION_REFERENCE = 'conversationReference';

    protected const string KEY_CONVERSATION_REFERENCES = 'conversationReferences';

    protected const string KEY_ATTACHMENTS = 'attachments';

    protected const string KEY_SELECTED_AGENT = 'selectedAgent';

    protected const string KEY_PAGE_CONTEXT = 'pageContext';

    public function __construct(
        protected StorefrontAssistantPageContextSanitizerInterface $storefrontAssistantPageContextSanitizer,
        protected StorefrontAssistantPageContextMapperInterface $storefrontAssistantPageContextMapper
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToStorefrontAssistantChatRequestTransfer(
        array $payload,
        string $customerReference,
        string $localeName
    ): StorefrontAssistantChatRequestTransfer {
        $rawAttachments = $payload[static::KEY_ATTACHMENTS] ?? [];
        $selectedAgent = $this->extractString($payload, static::KEY_SELECTED_AGENT);

        return (new StorefrontAssistantChatRequestTransfer())
            ->setMessage($this->extractString($payload, static::KEY_MESSAGE))
            ->setConversationReference($this->extractString($payload, static::KEY_CONVERSATION_REFERENCE))
            ->setCustomerReference($customerReference)
            ->setLocaleName($localeName)
            ->setSelectedAgent($selectedAgent !== '' ? $selectedAgent : null)
            ->setStorefrontAssistantPageContext($this->mapPageContext($payload[static::KEY_PAGE_CONTEXT] ?? null))
            ->setRawAttachments(is_array($rawAttachments) ? $rawAttachments : []);
    }

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToConversationCollectionCriteriaTransfer(
        array $payload,
        string $customerReference
    ): StorefrontAssistantConversationCollectionCriteriaTransfer {
        $storefrontAssistantConversationConditionsTransfer = (new StorefrontAssistantConversationConditionsTransfer())
            ->setCustomerReference($customerReference)
            ->setConversationReferences($this->extractConversationReferences($payload));

        return (new StorefrontAssistantConversationCollectionCriteriaTransfer())
            ->setStorefrontAssistantConversationConditions($storefrontAssistantConversationConditionsTransfer);
    }

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToConversationCollectionDeleteCriteriaTransfer(
        array $payload,
        string $customerReference
    ): StorefrontAssistantConversationCollectionDeleteCriteriaTransfer {
        $storefrontAssistantConversationDeleteConditionsTransfer = (new StorefrontAssistantConversationDeleteConditionsTransfer())
            ->setCustomerReference($customerReference)
            ->setConversationReferences($this->extractConversationReferences($payload));

        return (new StorefrontAssistantConversationCollectionDeleteCriteriaTransfer())
            ->setStorefrontAssistantConversationDeleteConditions($storefrontAssistantConversationDeleteConditionsTransfer);
    }

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToConversationMessageCollectionCriteriaTransfer(
        array $payload,
        string $customerReference
    ): StorefrontAssistantConversationMessageCollectionCriteriaTransfer {
        $storefrontAssistantConversationMessageConditionsTransfer = (new StorefrontAssistantConversationMessageConditionsTransfer())
            ->setCustomerReference($customerReference)
            ->setConversationReference($this->extractString($payload, static::KEY_CONVERSATION_REFERENCE));

        return (new StorefrontAssistantConversationMessageCollectionCriteriaTransfer())
            ->setStorefrontAssistantConversationMessageConditions($storefrontAssistantConversationMessageConditionsTransfer);
    }

    /**
     * @param array<mixed> $payload
     */
    protected function extractString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<mixed> $payload
     *
     * @return list<string>
     */
    protected function extractConversationReferences(array $payload): array
    {
        $conversationReferences = $payload[static::KEY_CONVERSATION_REFERENCES] ?? null;

        if (is_array($conversationReferences) && $conversationReferences !== []) {
            return array_values(array_filter($conversationReferences, 'is_string'));
        }

        $conversationReference = $this->extractString($payload, static::KEY_CONVERSATION_REFERENCE);

        return $conversationReference !== '' ? [$conversationReference] : [];
    }

    protected function mapPageContext(mixed $pageContext): ?StorefrontAssistantPageContextTransfer
    {
        $sanitizedPageContext = $this->storefrontAssistantPageContextSanitizer->sanitizePageContext($pageContext);

        if ($sanitizedPageContext === null) {
            return null;
        }

        return $this->storefrontAssistantPageContextMapper->mapPageContextToStorefrontAssistantPageContextTransfer($sanitizedPageContext);
    }
}
