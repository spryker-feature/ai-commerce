<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Checker;

use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Reader\BedrockKnowledgeBaseReferenceReaderInterface;

class BedrockKnowledgeBaseConfigurationChecker implements BedrockKnowledgeBaseConfigurationCheckerInterface
{
    public function __construct(
        protected BedrockKnowledgeBaseReferenceReaderInterface $bedrockKnowledgeBaseReferenceReader,
    ) {
    }

    public function isSourceTypeConfigured(string $sourceType): bool
    {
        $bedrockKnowledgeBaseReferenceTransfer = $this->bedrockKnowledgeBaseReferenceReader->getReference();

        if ((string)$bedrockKnowledgeBaseReferenceTransfer->getKnowledgeBaseId() === '') {
            return false;
        }

        return (string)($bedrockKnowledgeBaseReferenceTransfer->getDataSourceIdsBySourceType()[$sourceType] ?? '') !== '';
    }
}
