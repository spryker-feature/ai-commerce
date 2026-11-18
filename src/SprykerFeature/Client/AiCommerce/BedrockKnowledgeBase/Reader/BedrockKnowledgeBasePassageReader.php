<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\BedrockKnowledgeBase\Reader;

use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;
use Aws\Exception\AwsException;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;
use Generated\Shared\Transfer\KnowledgePassageTransfer;
use Generated\Shared\Transfer\KnowledgeSearchConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeSearchCriteriaTransfer;
use Spryker\Shared\Http\Logger\ExternalHttpInMemoryLoggerTrait;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\BedrockKnowledgeBase\Mapper\BedrockKnowledgeBasePassageMapperInterface;
use SprykerFeature\Client\AiCommerce\BedrockKnowledgeBase\Query\BedrockKnowledgeBaseRetrievalFilterBuilderInterface;
use SprykerFeature\Shared\AiCommerce\BedrockKnowledgeBase\BedrockKnowledgeBaseRerankingModelType;

class BedrockKnowledgeBasePassageReader implements BedrockKnowledgeBasePassageReaderInterface
{
    use ExternalHttpInMemoryLoggerTrait;
    use LoggerTrait;

    protected const int MAX_NUMBER_OF_RESULTS = 100;

    protected const string RERANKING_CONFIGURATION_TYPE_BEDROCK_RERANKING_MODEL = 'BEDROCK_RERANKING_MODEL';

    protected const string ERROR_MESSAGE_INVALID_CRITERIA = 'Amazon Bedrock knowledge base search requires KnowledgeSearchConditions.queryText, at least one KnowledgeSearchConditions.documentKeys or KnowledgeSearchConditions.ids value, and a positive Pagination.limit.';

    protected const string ERROR_MESSAGE_NOT_SET_UP = 'The Amazon Bedrock knowledge base is not configured. Set the knowledge base ID.';

    protected const string ERROR_MESSAGE_INVALID_RERANKING_MODEL_TYPE = 'The reranking model type "%s" is not supported. Use one of: %s.';

    protected const string ERROR_MESSAGE_RERANKING_MODEL_ARN_MISSING = 'The reranking model type CUSTOM requires the reranking model ARN.';

    protected const string ERROR_MESSAGE_SEARCH_FAILED = 'Knowledge search failed.';

    protected const string LOG_MESSAGE_SEARCH_FAILED = 'Amazon Bedrock knowledge base search failed: %s';

    protected const string LOG_MESSAGE_RESULT_SKIPPED = 'Amazon Bedrock knowledge base result of the document %s is skipped: the content type %s has no text.';

    protected const string RETRIEVAL_RESULTS = 'retrievalResults';

    protected const string RETRIEVAL_RESULT_CONTENT = 'content';

    protected const string RETRIEVAL_RESULT_CONTENT_TYPE = 'type';

    protected const string RETRIEVAL_RESULT_CONTENT_TYPE_TEXT = 'TEXT';

    protected const string RETRIEVAL_RESULT_LOCATION = 'location';

    protected const string RETRIEVAL_RESULT_CUSTOM_DOCUMENT_LOCATION = 'customDocumentLocation';

    protected const string RETRIEVAL_RESULT_CUSTOM_DOCUMENT_ID = 'id';

    protected const string METADATA = '@metadata';

    protected const string EFFECTIVE_URI = 'effectiveUri';

    protected const string HTTP_METHOD_POST = 'POST';

    protected const string REQUEST_KEY_KNOWLEDGE_BASE_ID = 'knowledgeBaseId';

    protected const string REQUEST_KEY_RETRIEVAL_QUERY = 'retrievalQuery';

    protected const string REQUEST_KEY_TEXT = 'text';

    protected const string REQUEST_KEY_RETRIEVAL_CONFIGURATION = 'retrievalConfiguration';

    protected const string REQUEST_KEY_MANAGED_SEARCH_CONFIGURATION = 'managedSearchConfiguration';

    protected const string REQUEST_KEY_NUMBER_OF_RESULTS = 'numberOfResults';

    protected const string REQUEST_KEY_RERANKING_MODEL_TYPE = 'rerankingModelType';

    protected const string REQUEST_KEY_FILTER = 'filter';

    protected const string REQUEST_KEY_RERANKING_CONFIGURATION = 'rerankingConfiguration';

    protected const string REQUEST_KEY_TYPE = 'type';

    protected const string REQUEST_KEY_BEDROCK_RERANKING_CONFIGURATION = 'bedrockRerankingConfiguration';

    protected const string REQUEST_KEY_MODEL_CONFIGURATION = 'modelConfiguration';

    protected const string REQUEST_KEY_MODEL_ARN = 'modelArn';

    protected const string LOG_KEY_STATUS_CODE = 'statusCode';

    protected const string LOG_KEY_ERROR_CODE = 'errorCode';

    protected const string LOG_KEY_MESSAGE = 'message';

    protected const string LOG_KEY_EXCEPTION = 'exception';

    public function __construct(
        protected BedrockAgentRuntimeClient $bedrockAgentRuntimeClient,
        protected BedrockKnowledgeBaseReferenceReaderInterface $bedrockKnowledgeBaseReferenceReader,
        protected BedrockKnowledgeBaseRetrievalFilterBuilderInterface $bedrockKnowledgeBaseRetrievalFilterBuilder,
        protected BedrockKnowledgeBasePassageMapperInterface $bedrockKnowledgeBaseKnowledgePassageMapper,
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function getKnowledgePassageCollection(KnowledgeSearchCriteriaTransfer $knowledgeSearchCriteriaTransfer): KnowledgePassageCollectionTransfer
    {
        if (!$this->isCriteriaValid($knowledgeSearchCriteriaTransfer)) {
            return $this->createErrorCollection(static::ERROR_MESSAGE_INVALID_CRITERIA);
        }

        $validationErrorMessage = $this->validateRerankingConfiguration();

        if ($validationErrorMessage !== null) {
            return $this->createErrorCollection($validationErrorMessage);
        }

        $knowledgeBaseId = (string)$this->bedrockKnowledgeBaseReferenceReader->getReference()->getKnowledgeBaseId();

        if ($knowledgeBaseId === '') {
            return $this->createErrorCollection(static::ERROR_MESSAGE_NOT_SET_UP);
        }

        $retrieveRequest = $this->createRetrieveRequest($knowledgeBaseId, $knowledgeSearchCriteriaTransfer);

        try {
            $retrieveResult = $this->bedrockAgentRuntimeClient->retrieve($retrieveRequest);
        } catch (AwsException $awsException) {
            $this->getExternalHttpInMemoryLogger()->log(
                static::HTTP_METHOD_POST,
                (string)$awsException->getRequest()?->getUri(),
                $retrieveRequest,
                [
                    static::LOG_KEY_STATUS_CODE => $awsException->getStatusCode(),
                    static::LOG_KEY_ERROR_CODE => $awsException->getAwsErrorCode(),
                    static::LOG_KEY_MESSAGE => $awsException->getAwsErrorMessage(),
                ],
            );

            return $this->createErrorCollection(static::ERROR_MESSAGE_SEARCH_FAILED, $awsException);
        }

        $retrieveResultData = $retrieveResult->toArray();
        $this->getExternalHttpInMemoryLogger()->log(
            static::HTTP_METHOD_POST,
            (string)($retrieveResultData[static::METADATA][static::EFFECTIVE_URI] ?? ''),
            $retrieveRequest,
            array_diff_key($retrieveResultData, [static::METADATA => true]),
        );

        $knowledgePassageCollectionTransfer = (new KnowledgePassageCollectionTransfer())->setIsSuccessful(true);
        $knowledgePassageTransfers = $this->mapRetrievalResultsToKnowledgePassages((array)($retrieveResult[static::RETRIEVAL_RESULTS] ?? []));

        foreach ($knowledgePassageTransfers as $knowledgePassageTransfer) {
            $knowledgePassageCollectionTransfer->addKnowledgePassage($knowledgePassageTransfer);
        }

        return $knowledgePassageCollectionTransfer;
    }

    /**
     * @param array<array<string, mixed>> $retrievalResults
     *
     * @return list<\Generated\Shared\Transfer\KnowledgePassageTransfer>
     */
    protected function mapRetrievalResultsToKnowledgePassages(array $retrievalResults): array
    {
        $knowledgePassageTransfers = [];

        foreach (array_values($retrievalResults) as $position => $retrievalResult) {
            if (!$this->isTextRetrievalResult($retrievalResult)) {
                continue;
            }

            $knowledgePassageTransfers[] = $this->bedrockKnowledgeBaseKnowledgePassageMapper->mapRetrievalResultToKnowledgePassage(
                $retrievalResult,
                $position,
                new KnowledgePassageTransfer(),
            );
        }

        usort(
            $knowledgePassageTransfers,
            static fn (KnowledgePassageTransfer $left, KnowledgePassageTransfer $right): int => $right->getScore() <=> $left->getScore(),
        );

        return $knowledgePassageTransfers;
    }

    /**
     * @param array<string, mixed> $retrievalResult
     */
    protected function isTextRetrievalResult(array $retrievalResult): bool
    {
        $contentType = (string)($retrievalResult[static::RETRIEVAL_RESULT_CONTENT][static::RETRIEVAL_RESULT_CONTENT_TYPE] ?? static::RETRIEVAL_RESULT_CONTENT_TYPE_TEXT);

        if ($contentType === static::RETRIEVAL_RESULT_CONTENT_TYPE_TEXT) {
            return true;
        }

        $this->getLogger()->warning(sprintf(
            static::LOG_MESSAGE_RESULT_SKIPPED,
            (string)($retrievalResult[static::RETRIEVAL_RESULT_LOCATION][static::RETRIEVAL_RESULT_CUSTOM_DOCUMENT_LOCATION][static::RETRIEVAL_RESULT_CUSTOM_DOCUMENT_ID] ?? ''),
            $contentType,
        ));

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createRetrieveRequest(string $knowledgeBaseId, KnowledgeSearchCriteriaTransfer $knowledgeSearchCriteriaTransfer): array
    {
        $knowledgeSearchConditionsTransfer = $knowledgeSearchCriteriaTransfer->getKnowledgeSearchConditionsOrFail();

        return [
            static::REQUEST_KEY_KNOWLEDGE_BASE_ID => $knowledgeBaseId,
            static::REQUEST_KEY_RETRIEVAL_QUERY => [static::REQUEST_KEY_TEXT => trim((string)$knowledgeSearchConditionsTransfer->getQueryText())],
            static::REQUEST_KEY_RETRIEVAL_CONFIGURATION => [
                static::REQUEST_KEY_MANAGED_SEARCH_CONFIGURATION => $this->createManagedSearchConfiguration(
                    $knowledgeSearchConditionsTransfer,
                    min($knowledgeSearchCriteriaTransfer->getPaginationOrFail()->getLimitOrFail(), static::MAX_NUMBER_OF_RESULTS),
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createManagedSearchConfiguration(KnowledgeSearchConditionsTransfer $knowledgeSearchConditionsTransfer, int $numberOfResults): array
    {
        $rerankingModelType = $this->aiCommerceConfig->getBedrockKnowledgeBaseRerankingModelType();
        $managedSearchConfiguration = [
            static::REQUEST_KEY_NUMBER_OF_RESULTS => $numberOfResults,
            static::REQUEST_KEY_RERANKING_MODEL_TYPE => $rerankingModelType,
        ];
        $retrievalFilter = $this->bedrockKnowledgeBaseRetrievalFilterBuilder->buildRetrievalFilter($knowledgeSearchConditionsTransfer);

        if ($retrievalFilter !== []) {
            $managedSearchConfiguration[static::REQUEST_KEY_FILTER] = $retrievalFilter;
        }

        if ($rerankingModelType !== BedrockKnowledgeBaseRerankingModelType::Custom->value) {
            return $managedSearchConfiguration;
        }

        $managedSearchConfiguration[static::REQUEST_KEY_RERANKING_CONFIGURATION] = [
            static::REQUEST_KEY_TYPE => static::RERANKING_CONFIGURATION_TYPE_BEDROCK_RERANKING_MODEL,
            static::REQUEST_KEY_BEDROCK_RERANKING_CONFIGURATION => [
                static::REQUEST_KEY_MODEL_CONFIGURATION => [static::REQUEST_KEY_MODEL_ARN => $this->aiCommerceConfig->getBedrockKnowledgeBaseRerankingModelArn()],
            ],
        ];

        return $managedSearchConfiguration;
    }

    protected function validateRerankingConfiguration(): ?string
    {
        $configuredRerankingModelType = $this->aiCommerceConfig->getBedrockKnowledgeBaseRerankingModelType();
        $rerankingModelType = BedrockKnowledgeBaseRerankingModelType::tryFrom($configuredRerankingModelType);

        if ($rerankingModelType === null) {
            return sprintf(static::ERROR_MESSAGE_INVALID_RERANKING_MODEL_TYPE, $configuredRerankingModelType, implode(', ', $this->getSupportedRerankingModelTypes()));
        }

        if ($rerankingModelType === BedrockKnowledgeBaseRerankingModelType::Custom && $this->aiCommerceConfig->getBedrockKnowledgeBaseRerankingModelArn() === '') {
            return static::ERROR_MESSAGE_RERANKING_MODEL_ARN_MISSING;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function getSupportedRerankingModelTypes(): array
    {
        return array_map(
            static fn (BedrockKnowledgeBaseRerankingModelType $rerankingModelType): string => $rerankingModelType->value,
            BedrockKnowledgeBaseRerankingModelType::cases(),
        );
    }

    protected function isCriteriaValid(KnowledgeSearchCriteriaTransfer $knowledgeSearchCriteriaTransfer): bool
    {
        $knowledgeSearchConditionsTransfer = $knowledgeSearchCriteriaTransfer->getKnowledgeSearchConditions();
        $paginationTransfer = $knowledgeSearchCriteriaTransfer->getPagination();

        if ($knowledgeSearchConditionsTransfer === null || $paginationTransfer === null) {
            return false;
        }

        return trim((string)$knowledgeSearchConditionsTransfer->getQueryText()) !== ''
            && (int)$paginationTransfer->getLimit() > 0
            && $this->hasDocumentScope($knowledgeSearchConditionsTransfer);
    }

    protected function hasDocumentScope(KnowledgeSearchConditionsTransfer $knowledgeSearchConditionsTransfer): bool
    {
        $documentIdentifiers = [...$knowledgeSearchConditionsTransfer->getDocumentKeys(), ...$knowledgeSearchConditionsTransfer->getIds()];

        return array_filter($documentIdentifiers, static fn (string $documentIdentifier): bool => $documentIdentifier !== '') !== [];
    }

    protected function createErrorCollection(string $message, ?AwsException $awsException = null): KnowledgePassageCollectionTransfer
    {
        $this->logError($message, $awsException);

        return (new KnowledgePassageCollectionTransfer())
            ->setIsSuccessful(false)
            ->addError((new ErrorTransfer())->setMessage($message));
    }

    protected function logError(string $message, ?AwsException $awsException): void
    {
        if ($awsException === null) {
            $this->getLogger()->error($message);

            return;
        }

        $this->getLogger()->error(sprintf(static::LOG_MESSAGE_SEARCH_FAILED, $awsException->getMessage()), [static::LOG_KEY_EXCEPTION => $awsException]);
    }
}
