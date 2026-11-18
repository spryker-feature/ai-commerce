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
    /**
     * Specification:
     * - Queue that receives one message per knowledge document to index.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_INDEX_QUEUE = 'knowledge_document.index';

    /**
     * Specification:
     * - Error queue of `KNOWLEDGE_DOCUMENT_INDEX_QUEUE`.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_INDEX_ERROR_QUEUE = 'knowledge_document.index.error';

    /**
     * Specification:
     * - Source type of the documents read from product attachments.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT = 'product_attachment';

    /**
     * Specification:
     * - `sprintf()` format of a document key: source type, source identifier.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_KEY_FORMAT = '%s:%s';

    /**
     * Specification:
     * - Scope reference type of the `idProductAbstract` values in `KnowledgeDocumentScopeRequestTransfer.scopeReferences`.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID = 'product_abstract_id';

    /**
     * Specification:
     * - Scope reference type of the abstract product SKUs in `KnowledgeDocumentScopeRequestTransfer.scopeReferences`.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU = 'abstract_sku';

    /**
     * Specification:
     * - Scope reference type of the concrete product SKUs in `KnowledgeDocumentScopeRequestTransfer.scopeReferences`.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_CONCRETE_SKU = 'concrete_sku';

    /**
     * Specification:
     * - Knowledge passage locale of a document that applies to all locales.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_LOCALE_ALL = '*';

    /**
     * Specification:
     * - Status of a document that waits for the index queue.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_STATUS_PENDING = 'pending';

    /**
     * Specification:
     * - Status of a document that the knowledge store indexes later. The status refresh reads its store status.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_STATUS_PROCESSING = 'processing';

    /**
     * Specification:
     * - Status of a document that the knowledge store has indexed.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_STATUS_INDEXED = 'indexed';

    /**
     * Specification:
     * - Status of a document that the knowledge store cannot index. The error message has the reason.
     *
     * @api
     */
    public const string KNOWLEDGE_DOCUMENT_STATUS_FAILED = 'failed';

    /**
     * Specification:
     * - Metadata attribute of the Amazon Bedrock knowledge base document with the document key.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_KEY = 'document_key';

    /**
     * Specification:
     * - Metadata attribute of the Amazon Bedrock knowledge base document with the source type.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_SOURCE_TYPE = 'source_type';

    /**
     * Specification:
     * - Metadata attribute of the Amazon Bedrock knowledge base document with the locale, `*` for all locales.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_LOCALE = 'locale';

    /**
     * Specification:
     * - Metadata attribute of the Amazon Bedrock knowledge base document with the document name.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_NAME = 'document_name';

    /**
     * Specification:
     * - Data source type of the documents that the application sends to the Amazon Bedrock knowledge base.
     *
     * @api
     */
    public const string BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_TYPE_CUSTOM = 'CUSTOM';

    protected const string BEDROCK_KNOWLEDGE_BASE_DEFAULT_AWS_REGION = 'eu-central-1';

    protected const string AWS_CLIENT_REGION = 'region';

    protected const string AWS_CLIENT_VERSION = 'version';

    protected const string AWS_CLIENT_VERSION_LATEST = 'latest';

    protected const string AWS_CLIENT_CREDENTIALS = 'credentials';

    protected const string AWS_CLIENT_RETRIES = 'retries';

    /**
     * @var array<string, string|int>
     */
    protected const array BEDROCK_KNOWLEDGE_BASE_AWS_CLIENT_RETRY_CONFIGURATION = [
        'mode' => 'adaptive',
        'max_attempts' => 5,
    ];

    protected const string AWS_CREDENTIALS_KEY = 'key';

    protected const string AWS_CREDENTIALS_SECRET = 'secret';

    protected const string AWS_CREDENTIALS_TOKEN = 'token';

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

    /**
     * Specification:
     * - Returns the AWS region of the Amazon Bedrock knowledge base.
     * - Returns `eu-central-1` when it is not set.
     *
     * @api
     */
    public function getBedrockKnowledgeBaseAwsRegion(): string
    {
        return (string)$this->get(AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_REGION, '') ?: static::BEDROCK_KNOWLEDGE_BASE_DEFAULT_AWS_REGION;
    }

    /**
     * Specification:
     * - Returns the constructor arguments of the AWS SDK clients `BedrockAgentClient` and `BedrockAgentRuntimeClient`: region, API version, retry configuration and the explicit credentials when they are set.
     * - The retry configuration uses the `adaptive` retry mode of the AWS SDK with 5 attempts: a throttled request is sent again after an exponential backoff, and the client limits its own request rate after a throttling response.
     *
     * @api
     *
     * @return array<string, mixed>
     */
    public function getBedrockKnowledgeBaseAwsClientConfiguration(): array
    {
        $awsClientConfiguration = [
            static::AWS_CLIENT_REGION => $this->getBedrockKnowledgeBaseAwsRegion(),
            static::AWS_CLIENT_VERSION => static::AWS_CLIENT_VERSION_LATEST,
            static::AWS_CLIENT_RETRIES => static::BEDROCK_KNOWLEDGE_BASE_AWS_CLIENT_RETRY_CONFIGURATION,
        ];
        $awsCredentials = $this->getBedrockKnowledgeBaseAwsCredentials();

        if ($awsCredentials === []) {
            return $awsClientConfiguration;
        }

        return $awsClientConfiguration + [static::AWS_CLIENT_CREDENTIALS => $awsCredentials];
    }

    /**
     * Specification:
     * - Returns the explicit AWS credentials of the Amazon Bedrock knowledge base: `key`, `secret` and optional `token`.
     * - Returns an empty array when no access key ID is set: the AWS SDK then uses its default credential provider chain.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getBedrockKnowledgeBaseAwsCredentials(): array
    {
        $accessKeyId = (string)$this->get(AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_ACCESS_KEY_ID, '');

        if ($accessKeyId === '') {
            return [];
        }

        return array_filter([
            static::AWS_CREDENTIALS_KEY => $accessKeyId,
            static::AWS_CREDENTIALS_SECRET => (string)$this->get(AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SECRET_ACCESS_KEY, ''),
            static::AWS_CREDENTIALS_TOKEN => (string)$this->get(AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SESSION_TOKEN, ''),
        ]);
    }

    /**
     * Specification:
     * - Returns the configured ID of the Amazon Bedrock knowledge base, or an empty string.
     *
     * @api
     */
    public function getBedrockKnowledgeBaseId(): string
    {
        return (string)$this->get(AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_ID, '');
    }

    /**
     * Specification:
     * - Returns the configured IDs of the custom data sources of the Amazon Bedrock knowledge base, keyed by knowledge document source type.
     * - Leaves out the source types without an ID.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getBedrockKnowledgeBaseDataSourceIds(): array
    {
        $dataSourceIds = [];

        foreach ((array)$this->get(AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS, []) as $sourceType => $dataSourceId) {
            if ((string)$dataSourceId === '') {
                continue;
            }

            $dataSourceIds[(string)$sourceType] = (string)$dataSourceId;
        }

        return $dataSourceIds;
    }
}
