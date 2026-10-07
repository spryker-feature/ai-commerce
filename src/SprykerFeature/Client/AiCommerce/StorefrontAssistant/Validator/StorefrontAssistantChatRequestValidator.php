<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Attachment\AttachmentValidatorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Gate\StorefrontAssistantFeatureGateInterface;

class StorefrontAssistantChatRequestValidator implements StorefrontAssistantChatRequestValidatorInterface
{
    protected const string MESSAGE_FEATURE_DISABLED = 'ai_commerce.storefront_assistant.validation.feature_disabled';

    protected const string MESSAGE_CUSTOMER_REFERENCE_REQUIRED = 'ai_commerce.storefront_assistant.validation.customer_reference_required';

    protected const string MESSAGE_CONVERSATION_REFERENCE_INVALID = 'ai_commerce.storefront_assistant.validation.conversation_reference_invalid';

    protected const string MESSAGE_MESSAGE_REQUIRED = 'ai_commerce.storefront_assistant.validation.message_required';

    protected const string MESSAGE_MESSAGE_TOO_LONG = 'ai_commerce.storefront_assistant.validation.message_too_long';

    protected const string GLOSSARY_PARAMETER_MAX = '%max%';

    public function __construct(
        protected StorefrontAssistantFeatureGateInterface $storefrontAssistantFeatureGate,
        protected AttachmentValidatorInterface $attachmentValidator,
        protected ConversationReferenceValidatorInterface $conversationReferenceValidator,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    public function validate(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): StorefrontAssistantChatRequestValidationResult {
        if (!$this->storefrontAssistantFeatureGate->isEnabled()) {
            return $this->createRejectedResult(static::MESSAGE_FEATURE_DISABLED);
        }

        if (trim((string)$storefrontAssistantChatRequestTransfer->getCustomerReference()) === '') {
            return $this->createRejectedResult(static::MESSAGE_CUSTOMER_REFERENCE_REQUIRED);
        }

        if (!$this->conversationReferenceValidator->isValid((string)$storefrontAssistantChatRequestTransfer->getConversationReference())) {
            return $this->createRejectedResult(static::MESSAGE_CONVERSATION_REFERENCE_INVALID);
        }

        $message = trim((string)$storefrontAssistantChatRequestTransfer->getMessage());

        if ($message === '') {
            return $this->createRejectedResult(static::MESSAGE_MESSAGE_REQUIRED);
        }

        $maxMessageLengthCharacters = $this->aiCommerceConfig->getStorefrontAssistantMaxMessageLengthCharacters();

        if (mb_strlen($message) > $maxMessageLengthCharacters) {
            return $this->createRejectedResult(
                static::MESSAGE_MESSAGE_TOO_LONG,
                [static::GLOSSARY_PARAMETER_MAX => $maxMessageLengthCharacters],
            );
        }

        $attachmentValidationResult = $this->attachmentValidator->validate($storefrontAssistantChatRequestTransfer);

        if (!$attachmentValidationResult->isValid()) {
            return $this->createRejectedResult(
                (string)$attachmentValidationResult->getRejectionMessage(),
                $attachmentValidationResult->getRejectionMessageParameters(),
            );
        }

        return new StorefrontAssistantChatRequestValidationResult([], $attachmentValidationResult->getAttachments());
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function createRejectedResult(string $message, array $parameters = []): StorefrontAssistantChatRequestValidationResult
    {
        return new StorefrontAssistantChatRequestValidationResult([
            (new ErrorTransfer())
                ->setMessage($message)
                ->setParameters($parameters),
        ]);
    }
}
