<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer;

use DateTime;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Builder\KnowledgeDocumentIndexResponseBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;

class KnowledgeDocumentContentIndexer implements KnowledgeDocumentContentIndexerInterface
{
    protected const string STATUS_PROCESSING = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING;

    protected const string STATUS_INDEXED = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED;

    protected const string STATUS_FAILED = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED;

    protected const string CONTENT_HASH_ALGORITHM = 'sha256';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string ERROR_MESSAGE_FILE_TOO_LARGE = 'The document is larger than %d bytes.';

    protected const string ERROR_MESSAGE_NO_STATUS = 'The knowledge store plugin returned no document status.';

    public function __construct(
        protected KnowledgeStorePluginInterface $knowledgeStorePlugin,
        protected KnowledgeDocumentIndexResponseBuilderInterface $knowledgeDocumentIndexResponseBuilder,
        protected AiCommerceEntityManagerInterface $aiCommerceEntityManager,
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function indexDocumentContent(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer,
        KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin,
    ): KnowledgeDocumentIndexResponseTransfer {
        $maxContentSize = $this->getMaxContentSize();

        $knowledgeDocumentTransfer
            ->setDocumentName($knowledgeDocumentSourceTransfer->getDocumentName())
            ->setLocaleName($knowledgeDocumentSourceTransfer->getLocaleName());

        $knowledgeDocumentContentTransfer = $knowledgeDocumentSourcePlugin->readDocumentContent(
            $knowledgeDocumentSourceTransfer->setMaxContentSize($maxContentSize),
        );

        if (!$knowledgeDocumentContentTransfer->getIsSuccessful()) {
            return $this->knowledgeDocumentIndexResponseBuilder->createFailureResponse(
                (bool)$knowledgeDocumentContentTransfer->getIsTemporaryFailure(),
                $knowledgeDocumentContentTransfer->getErrors(),
            );
        }

        if (strlen((string)$knowledgeDocumentContentTransfer->getContent()) > $maxContentSize) {
            return $this->knowledgeDocumentIndexResponseBuilder->createErrorResponse(sprintf(static::ERROR_MESSAGE_FILE_TOO_LARGE, $maxContentSize));
        }

        $contentHash = hash(static::CONTENT_HASH_ALGORITHM, (string)$knowledgeDocumentContentTransfer->getContent());
        $knowledgeDocumentContentTransfer->setKnowledgeDocumentSource($knowledgeDocumentSourceTransfer);

        if ($knowledgeDocumentTransfer->getContentHash() !== null && $knowledgeDocumentTransfer->getContentHash() === $contentHash) {
            return $this->refreshUnchangedDocumentStatus($knowledgeDocumentTransfer, $knowledgeDocumentContentTransfer, $contentHash);
        }

        return $this->sendDocumentContent($knowledgeDocumentTransfer, $knowledgeDocumentContentTransfer, $contentHash);
    }

    protected function getMaxContentSize(): int
    {
        return max(1, min($this->aiCommerceConfig->getKnowledgeDocumentMaxFileSize(), $this->knowledgeStorePlugin->getMaxDocumentSize()));
    }

    protected function sendDocumentContent(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer,
        string $contentHash,
    ): KnowledgeDocumentIndexResponseTransfer {
        $knowledgeDocumentIndexResponseTransfer = $this->knowledgeStorePlugin->indexDocument($knowledgeDocumentContentTransfer);

        if (!$knowledgeDocumentIndexResponseTransfer->getIsSuccessful()) {
            return $knowledgeDocumentIndexResponseTransfer;
        }

        $status = $knowledgeDocumentIndexResponseTransfer->getStatus();

        if (!in_array($status, [static::STATUS_INDEXED, static::STATUS_PROCESSING], true)) {
            return $this->knowledgeDocumentIndexResponseBuilder->createErrorResponse(static::ERROR_MESSAGE_NO_STATUS);
        }

        return $this->saveIndexedDocument(
            $knowledgeDocumentTransfer
                ->setContentHash($contentHash)
                ->setIndexedAt((new DateTime())->format(static::DATE_TIME_FORMAT)),
            $status,
        );
    }

    protected function refreshUnchangedDocumentStatus(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer,
        string $contentHash,
    ): KnowledgeDocumentIndexResponseTransfer {
        $knowledgeDocumentCollectionResponseTransfer = $this->knowledgeStorePlugin->refreshDocumentStatuses(
            (new KnowledgeDocumentCollectionTransfer())->addKnowledgeDocument(
                (new KnowledgeDocumentTransfer())->fromArray($knowledgeDocumentTransfer->toArray(), true),
            ),
        );

        if (!$knowledgeDocumentCollectionResponseTransfer->getIsSuccessful()) {
            return $this->knowledgeDocumentIndexResponseBuilder->createCollectionFailureResponse($knowledgeDocumentCollectionResponseTransfer);
        }

        foreach ($knowledgeDocumentCollectionResponseTransfer->getKnowledgeDocuments() as $refreshedKnowledgeDocumentTransfer) {
            if ($refreshedKnowledgeDocumentTransfer->getStatus() === static::STATUS_FAILED) {
                return $this->sendDocumentContent($knowledgeDocumentTransfer, $knowledgeDocumentContentTransfer, $contentHash);
            }

            if (in_array($refreshedKnowledgeDocumentTransfer->getStatus(), [static::STATUS_INDEXED, static::STATUS_PROCESSING], true)) {
                return $this->saveIndexedDocument($knowledgeDocumentTransfer, (string)$refreshedKnowledgeDocumentTransfer->getStatus());
            }
        }

        return $this->knowledgeDocumentIndexResponseBuilder->createErrorResponse(static::ERROR_MESSAGE_NO_STATUS);
    }

    protected function saveIndexedDocument(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        string $status,
    ): KnowledgeDocumentIndexResponseTransfer {
        $this->aiCommerceEntityManager->saveKnowledgeDocument(
            $knowledgeDocumentTransfer
                ->setStatus($status)
                ->setAttemptCount(0)
                ->setErrorMessage(null),
        );

        return $this->knowledgeDocumentIndexResponseBuilder->createSuccessResponse();
    }
}
