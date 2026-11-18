<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore;

use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupRequestTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupResponseTransfer;

interface KnowledgeStorePluginInterface
{
    /**
     * Specification:
     * - Checks and configures the resources of the knowledge store. Running it again changes nothing that is already correct.
     * - Returns an error when the store cannot accept the documents of a source type of `KnowledgeStoreSetupRequest.sourceTypes`.
     * - Returns `isSkipped = true` with the reason in `messages` when the knowledge store is not configured.
     * - Returns the steps that ran, the result in `messages`, and `isSuccessful = false` with errors on failure.
     *
     * @api
     */
    public function setupStore(KnowledgeStoreSetupRequestTransfer $knowledgeStoreSetupRequestTransfer): KnowledgeStoreSetupResponseTransfer;

    /**
     * Specification:
     * - Requires `KnowledgeDocumentContent.knowledgeDocumentSource`, the raw content and the MIME type.
     * - Replaces the document `{sourceType}:{sourceIdentifier}` in the knowledge store.
     * - Returns `isSuccessful = true` with the status `indexed` when the store has the document, or `processing` when the store indexes it later.
     * - Returns `isSuccessful = false` with errors on failure. Sets `isTemporaryFailure = true` when a retry can succeed.
     *
     * @api
     */
    public function indexDocument(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer
    ): KnowledgeDocumentIndexResponseTransfer;

    /**
     * Specification:
     * - Deletes the documents `{sourceType}:{sourceIdentifier}` of the collection from the knowledge store.
     * - A document that the store does not have is not an error.
     * - Returns `isSuccessful = false` with errors when the store fails. Sets `isTemporaryFailure = false` when a retry cannot succeed.
     *
     * @api
     */
    public function deleteDocuments(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer;

    /**
     * Specification:
     * - Reads the store status of each document of the collection.
     * - Returns the documents with the status `indexed`, `processing` or `failed`. Sets the error message of a `failed` document.
     * - Returns `isSuccessful = false` with errors when the store fails. Sets `isTemporaryFailure = false` when a retry cannot succeed.
     *
     * @api
     */
    public function refreshDocumentStatuses(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer;

    /**
     * Specification:
     * - Returns the maximum number of content bytes of one document that the knowledge store accepts.
     * - The indexer does not download more content than this size.
     *
     * @api
     */
    public function getMaxDocumentSize(): int;

    /**
     * Specification:
     * - Returns `true` when the knowledge store is configured and can accept the documents of the source type.
     * - Returns `false` otherwise. The indexer then does not queue, download or index the documents of the source type.
     * - Does not connect to the knowledge store.
     *
     * @api
     */
    public function isConfigured(string $sourceType): bool;
}
