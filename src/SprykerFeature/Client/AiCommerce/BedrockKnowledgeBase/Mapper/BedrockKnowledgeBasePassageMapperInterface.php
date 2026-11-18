<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\BedrockKnowledgeBase\Mapper;

use Generated\Shared\Transfer\KnowledgePassageTransfer;

interface BedrockKnowledgeBasePassageMapperInterface
{
    /**
     * @param array<string, mixed> $retrievalResult
     */
    public function mapRetrievalResultToKnowledgePassage(
        array $retrievalResult,
        int $position,
        KnowledgePassageTransfer $knowledgePassageTransfer
    ): KnowledgePassageTransfer;
}
