<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Document;

use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class DocumentToolArgumentMapper implements DocumentToolArgumentMapperInterface
{
    public const string PARAMETER_ABSTRACT_SKUS = 'abstract_skus';

    public const string PARAMETER_CONCRETE_SKUS = 'concrete_skus';

    public const string PARAMETER_PRODUCT_ABSTRACT_IDS = 'product_abstract_ids';

    public const string PARAMETER_ATTACHMENT_IDS = 'attachment_ids';

    protected const string PATTERN_LIST_SEPARATOR = '/,/';

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function mapArgumentsToKnowledgeDocumentScopeRequest(array $arguments): KnowledgeDocumentScopeRequestTransfer
    {
        return (new KnowledgeDocumentScopeRequestTransfer())
            ->setScopeReferences(array_filter([
                SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU => $this->mapArgumentToStrings($arguments, static::PARAMETER_ABSTRACT_SKUS),
                SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_CONCRETE_SKU => $this->mapArgumentToStrings($arguments, static::PARAMETER_CONCRETE_SKUS),
                SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID => array_map('strval', $this->mapArgumentToPositiveIntegers($arguments, static::PARAMETER_PRODUCT_ABSTRACT_IDS)),
            ]))
            ->setSourceIdentifiers($this->mapArgumentToStrings($arguments, static::PARAMETER_ATTACHMENT_IDS));
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return list<string>
     */
    public function mapArgumentToStrings(array $arguments, string $parameterName): array
    {
        $value = $arguments[$parameterName] ?? [];
        $values = is_string($value) ? preg_split(static::PATTERN_LIST_SEPARATOR, $value) : $value;

        if (!is_array($values)) {
            return [];
        }

        $strings = [];

        foreach ($values as $item) {
            if (!is_scalar($item) || trim((string)$item) === '') {
                continue;
            }

            $strings[] = trim((string)$item);
        }

        return array_values(array_unique($strings));
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return list<int>
     */
    protected function mapArgumentToPositiveIntegers(array $arguments, string $parameterName): array
    {
        $integers = [];

        foreach ($this->mapArgumentToStrings($arguments, $parameterName) as $value) {
            if (!is_numeric($value) || (int)$value <= 0) {
                continue;
            }

            $integers[] = (int)$value;
        }

        return array_values(array_unique($integers));
    }
}
