<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper;

interface BedrockKnowledgeBaseDocumentStatusMapperInterface
{
    public function mapKnowledgeBaseDocumentStatusToKnowledgeDocumentStatus(string $knowledgeBaseDocumentStatus): string;

    public function createFailureMessage(string $knowledgeBaseDocumentStatus, ?string $statusReason): string;
}
