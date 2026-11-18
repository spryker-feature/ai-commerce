<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer;

use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;

interface KnowledgeDocumentContentIndexerInterface
{
    public function indexDocumentContent(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer,
        KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin,
    ): KnowledgeDocumentIndexResponseTransfer;
}
