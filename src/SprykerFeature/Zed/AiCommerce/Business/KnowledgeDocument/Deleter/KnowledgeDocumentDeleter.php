<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Deleter;

use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Builder\KnowledgeDocumentIndexResponseBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;

class KnowledgeDocumentDeleter implements KnowledgeDocumentDeleterInterface
{
    public function __construct(
        protected KnowledgeStorePluginInterface $knowledgeStorePlugin,
        protected KnowledgeDocumentIndexResponseBuilderInterface $knowledgeDocumentIndexResponseBuilder,
        protected AiCommerceEntityManagerInterface $aiCommerceEntityManager,
    ) {
    }

    public function deleteDocument(KnowledgeDocumentTransfer $knowledgeDocumentTransfer): KnowledgeDocumentIndexResponseTransfer
    {
        $knowledgeDocumentCollectionResponseTransfer = $this->knowledgeStorePlugin->deleteDocuments(
            (new KnowledgeDocumentCollectionTransfer())->addKnowledgeDocument($knowledgeDocumentTransfer),
        );

        if (!$knowledgeDocumentCollectionResponseTransfer->getIsSuccessful()) {
            return $this->knowledgeDocumentIndexResponseBuilder->createCollectionFailureResponse($knowledgeDocumentCollectionResponseTransfer);
        }

        $this->aiCommerceEntityManager->deleteKnowledgeDocument($knowledgeDocumentTransfer);

        return $this->knowledgeDocumentIndexResponseBuilder->createSuccessResponse();
    }
}
