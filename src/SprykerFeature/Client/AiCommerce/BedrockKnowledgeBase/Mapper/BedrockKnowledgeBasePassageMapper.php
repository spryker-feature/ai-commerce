<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\BedrockKnowledgeBase\Mapper;

use Generated\Shared\Transfer\KnowledgePassageTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class BedrockKnowledgeBasePassageMapper implements BedrockKnowledgeBasePassageMapperInterface
{
    protected const string RETRIEVAL_RESULT_CONTENT = 'content';

    protected const string RETRIEVAL_RESULT_CONTENT_TEXT = 'text';

    protected const string RETRIEVAL_RESULT_METADATA = 'metadata';

    protected const string RETRIEVAL_RESULT_SCORE = 'score';

    protected const string RETRIEVAL_RESULT_LOCATION = 'location';

    protected const string RETRIEVAL_RESULT_CUSTOM_DOCUMENT_LOCATION = 'customDocumentLocation';

    protected const string RETRIEVAL_RESULT_CUSTOM_DOCUMENT_ID = 'id';

    protected const string SYSTEM_METADATA_KEY_PREFIX = 'x-amz-bedrock-kb-';

    protected const string SYSTEM_METADATA_KEY_CHUNK_ID = 'x-amz-bedrock-kb-chunk-id';

    protected const string PASSAGE_ID_FORMAT = '%s:%s';

    protected const array DOCUMENT_METADATA_KEYS = [
        SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_KEY,
        SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_SOURCE_TYPE,
        SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_LOCALE,
        SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_NAME,
    ];

    /**
     * @param array<string, mixed> $retrievalResult
     */
    public function mapRetrievalResultToKnowledgePassage(
        array $retrievalResult,
        int $position,
        KnowledgePassageTransfer $knowledgePassageTransfer
    ): KnowledgePassageTransfer {
        $metadata = $retrievalResult[static::RETRIEVAL_RESULT_METADATA] ?? [];
        $documentKey = (string)($metadata[SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_KEY]
            ?? $retrievalResult[static::RETRIEVAL_RESULT_LOCATION][static::RETRIEVAL_RESULT_CUSTOM_DOCUMENT_LOCATION][static::RETRIEVAL_RESULT_CUSTOM_DOCUMENT_ID]
            ?? '');

        return $knowledgePassageTransfer
            ->setId(sprintf(static::PASSAGE_ID_FORMAT, $documentKey, $metadata[static::SYSTEM_METADATA_KEY_CHUNK_ID] ?? $position))
            ->setDocumentKey($documentKey)
            ->setSourceType($this->findMetadataValue($metadata, SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_SOURCE_TYPE))
            ->setLocale($this->findMetadataValue($metadata, SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_LOCALE))
            ->setDocumentName($this->findMetadataValue($metadata, SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_NAME))
            ->setContent((string)($retrievalResult[static::RETRIEVAL_RESULT_CONTENT][static::RETRIEVAL_RESULT_CONTENT_TEXT] ?? ''))
            ->setScore((float)($retrievalResult[static::RETRIEVAL_RESULT_SCORE] ?? 0.0))
            ->setMetadata($this->filterSourceMetadata($metadata));
    }

    /**
     * @param array<string, mixed> $metadata
     */
    protected function findMetadataValue(array $metadata, string $metadataKey): ?string
    {
        if (!isset($metadata[$metadataKey]) || !is_scalar($metadata[$metadataKey])) {
            return null;
        }

        return (string)$metadata[$metadataKey];
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    protected function filterSourceMetadata(array $metadata): array
    {
        return array_filter(
            array_diff_key($metadata, array_flip(static::DOCUMENT_METADATA_KEYS)),
            static fn (string|int $metadataKey): bool => !str_starts_with((string)$metadataKey, static::SYSTEM_METADATA_KEY_PREFIX),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
