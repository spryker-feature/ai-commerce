<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Indexer;

use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;

interface BedrockKnowledgeBaseDocumentIndexerInterface
{
    public function indexDocument(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer
    ): KnowledgeDocumentIndexResponseTransfer;

    public function deleteDocuments(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer;

    public function refreshDocumentStatuses(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer;
}
