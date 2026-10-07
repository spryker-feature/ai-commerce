<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce;

use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Shared\Kernel\AbstractSharedConfig;

class AiCommerceConfig extends AbstractSharedConfig
{
    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_GENERAL_IS_ENABLED = 'ai_commerce:storefront_assistant:general:is_enabled';

    protected const bool STOREFRONT_ASSISTANT_DEFAULT_IS_ENABLED = false;

    protected const int STOREFRONT_ASSISTANT_CHARACTERS_PER_TOKEN = 4;

    protected const int STOREFRONT_ASSISTANT_MAX_MESSAGE_CONTEXT_WINDOW_SHARE_DIVISOR = 2;

    protected const int STOREFRONT_ASSISTANT_MIN_MESSAGE_LENGTH_CHARACTERS = 2000;

    /**
     * @uses \Spryker\Zed\AiFoundation\AiFoundationConfig::DEFAULT_CONVERSATION_HISTORY_CONTEXT_WINDOW
     */
    protected const int STOREFRONT_ASSISTANT_DEFAULT_CONVERSATION_HISTORY_CONTEXT_WINDOW = 50000;

    protected const int STOREFRONT_ASSISTANT_MAX_ATTACHMENT_SIZE_BYTES = 5_242_880;

    protected const int STOREFRONT_ASSISTANT_MAX_ATTACHMENT_COUNT = 5;

    protected const int STOREFRONT_ASSISTANT_MAX_ATTACHMENT_FILENAME_LENGTH = 255;

    protected const int STOREFRONT_ASSISTANT_MAX_CONVERSATION_REFERENCE_LENGTH = 64;

    protected const string STOREFRONT_ASSISTANT_CONVERSATION_REFERENCE_PATTERN = '/\A[A-Za-z0-9_-]+\z/';

    /**
     * @var list<string>
     */
    protected const array STOREFRONT_ASSISTANT_SUPPORTED_ATTACHMENT_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    /**
     * @var array<string, list<string>>
     */
    protected const array STOREFRONT_ASSISTANT_ATTACHMENT_MIME_TYPE_SIGNATURES = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89PNG\r\n\x1A\n"],
        'image/gif' => ['GIF87a', 'GIF89a'],
        'image/webp' => ['RIFF'],
        'application/pdf' => ['%PDF-'],
    ];

    /**
     * Specification:
     * - Returns the Configuration Management key of the Storefront Assistant enable toggle.
     *
     * @api
     */
    public function getStorefrontAssistantEnabledKey(): string
    {
        return static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_GENERAL_IS_ENABLED;
    }

    /**
     * Specification:
     * - Returns the enabled state the Storefront Assistant falls back to when the Configuration Management value is unset.
     *
     * @api
     */
    public function isStorefrontAssistantEnabledByDefault(): bool
    {
        return static::STOREFRONT_ASSISTANT_DEFAULT_IS_ENABLED;
    }

    /**
     * Specification:
     * - Returns the media types a Storefront Assistant attachment may declare.
     * - Single source of truth shared by the Client validator and the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     *
     * @return list<string>
     */
    public function getStorefrontAssistantSupportedAttachmentMimeTypes(): array
    {
        return static::STOREFRONT_ASSISTANT_SUPPORTED_ATTACHMENT_MIME_TYPES;
    }

    /**
     * Specification:
     * - Returns the binary signatures a decoded attachment is verified against, keyed by the media type the client claims.
     * - Guards against a client that declares a supported media type for bytes of another kind.
     *
     * @api
     *
     * @return array<string, list<string>>
     */
    public function getStorefrontAssistantAttachmentMimeTypeSignatures(): array
    {
        return static::STOREFRONT_ASSISTANT_ATTACHMENT_MIME_TYPE_SIGNATURES;
    }

    /**
     * Specification:
     * - Returns the maximum decoded byte size a single Storefront Assistant attachment may reach.
     * - Single source of truth shared by the Client validator and the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentSizeBytes(): int
    {
        return static::STOREFRONT_ASSISTANT_MAX_ATTACHMENT_SIZE_BYTES;
    }

    /**
     * Specification:
     * - Returns the maximum number of attachments a single Storefront Assistant message may carry.
     * - Single source of truth shared by the Client validator and the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentCount(): int
    {
        return static::STOREFRONT_ASSISTANT_MAX_ATTACHMENT_COUNT;
    }

    /**
     * Specification:
     * - Returns the maximum number of characters a stored attachment file name may reach.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentFilenameLength(): int
    {
        return static::STOREFRONT_ASSISTANT_MAX_ATTACHMENT_FILENAME_LENGTH;
    }

    /**
     * Specification:
     * - Returns the maximum number of characters a client-provided Storefront Assistant conversation reference may reach.
     *
     * @api
     */
    public function getStorefrontAssistantMaxConversationReferenceLength(): int
    {
        return static::STOREFRONT_ASSISTANT_MAX_CONVERSATION_REFERENCE_LENGTH;
    }

    /**
     * Specification:
     * - Returns the regular expression a client-provided Storefront Assistant conversation reference must match.
     *
     * @api
     */
    public function getStorefrontAssistantConversationReferencePattern(): string
    {
        return static::STOREFRONT_ASSISTANT_CONVERSATION_REFERENCE_PATTERN;
    }

    /**
     * Specification:
     * - Returns the maximum number of characters a single Storefront Assistant message may reach.
     * - Derived from the conversation history context window so the limit follows a project that resizes it.
     * - Never returns less than the floor that keeps ordinary customer questions acceptable.
     * - Single source of truth shared by the Client validator and the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxMessageLengthCharacters(): int
    {
        $contextWindowTokens = (int)$this->get(
            AiFoundationConstants::CONVERSATION_HISTORY_CONTEXT_WINDOW,
            static::STOREFRONT_ASSISTANT_DEFAULT_CONVERSATION_HISTORY_CONTEXT_WINDOW,
        );

        $maxMessageLengthCharacters = intdiv(
            $contextWindowTokens * static::STOREFRONT_ASSISTANT_CHARACTERS_PER_TOKEN,
            static::STOREFRONT_ASSISTANT_MAX_MESSAGE_CONTEXT_WINDOW_SHARE_DIVISOR,
        );

        return max($maxMessageLengthCharacters, static::STOREFRONT_ASSISTANT_MIN_MESSAGE_LENGTH_CHARACTERS);
    }
}
