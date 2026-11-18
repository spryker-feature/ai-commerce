<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Indexer;

use Aws\BedrockAgent\BedrockAgentClient;
use Aws\Exception\AwsException;
use Generated\Shared\Transfer\BedrockKnowledgeBaseReferenceTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper\BedrockKnowledgeBaseDocumentMapperInterface;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper\BedrockKnowledgeBaseDocumentStatusMapperInterface;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Reader\BedrockKnowledgeBaseReferenceReaderInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class BedrockKnowledgeBaseDocumentIndexer implements BedrockKnowledgeBaseDocumentIndexerInterface
{
    protected const int MAX_DOCUMENTS_PER_REQUEST = 10;

    protected const int HTTP_STATUS_CODE_TOO_MANY_REQUESTS = 429;

    protected const int HTTP_STATUS_CODE_SERVER_ERROR = 500;

    protected const array TEMPORARY_AWS_ERROR_CODES = ['ThrottlingException', 'ServiceUnavailableException', 'InternalServerException'];

    protected const string ERROR_MESSAGE_NO_CONTENT = 'The document has no content.';

    protected const string ERROR_MESSAGE_CONTENT_TOO_LARGE = 'The document has %d bytes. The Amazon Bedrock knowledge base accepts at most %d bytes in one inline document.';

    protected const string ERROR_MESSAGE_NOT_SET_UP = 'The Amazon Bedrock knowledge base is not configured. Set the knowledge base ID.';

    protected const string ERROR_MESSAGE_DATA_SOURCE_NOT_CONFIGURED = 'No Amazon Bedrock data source is configured for the source type %s. Add its data source ID to BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS.';

    protected const string ERROR_MESSAGE_AWS_REQUEST_FAILED = 'Amazon Bedrock request failed: %s';

    protected const string ERROR_MESSAGE_NO_DOCUMENT_STATUS = 'The Amazon Bedrock knowledge base returned no document status.';

    public function __construct(
        protected BedrockAgentClient $bedrockAgentClient,
        protected BedrockKnowledgeBaseReferenceReaderInterface $bedrockKnowledgeBaseReferenceReader,
        protected BedrockKnowledgeBaseDocumentMapperInterface $bedrockKnowledgeBaseDocumentMapper,
        protected BedrockKnowledgeBaseDocumentStatusMapperInterface $bedrockKnowledgeBaseDocumentStatusMapper,
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function indexDocument(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer
    ): KnowledgeDocumentIndexResponseTransfer {
        $contentSize = strlen((string)$knowledgeDocumentContentTransfer->getContent());

        if ($contentSize === 0) {
            return $this->createIndexFailureResponse(false, static::ERROR_MESSAGE_NO_CONTENT);
        }

        $maxInlineDocumentSize = $this->aiCommerceConfig->getBedrockKnowledgeBaseMaxInlineDocumentSize();

        if ($contentSize > $maxInlineDocumentSize) {
            return $this->createIndexFailureResponse(false, sprintf(static::ERROR_MESSAGE_CONTENT_TOO_LARGE, $contentSize, $maxInlineDocumentSize));
        }

        $bedrockKnowledgeBaseReferenceTransfer = $this->bedrockKnowledgeBaseReferenceReader->getReference();

        if (!$this->isKnowledgeBaseConfigured($bedrockKnowledgeBaseReferenceTransfer)) {
            return $this->createIndexFailureResponse(false, static::ERROR_MESSAGE_NOT_SET_UP);
        }

        $sourceType = $knowledgeDocumentContentTransfer->getKnowledgeDocumentSourceOrFail()->getSourceTypeOrFail();
        $dataSourceId = $this->findDataSourceId($bedrockKnowledgeBaseReferenceTransfer, $sourceType);

        if ($dataSourceId === null) {
            return $this->createIndexFailureResponse(false, sprintf(static::ERROR_MESSAGE_DATA_SOURCE_NOT_CONFIGURED, $sourceType));
        }

        try {
            $documentDetails = (array)$this->bedrockAgentClient->ingestKnowledgeBaseDocuments([
                'knowledgeBaseId' => $bedrockKnowledgeBaseReferenceTransfer->getKnowledgeBaseIdOrFail(),
                'dataSourceId' => $dataSourceId,
                'documents' => [
                    $this->bedrockKnowledgeBaseDocumentMapper->mapKnowledgeDocumentContentToKnowledgeBaseDocument($knowledgeDocumentContentTransfer),
                ],
            ])->get('documentDetails');
        } catch (AwsException $awsException) {
            return $this->createIndexFailureResponse($this->isTemporaryFailure($awsException), $this->createAwsErrorMessage($awsException));
        }

        return $this->createIndexResponse($documentDetails[0] ?? []);
    }

    public function deleteDocuments(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer {
        if ($knowledgeDocumentCollectionTransfer->getKnowledgeDocuments()->count() === 0) {
            return (new KnowledgeDocumentCollectionResponseTransfer())->setIsSuccessful(true);
        }

        $bedrockKnowledgeBaseReferenceTransfer = $this->bedrockKnowledgeBaseReferenceReader->getReference();

        if (!$this->isKnowledgeBaseConfigured($bedrockKnowledgeBaseReferenceTransfer)) {
            return $this->createCollectionFailureResponse(static::ERROR_MESSAGE_NOT_SET_UP);
        }

        $unconfiguredSourceTypes = $this->getUnconfiguredSourceTypes($bedrockKnowledgeBaseReferenceTransfer, $knowledgeDocumentCollectionTransfer);

        if ($unconfiguredSourceTypes !== []) {
            return $this->createCollectionFailureResponse(sprintf(static::ERROR_MESSAGE_DATA_SOURCE_NOT_CONFIGURED, implode(', ', $unconfiguredSourceTypes)));
        }

        try {
            foreach ($this->groupDocumentKeysByDataSourceId($bedrockKnowledgeBaseReferenceTransfer, $knowledgeDocumentCollectionTransfer) as $dataSourceId => $documentKeys) {
                $this->deleteKnowledgeBaseDocumentChunks($bedrockKnowledgeBaseReferenceTransfer, (string)$dataSourceId, $documentKeys);
            }
        } catch (AwsException $awsException) {
            return $this->createCollectionFailureResponse($this->createAwsErrorMessage($awsException), $this->isTemporaryFailure($awsException));
        }

        return (new KnowledgeDocumentCollectionResponseTransfer())->setIsSuccessful(true);
    }

    public function refreshDocumentStatuses(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): KnowledgeDocumentCollectionResponseTransfer {
        $bedrockKnowledgeBaseReferenceTransfer = $this->bedrockKnowledgeBaseReferenceReader->getReference();

        if (!$this->isKnowledgeBaseConfigured($bedrockKnowledgeBaseReferenceTransfer)) {
            return $this->createCollectionFailureResponse(static::ERROR_MESSAGE_NOT_SET_UP);
        }

        $knowledgeDocumentTransfers = $this->indexDocumentsByDocumentKey($knowledgeDocumentCollectionTransfer);

        try {
            foreach ($this->groupDocumentKeysByDataSourceId($bedrockKnowledgeBaseReferenceTransfer, $knowledgeDocumentCollectionTransfer) as $dataSourceId => $documentKeys) {
                $this->refreshKnowledgeBaseDocumentChunkStatuses($bedrockKnowledgeBaseReferenceTransfer, (string)$dataSourceId, $documentKeys, $knowledgeDocumentTransfers);
            }
        } catch (AwsException $awsException) {
            return $this->createCollectionFailureResponse($this->createAwsErrorMessage($awsException), $this->isTemporaryFailure($awsException));
        }

        $knowledgeDocumentCollectionResponseTransfer = (new KnowledgeDocumentCollectionResponseTransfer())->setIsSuccessful(true);

        foreach ($knowledgeDocumentTransfers as $knowledgeDocumentTransfer) {
            if ($this->findDataSourceId($bedrockKnowledgeBaseReferenceTransfer, $knowledgeDocumentTransfer->getSourceTypeOrFail()) === null) {
                $knowledgeDocumentTransfer
                    ->setStatus(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED)
                    ->setErrorMessage(sprintf(static::ERROR_MESSAGE_DATA_SOURCE_NOT_CONFIGURED, $knowledgeDocumentTransfer->getSourceTypeOrFail()));
            }

            $knowledgeDocumentCollectionResponseTransfer->addKnowledgeDocument($knowledgeDocumentTransfer);
        }

        return $knowledgeDocumentCollectionResponseTransfer;
    }

    /**
     * @param list<string> $documentKeys
     */
    protected function deleteKnowledgeBaseDocumentChunks(
        BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer,
        string $dataSourceId,
        array $documentKeys
    ): void {
        foreach (array_chunk($documentKeys, max(1, static::MAX_DOCUMENTS_PER_REQUEST)) as $documentKeyChunk) {
            $this->bedrockAgentClient->deleteKnowledgeBaseDocuments(
                $this->createKnowledgeBaseDocumentsRequest($bedrockKnowledgeBaseReferenceTransfer, $dataSourceId, $documentKeyChunk),
            );
        }
    }

    /**
     * @param list<string> $documentKeys
     * @param array<string, \Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     */
    protected function refreshKnowledgeBaseDocumentChunkStatuses(
        BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer,
        string $dataSourceId,
        array $documentKeys,
        array $knowledgeDocumentTransfers
    ): void {
        foreach (array_chunk($documentKeys, max(1, static::MAX_DOCUMENTS_PER_REQUEST)) as $documentKeyChunk) {
            $this->applyKnowledgeBaseDocumentStatuses(
                $this->getKnowledgeBaseDocumentDetails($bedrockKnowledgeBaseReferenceTransfer, $dataSourceId, $documentKeyChunk),
                $knowledgeDocumentTransfers,
            );
        }
    }

    protected function findDataSourceId(BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer, string $sourceType): ?string
    {
        $dataSourceId = (string)($bedrockKnowledgeBaseReferenceTransfer->getDataSourceIdsBySourceType()[$sourceType] ?? '');

        return $dataSourceId !== '' ? $dataSourceId : null;
    }

    /**
     * @return list<string>
     */
    protected function getUnconfiguredSourceTypes(
        BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer,
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): array {
        $unconfiguredSourceTypes = [];

        foreach ($knowledgeDocumentCollectionTransfer->getKnowledgeDocuments() as $knowledgeDocumentTransfer) {
            $sourceType = $knowledgeDocumentTransfer->getSourceTypeOrFail();

            if ($this->findDataSourceId($bedrockKnowledgeBaseReferenceTransfer, $sourceType) === null) {
                $unconfiguredSourceTypes[$sourceType] = $sourceType;
            }
        }

        return array_values($unconfiguredSourceTypes);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function groupDocumentKeysByDataSourceId(
        BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer,
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): array {
        $documentKeysByDataSourceId = [];

        foreach ($this->indexDocumentsByDocumentKey($knowledgeDocumentCollectionTransfer) as $documentKey => $knowledgeDocumentTransfer) {
            $dataSourceId = $this->findDataSourceId($bedrockKnowledgeBaseReferenceTransfer, $knowledgeDocumentTransfer->getSourceTypeOrFail());

            if ($dataSourceId === null) {
                continue;
            }

            $documentKeysByDataSourceId[$dataSourceId][] = (string)$documentKey;
        }

        return $documentKeysByDataSourceId;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\KnowledgeDocumentTransfer>
     */
    protected function indexDocumentsByDocumentKey(
        KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer
    ): array {
        $knowledgeDocumentTransfers = [];

        foreach ($knowledgeDocumentCollectionTransfer->getKnowledgeDocuments() as $knowledgeDocumentTransfer) {
            $documentKey = sprintf(
                SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT,
                $knowledgeDocumentTransfer->getSourceTypeOrFail(),
                $knowledgeDocumentTransfer->getSourceIdentifierOrFail(),
            );
            $knowledgeDocumentTransfers[$documentKey] = $knowledgeDocumentTransfer;
        }

        return $knowledgeDocumentTransfers;
    }

    /**
     * @param list<string> $documentKeys
     *
     * @return list<array<string, mixed>>
     */
    protected function getKnowledgeBaseDocumentDetails(
        BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer,
        string $dataSourceId,
        array $documentKeys
    ): array {
        /** @var list<array<string, mixed>> $documentDetails */
        $documentDetails = (array)$this->bedrockAgentClient
            ->getKnowledgeBaseDocuments($this->createKnowledgeBaseDocumentsRequest($bedrockKnowledgeBaseReferenceTransfer, $dataSourceId, $documentKeys))
            ->get('documentDetails');

        return $documentDetails;
    }

    /**
     * @param list<string> $documentKeys
     *
     * @return array<string, mixed>
     */
    protected function createKnowledgeBaseDocumentsRequest(
        BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer,
        string $dataSourceId,
        array $documentKeys
    ): array {
        return [
            'knowledgeBaseId' => $bedrockKnowledgeBaseReferenceTransfer->getKnowledgeBaseIdOrFail(),
            'dataSourceId' => $dataSourceId,
            'documentIdentifiers' => array_map(
                static fn (string $documentKey): array => ['dataSourceType' => SharedAiCommerceConfig::BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_TYPE_CUSTOM, 'custom' => ['id' => $documentKey]],
                $documentKeys,
            ),
        ];
    }

    /**
     * @param list<array<string, mixed>> $documentDetails
     * @param array<string, \Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     */
    protected function applyKnowledgeBaseDocumentStatuses(array $documentDetails, array $knowledgeDocumentTransfers): void
    {
        foreach ($documentDetails as $documentDetail) {
            $knowledgeDocumentTransfer = $knowledgeDocumentTransfers[(string)($documentDetail['identifier']['custom']['id'] ?? '')] ?? null;

            if ($knowledgeDocumentTransfer === null) {
                continue;
            }

            $this->applyKnowledgeBaseDocumentStatus($documentDetail, $knowledgeDocumentTransfer);
        }
    }

    /**
     * @param array<string, mixed> $documentDetail
     */
    protected function applyKnowledgeBaseDocumentStatus(
        array $documentDetail,
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer
    ): KnowledgeDocumentTransfer {
        $knowledgeBaseDocumentStatus = (string)($documentDetail['status'] ?? '');
        $status = $this->bedrockKnowledgeBaseDocumentStatusMapper->mapKnowledgeBaseDocumentStatusToKnowledgeDocumentStatus($knowledgeBaseDocumentStatus);
        $knowledgeDocumentTransfer->setStatus($status);

        if ($status !== SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED) {
            return $knowledgeDocumentTransfer;
        }

        return $knowledgeDocumentTransfer->setErrorMessage(
            $this->bedrockKnowledgeBaseDocumentStatusMapper->createFailureMessage($knowledgeBaseDocumentStatus, $documentDetail['statusReason'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $documentDetail
     */
    protected function createIndexResponse(array $documentDetail): KnowledgeDocumentIndexResponseTransfer
    {
        $knowledgeBaseDocumentStatus = (string)($documentDetail['status'] ?? '');

        if ($knowledgeBaseDocumentStatus === '') {
            return $this->createIndexFailureResponse(true, static::ERROR_MESSAGE_NO_DOCUMENT_STATUS);
        }

        $status = $this->bedrockKnowledgeBaseDocumentStatusMapper->mapKnowledgeBaseDocumentStatusToKnowledgeDocumentStatus($knowledgeBaseDocumentStatus);

        if ($status === SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED) {
            return $this->createIndexFailureResponse(
                false,
                $this->bedrockKnowledgeBaseDocumentStatusMapper->createFailureMessage($knowledgeBaseDocumentStatus, $documentDetail['statusReason'] ?? null),
            );
        }

        return (new KnowledgeDocumentIndexResponseTransfer())
            ->setIsSuccessful(true)
            ->setStatus($status);
    }

    protected function isKnowledgeBaseConfigured(BedrockKnowledgeBaseReferenceTransfer $bedrockKnowledgeBaseReferenceTransfer): bool
    {
        return (string)$bedrockKnowledgeBaseReferenceTransfer->getKnowledgeBaseId() !== '';
    }

    protected function isTemporaryFailure(AwsException $awsException): bool
    {
        $statusCode = (int)$awsException->getStatusCode();

        return $awsException->isConnectionError()
            || $statusCode === static::HTTP_STATUS_CODE_TOO_MANY_REQUESTS
            || $statusCode >= static::HTTP_STATUS_CODE_SERVER_ERROR
            || in_array($awsException->getAwsErrorCode(), static::TEMPORARY_AWS_ERROR_CODES, true);
    }

    protected function createAwsErrorMessage(AwsException $awsException): string
    {
        return sprintf(static::ERROR_MESSAGE_AWS_REQUEST_FAILED, $awsException->getAwsErrorMessage() ?: $awsException->getMessage());
    }

    protected function createIndexFailureResponse(bool $isTemporaryFailure, string $message): KnowledgeDocumentIndexResponseTransfer
    {
        return (new KnowledgeDocumentIndexResponseTransfer())
            ->setIsSuccessful(false)
            ->setIsTemporaryFailure($isTemporaryFailure)
            ->addError((new ErrorTransfer())->setMessage($message));
    }

    protected function createCollectionFailureResponse(string $message, bool $isTemporaryFailure = false): KnowledgeDocumentCollectionResponseTransfer
    {
        return (new KnowledgeDocumentCollectionResponseTransfer())
            ->setIsSuccessful(false)
            ->setIsTemporaryFailure($isTemporaryFailure)
            ->addError((new ErrorTransfer())->setMessage($message));
    }
}
