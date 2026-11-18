<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\BedrockKnowledgeBase\Query;

use Generated\Shared\Transfer\KnowledgeSearchConditionsTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class BedrockKnowledgeBaseRetrievalFilterBuilder implements BedrockKnowledgeBaseRetrievalFilterBuilderInterface
{
    protected const string FILTER_OPERATOR_IN = 'in';

    protected const string FILTER_OPERATOR_EQUALS = 'equals';

    protected const string FILTER_OPERATOR_AND_ALL = 'andAll';

    protected const string FILTER_KEY = 'key';

    protected const string FILTER_VALUE = 'value';

    /**
     * @return array<string, mixed>
     */
    public function buildRetrievalFilter(KnowledgeSearchConditionsTransfer $knowledgeSearchConditionsTransfer): array
    {
        $retrievalFilters = array_values(array_filter([
            $this->createInFilter(
                SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_KEY,
                [...$knowledgeSearchConditionsTransfer->getDocumentKeys(), ...$knowledgeSearchConditionsTransfer->getIds()],
            ),
            $this->createInFilter(SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_SOURCE_TYPE, $knowledgeSearchConditionsTransfer->getSourceTypes()),
            $this->createLocaleFilter($knowledgeSearchConditionsTransfer->getLocales()),
            $this->createDocumentNameFilter((string)$knowledgeSearchConditionsTransfer->getDocumentName()),
        ]));

        if (count($retrievalFilters) < 2) {
            return $retrievalFilters[0] ?? [];
        }

        return [static::FILTER_OPERATOR_AND_ALL => $retrievalFilters];
    }

    /**
     * @param array<string> $values
     *
     * @return array<string, mixed>
     */
    protected function createInFilter(string $metadataKey, array $values): array
    {
        $values = array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));

        if ($values === []) {
            return [];
        }

        return [static::FILTER_OPERATOR_IN => [static::FILTER_KEY => $metadataKey, static::FILTER_VALUE => $values]];
    }

    /**
     * @param array<string> $locales
     *
     * @return array<string, mixed>
     */
    protected function createLocaleFilter(array $locales): array
    {
        if ($locales === []) {
            return [];
        }

        return $this->createInFilter(
            SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_LOCALE,
            [...$locales, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_LOCALE_ALL],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function createDocumentNameFilter(string $documentName): array
    {
        if ($documentName === '') {
            return [];
        }

        return [
        static::FILTER_OPERATOR_EQUALS => [
            static::FILTER_KEY => SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_METADATA_KEY_DOCUMENT_NAME,
            static::FILTER_VALUE => $documentName,
        ]];
    }
}
