<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\BedrockKnowledgeBase\Reader;

use Generated\Shared\Transfer\BedrockKnowledgeBaseReferenceTransfer;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;

class BedrockKnowledgeBaseReferenceReader implements BedrockKnowledgeBaseReferenceReaderInterface
{
    public function __construct(
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function getReference(): BedrockKnowledgeBaseReferenceTransfer
    {
        return (new BedrockKnowledgeBaseReferenceTransfer())
            ->setKnowledgeBaseId($this->aiCommerceConfig->getBedrockKnowledgeBaseId());
    }
}
