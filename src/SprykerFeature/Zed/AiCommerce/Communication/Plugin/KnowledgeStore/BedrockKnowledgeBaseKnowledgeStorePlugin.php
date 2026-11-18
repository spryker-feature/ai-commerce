<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeStore;

use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupRequestTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupResponseTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;

/**
 * - Writes to an Amazon Bedrock managed knowledge base with a custom data source. The knowledge base parses, splits and embeds the raw files, PDF and Office files included.
 * - Use it with `BedrockKnowledgeBaseKnowledgeSearchPlugin` as the knowledge search plugin of the AiCommerce client.
 *
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class BedrockKnowledgeBaseKnowledgeStorePlugin extends AbstractPlugin implements KnowledgeStorePluginInterface
{
    /**
     * {@inheritDoc}
     * - The knowledge base and its custom data sources are a precondition: the setup does not create them.
     * - Skips the setup when `AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_ID` is not set.
     * - Returns an error when `AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS` is empty, or has no data source for a source type of `KnowledgeStoreSetupRequest.sourceTypes`.
     * - Waits until the configured knowledge base is `ACTIVE` and each configured data source is `AVAILABLE`.
     * - Updates the media extraction configuration of each data source when it differs from the configuration.
     * - Stores nothing.
     * - Does not delete the knowledge base when `isIndexRecreated = true`: adds a step instead.
     *
     * @api
     */
    public function setupStore(KnowledgeStoreSetupRequestTransfer $knowledgeStoreSetupRequestTransfer): KnowledgeStoreSetupResponseTransfer
    {
        return $this->getBusinessFactory()
            ->createBedrockKnowledgeBaseSetup()
            ->setup($knowledgeStoreSetupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     * - Sends one `IngestKnowledgeBaseDocuments` request to the data source of the source type of the document. The custom document ID is the document key, so a new version replaces the old one.
     * - Sends text MIME types with valid UTF-8 as inline text, other files as inline bytes with their MIME type.
     * - Adds the inline metadata `document_key`, `source_type`, `locale` (`*` for all locales), `document_name`, `sourceIdentifier` and the scalar source metadata.
     * - Fails permanently when the content is empty or larger than `AiCommerceConfig::getBedrockKnowledgeBaseMaxInlineDocumentSize()`, when the knowledge base is not set up, or when the source type has no data source.
     * - Fails temporarily on a connection error, HTTP 429, HTTP 5xx or throttling.
     * - Returns the status `processing`: the knowledge base indexes the document later.
     *
     * @api
     */
    public function indexDocument(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer
    ): KnowledgeDocumentIndexResponseTransfer {
        return $this->getBusinessFactory()
            ->createBedrockKnowledgeBaseDocumentIndexer()
            ->indexDocument($knowledgeDocumentContentTransfer);
    }

    /**
     * {@inheritDoc}
     * - Sends `DeleteKnowledgeBaseDocuments` requests to the data source of each source type, with the document keys as custom document IDs, 10 documents per request.
     * - Returns an error and sends no request when the knowledge base is not set up or a source type has no data source. Sets `isTemporaryFailure = false` for these errors.
     * - Sets `isTemporaryFailure = true` on a connection error, HTTP 429, HTTP 5xx or throttling.
     *
     * @api
     */
    public function deleteDocuments(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer {
        return $this->getBusinessFactory()
            ->createBedrockKnowledgeBaseDocumentIndexer()
            ->deleteDocuments($knowledgeDocumentCollectionTransfer);
    }

    /**
     * {@inheritDoc}
     * - Reads the document statuses with `GetKnowledgeBaseDocuments` from the data source of each source type, 10 documents per request.
     * - Sets the status `failed` with an error message on the documents of a source type without a data source.
     * - Maps `INDEXED`, `PARTIALLY_INDEXED` and `METADATA_PARTIALLY_INDEXED` to `indexed`; `PENDING`, `STARTING` and `IN_PROGRESS` to `processing`; all other statuses to `failed` with the status reason in `errorMessage`.
     * - Keeps the status of a document that the response does not contain.
     * - Returns an error with `isTemporaryFailure = false` when the knowledge base is not set up. Sets `isTemporaryFailure = true` on a connection error, HTTP 429, HTTP 5xx or throttling.
     *
     * @api
     */
    public function refreshDocumentStatuses(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer {
        return $this->getBusinessFactory()
            ->createBedrockKnowledgeBaseDocumentIndexer()
            ->refreshDocumentStatuses($knowledgeDocumentCollectionTransfer);
    }

    /**
     * {@inheritDoc}
     * - Returns `AiCommerceConfig::getBedrockKnowledgeBaseMaxInlineDocumentSize()`, the limit of the inline content of one `IngestKnowledgeBaseDocuments` document.
     *
     * @api
     */
    public function getMaxDocumentSize(): int
    {
        return $this->getConfig()->getBedrockKnowledgeBaseMaxInlineDocumentSize();
    }

    /**
     * {@inheritDoc}
     * - Returns `true` when `AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_ID` is set and `AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS` has a data source ID for the source type.
     *
     * @api
     */
    public function isConfigured(string $sourceType): bool
    {
        return $this->getBusinessFactory()
            ->createBedrockKnowledgeBaseConfigurationChecker()
            ->isSourceTypeConfigured($sourceType);
    }
}
