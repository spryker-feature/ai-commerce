<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper;

use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class BedrockKnowledgeBaseDocumentStatusMapper implements BedrockKnowledgeBaseDocumentStatusMapperInterface
{
    protected const array INDEXED_KNOWLEDGE_BASE_DOCUMENT_STATUSES = [
        'INDEXED',
        'PARTIALLY_INDEXED',
        'METADATA_PARTIALLY_INDEXED',
    ];

    protected const array PROCESSING_KNOWLEDGE_BASE_DOCUMENT_STATUSES = [
        'PENDING',
        'STARTING',
        'IN_PROGRESS',
        'TEXT_INDEXED',
    ];

    protected const string FAILURE_MESSAGE_FORMAT = 'The Amazon Bedrock knowledge base document status is %s.';

    protected const string FAILURE_MESSAGE_WITH_REASON_FORMAT = 'The Amazon Bedrock knowledge base document status is %s: %s';

    public function mapKnowledgeBaseDocumentStatusToKnowledgeDocumentStatus(string $knowledgeBaseDocumentStatus): string
    {
        if (in_array($knowledgeBaseDocumentStatus, static::INDEXED_KNOWLEDGE_BASE_DOCUMENT_STATUSES, true)) {
            return SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED;
        }

        if (in_array($knowledgeBaseDocumentStatus, static::PROCESSING_KNOWLEDGE_BASE_DOCUMENT_STATUSES, true)) {
            return SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING;
        }

        return SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED;
    }

    public function createFailureMessage(string $knowledgeBaseDocumentStatus, ?string $statusReason): string
    {
        if ((string)$statusReason === '') {
            return sprintf(static::FAILURE_MESSAGE_FORMAT, $knowledgeBaseDocumentStatus);
        }

        return sprintf(static::FAILURE_MESSAGE_WITH_REASON_FORMAT, $knowledgeBaseDocumentStatus, $statusReason);
    }
}
