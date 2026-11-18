<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reindexer;

use Generated\Shared\Transfer\KnowledgeDocumentReindexRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentReindexResponseTransfer;

interface KnowledgeDocumentReindexerInterface
{
    public function reindex(
        KnowledgeDocumentReindexRequestTransfer $knowledgeDocumentReindexRequestTransfer,
    ): KnowledgeDocumentReindexResponseTransfer;
}
