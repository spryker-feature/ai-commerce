<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper;

use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;

class BedrockKnowledgeBaseDocumentMapper implements BedrockKnowledgeBaseDocumentMapperInterface
{
    protected const string SOURCE_TYPE_IN_LINE = 'IN_LINE';

    protected const string METADATA_KEY_SOURCE_IDENTIFIER = 'sourceIdentifier';

    protected const string INLINE_CONTENT_TYPE_TEXT = 'TEXT';

    protected const string INLINE_CONTENT_TYPE_BYTE = 'BYTE';

    protected const string METADATA_TYPE_IN_LINE_ATTRIBUTE = 'IN_LINE_ATTRIBUTE';

    protected const string METADATA_VALUE_TYPE_STRING = 'STRING';

    protected const string METADATA_VALUE_TYPE_NUMBER = 'NUMBER';

    protected const string METADATA_VALUE_TYPE_BOOLEAN = 'BOOLEAN';

    protected const string DEFAULT_MIME_TYPE = 'application/octet-stream';

    protected const string CHARACTER_ENCODING_UTF_8 = 'UTF-8';

    protected const int MAX_METADATA_ATTRIBUTES = 50;

    protected const int MAX_METADATA_KEY_LENGTH = 200;

    protected const int MAX_METADATA_STRING_VALUE_LENGTH = 2048;

    public function __construct(protected AiCommerceConfig $aiCommerceConfig)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function mapKnowledgeDocumentContentToKnowledgeBaseDocument(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer
    ): array {
        $knowledgeDocumentSourceTransfer = $knowledgeDocumentContentTransfer->getKnowledgeDocumentSourceOrFail();

        return [
            'content' => [
                'dataSourceType' => SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_TYPE_CUSTOM,
                'custom' => [
                    'customDocumentIdentifier' => ['id' => $this->createDocumentKey($knowledgeDocumentSourceTransfer)],
                    'sourceType' => static::SOURCE_TYPE_IN_LINE,
                    'inlineContent' => $this->createInlineContent($knowledgeDocumentContentTransfer),
                ],
            ],
            'metadata' => [
                'type' => static::METADATA_TYPE_IN_LINE_ATTRIBUTE,
                'inlineAttributes' => $this->createInlineAttributes($knowledgeDocumentSourceTransfer),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createInlineContent(KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer): array
    {
        $content = (string)$knowledgeDocumentContentTransfer->getContent();
        $mimeType = (string)$knowledgeDocumentContentTransfer->getMimeType();

        if (in_array($mimeType, $this->aiCommerceConfig->getBedrockKnowledgeBaseInlineTextMimeTypes(), true) && mb_check_encoding($content, static::CHARACTER_ENCODING_UTF_8)) {
            return [
                'type' => static::INLINE_CONTENT_TYPE_TEXT,
                'textContent' => ['data' => $content],
            ];
        }

        return [
            'type' => static::INLINE_CONTENT_TYPE_BYTE,
            'byteContent' => [
                'mimeType' => $mimeType ?: static::DEFAULT_MIME_TYPE,
                'data' => $content,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function createInlineAttributes(KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): array
    {
        $metadata = [
            static::METADATA_KEY_SOURCE_IDENTIFIER => $knowledgeDocumentSourceTransfer->getSourceIdentifierOrFail(),
            SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_KEY => $this->createDocumentKey($knowledgeDocumentSourceTransfer),
            SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_SOURCE_TYPE => $knowledgeDocumentSourceTransfer->getSourceTypeOrFail(),
            SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_LOCALE => $knowledgeDocumentSourceTransfer->getLocaleName()
                ?? SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_LOCALE_ALL,
            SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_NAME => $knowledgeDocumentSourceTransfer->getDocumentName(),
        ] + $knowledgeDocumentSourceTransfer->getMetadata();
        $inlineAttributes = [];

        foreach ($metadata as $metadataKey => $metadataValue) {
            $metadataAttributeValue = $this->createMetadataAttributeValue($metadataValue);

            if ($metadataAttributeValue === null || !$this->isMetadataKeyValid((string)$metadataKey)) {
                continue;
            }

            $inlineAttributes[] = ['key' => (string)$metadataKey, 'value' => $metadataAttributeValue];
        }

        return array_slice($inlineAttributes, 0, static::MAX_METADATA_ATTRIBUTES);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function createMetadataAttributeValue(mixed $metadataValue): ?array
    {
        if (is_bool($metadataValue)) {
            return ['type' => static::METADATA_VALUE_TYPE_BOOLEAN, 'booleanValue' => $metadataValue];
        }

        if (is_int($metadataValue) || is_float($metadataValue)) {
            return ['type' => static::METADATA_VALUE_TYPE_NUMBER, 'numberValue' => $metadataValue];
        }

        if (!is_string($metadataValue) || $metadataValue === '') {
            return null;
        }

        return [
            'type' => static::METADATA_VALUE_TYPE_STRING,
            'stringValue' => mb_substr($metadataValue, 0, static::MAX_METADATA_STRING_VALUE_LENGTH),
        ];
    }

    protected function isMetadataKeyValid(string $metadataKey): bool
    {
        return $metadataKey !== '' && strlen($metadataKey) <= static::MAX_METADATA_KEY_LENGTH;
    }

    protected function createDocumentKey(KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): string
    {
        return sprintf(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT,
            $knowledgeDocumentSourceTransfer->getSourceTypeOrFail(),
            $knowledgeDocumentSourceTransfer->getSourceIdentifierOrFail(),
        );
    }
}
