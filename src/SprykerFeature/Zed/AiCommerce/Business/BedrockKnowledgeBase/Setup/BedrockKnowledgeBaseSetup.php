<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Setup;

use Aws\BedrockAgent\BedrockAgentClient;
use Aws\Exception\AwsException;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupRequestTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupResponseTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Exception\BedrockKnowledgeBaseSetupException;

class BedrockKnowledgeBaseSetup implements BedrockKnowledgeBaseSetupInterface
{
    protected const string KNOWLEDGE_STORE_NAME = 'Amazon Bedrock knowledge base';

    protected const string DATA_SOURCE_CONFIGURATION = 'dataSourceConfiguration';

    protected const string MANAGED_KNOWLEDGE_BASE_CONNECTOR_CONFIGURATION = 'managedKnowledgeBaseConnectorConfiguration';

    protected const string MEDIA_EXTRACTION_CONFIGURATION = 'mediaExtractionConfiguration';

    /**
     * @var list<string>
     */
    protected const array DATA_SOURCE_UPDATE_FIELDS = [
        'name',
        'description',
        'dataSourceConfiguration',
        'dataDeletionPolicy',
        'serverSideEncryptionConfiguration',
        'vectorIngestionConfiguration',
    ];

    protected const string KNOWLEDGE_BASE_STATUS_ACTIVE = 'ACTIVE';

    protected const array KNOWLEDGE_BASE_PENDING_STATUSES = ['CREATING', 'UPDATING'];

    protected const string DATA_SOURCE_STATUS_AVAILABLE = 'AVAILABLE';

    protected const array DATA_SOURCE_PENDING_STATUSES = ['CREATING', 'UPDATING'];

    protected const string MESSAGE_SKIPPED = 'Set BEDROCK_KNOWLEDGE_BASE_ID to the ID of an existing Amazon Bedrock knowledge base.';

    protected const string MESSAGE_KNOWLEDGE_BASE = 'Knowledge base: %s (%s).';

    protected const string MESSAGE_DATA_SOURCE = 'Data source: %s (%s).';

    protected const string STEP_REGION = 'AWS region: %s.';

    protected const string STEP_RECREATE_NOT_SUPPORTED = 'The option to recreate the index is not supported: the knowledge base is kept. Run the forced re-index to replace all documents.';

    protected const string STEP_KNOWLEDGE_BASE_CONFIGURED = 'The configured knowledge base %s is used.';

    protected const string STEP_KNOWLEDGE_BASE_ACTIVE = 'The knowledge base %s is ACTIVE.';

    protected const string STEP_DATA_SOURCE_CONFIGURED = 'The configured data source %s is used for the source types: %s.';

    protected const string STEP_DATA_SOURCE_AVAILABLE = 'The data source %s is AVAILABLE.';

    protected const string STEP_DATA_SOURCE_MEDIA_EXTRACTION_UPDATED = 'The media extraction configuration of the data source %s is updated.';

    protected const string ERROR_MESSAGE_DATA_SOURCE_IDS_NOT_CONFIGURED = 'Set BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS to the IDs of the custom data sources of the knowledge base %s, keyed by source type.';

    protected const string ERROR_MESSAGE_SOURCE_TYPE_DATA_SOURCE_ID_NOT_CONFIGURED = 'Add the source type %s to BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS, with the ID of a custom data source of the knowledge base %s.';

    protected const string SOURCE_TYPE_SEPARATOR = ', ';

    protected const string ERROR_MESSAGE_AWS_REQUEST_FAILED = 'Amazon Bedrock request failed: %s';

    protected const string ERROR_MESSAGE_KNOWLEDGE_BASE_STATUS = 'The knowledge base %s has the status %s. %s';

    protected const string ERROR_MESSAGE_KNOWLEDGE_BASE_TIMEOUT = 'The knowledge base %s is not ACTIVE after %d status checks.';

    protected const string ERROR_MESSAGE_DATA_SOURCE_STATUS = 'The data source %s has the status %s. %s';

    protected const string ERROR_MESSAGE_DATA_SOURCE_TIMEOUT = 'The data source %s is not AVAILABLE after %d status checks.';

    protected const string FAILURE_REASON_SEPARATOR = ' ';

    public function __construct(
        protected BedrockAgentClient $bedrockAgentClient,
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function setup(KnowledgeStoreSetupRequestTransfer $knowledgeStoreSetupRequestTransfer): KnowledgeStoreSetupResponseTransfer
    {
        $knowledgeStoreSetupResponseTransfer = (new KnowledgeStoreSetupResponseTransfer())
            ->setKnowledgeStoreName(static::KNOWLEDGE_STORE_NAME)
            ->setIsSuccessful(true)
            ->setIsSkipped(false);

        if ($this->aiCommerceConfig->getBedrockKnowledgeBaseId() === '') {
            return $knowledgeStoreSetupResponseTransfer
                ->setIsSkipped(true)
                ->addMessage(static::MESSAGE_SKIPPED);
        }

        $knowledgeStoreSetupResponseTransfer->addStep(sprintf(static::STEP_REGION, $this->aiCommerceConfig->getBedrockKnowledgeBaseAwsRegion()));

        if ($knowledgeStoreSetupRequestTransfer->getIsIndexRecreated() === true) {
            $knowledgeStoreSetupResponseTransfer->addStep(static::STEP_RECREATE_NOT_SUPPORTED);
        }

        $dataSourceIds = $this->aiCommerceConfig->getBedrockKnowledgeBaseDataSourceIds();

        if ($dataSourceIds === []) {
            return $this->addError(
                $knowledgeStoreSetupResponseTransfer,
                sprintf(static::ERROR_MESSAGE_DATA_SOURCE_IDS_NOT_CONFIGURED, $this->aiCommerceConfig->getBedrockKnowledgeBaseId()),
            );
        }

        foreach (array_diff($knowledgeStoreSetupRequestTransfer->getSourceTypes(), array_keys($dataSourceIds)) as $sourceType) {
            $this->addError(
                $knowledgeStoreSetupResponseTransfer,
                sprintf(static::ERROR_MESSAGE_SOURCE_TYPE_DATA_SOURCE_ID_NOT_CONFIGURED, $sourceType, $this->aiCommerceConfig->getBedrockKnowledgeBaseId()),
            );
        }

        if (!$knowledgeStoreSetupResponseTransfer->getIsSuccessful()) {
            return $knowledgeStoreSetupResponseTransfer;
        }

        try {
            return $this->setupKnowledgeBaseAndDataSource($knowledgeStoreSetupResponseTransfer);
        } catch (AwsException $awsException) {
            return $this->addError(
                $knowledgeStoreSetupResponseTransfer,
                sprintf(static::ERROR_MESSAGE_AWS_REQUEST_FAILED, $awsException->getAwsErrorMessage() ?: $awsException->getMessage()),
            );
        } catch (BedrockKnowledgeBaseSetupException $bedrockKnowledgeBaseSetupException) {
            return $this->addError($knowledgeStoreSetupResponseTransfer, $bedrockKnowledgeBaseSetupException->getMessage());
        }
    }

    protected function setupKnowledgeBaseAndDataSource(
        KnowledgeStoreSetupResponseTransfer $knowledgeStoreSetupResponseTransfer
    ): KnowledgeStoreSetupResponseTransfer {
        $knowledgeBaseId = $this->setupKnowledgeBase($knowledgeStoreSetupResponseTransfer);

        foreach ($this->groupSourceTypesByDataSourceId($this->aiCommerceConfig->getBedrockKnowledgeBaseDataSourceIds()) as $dataSourceId => $sourceTypes) {
            $this->setupDataSource($knowledgeBaseId, (string)$dataSourceId, $sourceTypes, $knowledgeStoreSetupResponseTransfer);
        }

        return $knowledgeStoreSetupResponseTransfer;
    }

    /**
     * @param array<string, string> $dataSourceIds
     *
     * @return array<string, list<string>>
     */
    protected function groupSourceTypesByDataSourceId(array $dataSourceIds): array
    {
        $sourceTypesByDataSourceId = [];

        foreach ($dataSourceIds as $sourceType => $dataSourceId) {
            $sourceTypesByDataSourceId[$dataSourceId][] = (string)$sourceType;
        }

        return $sourceTypesByDataSourceId;
    }

    protected function setupKnowledgeBase(KnowledgeStoreSetupResponseTransfer $knowledgeStoreSetupResponseTransfer): string
    {
        $knowledgeBaseId = $this->aiCommerceConfig->getBedrockKnowledgeBaseId();
        $knowledgeStoreSetupResponseTransfer->addStep(sprintf(static::STEP_KNOWLEDGE_BASE_CONFIGURED, $knowledgeBaseId));
        $knowledgeBaseName = $this->waitForKnowledgeBase($knowledgeBaseId);
        $knowledgeStoreSetupResponseTransfer
            ->addStep(sprintf(static::STEP_KNOWLEDGE_BASE_ACTIVE, $knowledgeBaseId))
            ->addMessage(sprintf(static::MESSAGE_KNOWLEDGE_BASE, $knowledgeBaseName, $knowledgeBaseId));

        return $knowledgeBaseId;
    }

    protected function waitForKnowledgeBase(string $knowledgeBaseId): string
    {
        $statusCheckAttempts = max(1, $this->aiCommerceConfig->getBedrockKnowledgeBaseSetupStatusCheckAttempts());

        for ($attempt = 1; $attempt <= $statusCheckAttempts; $attempt++) {
            $knowledgeBase = (array)$this->bedrockAgentClient->getKnowledgeBase(['knowledgeBaseId' => $knowledgeBaseId])->get('knowledgeBase');
            $status = (string)($knowledgeBase['status'] ?? '');

            if ($status === static::KNOWLEDGE_BASE_STATUS_ACTIVE) {
                return (string)($knowledgeBase['name'] ?? '');
            }

            if (!in_array($status, static::KNOWLEDGE_BASE_PENDING_STATUSES, true)) {
                throw new BedrockKnowledgeBaseSetupException(sprintf(
                    static::ERROR_MESSAGE_KNOWLEDGE_BASE_STATUS,
                    $knowledgeBaseId,
                    $status,
                    implode(static::FAILURE_REASON_SEPARATOR, (array)($knowledgeBase['failureReasons'] ?? [])),
                ));
            }

            $this->waitForNextStatusCheck($attempt, $statusCheckAttempts);
        }

        throw new BedrockKnowledgeBaseSetupException(sprintf(static::ERROR_MESSAGE_KNOWLEDGE_BASE_TIMEOUT, $knowledgeBaseId, $statusCheckAttempts));
    }

    /**
     * @param list<string> $sourceTypes
     */
    protected function setupDataSource(
        string $knowledgeBaseId,
        string $dataSourceId,
        array $sourceTypes,
        KnowledgeStoreSetupResponseTransfer $knowledgeStoreSetupResponseTransfer
    ): KnowledgeStoreSetupResponseTransfer {
        $knowledgeStoreSetupResponseTransfer->addStep(sprintf(static::STEP_DATA_SOURCE_CONFIGURED, $dataSourceId, implode(static::SOURCE_TYPE_SEPARATOR, $sourceTypes)));
        $dataSource = $this->waitForDataSource($knowledgeBaseId, $dataSourceId);

        if ($this->isMediaExtractionConfigurationUpdateRequired($dataSource)) {
            $this->bedrockAgentClient->updateDataSource($this->createUpdateDataSourceRequest($knowledgeBaseId, $dataSourceId, $dataSource));
            $knowledgeStoreSetupResponseTransfer->addStep(sprintf(static::STEP_DATA_SOURCE_MEDIA_EXTRACTION_UPDATED, $dataSourceId));
            $dataSource = $this->waitForDataSource($knowledgeBaseId, $dataSourceId);
        }

        return $knowledgeStoreSetupResponseTransfer
            ->addStep(sprintf(static::STEP_DATA_SOURCE_AVAILABLE, $dataSourceId))
            ->addMessage(sprintf(static::MESSAGE_DATA_SOURCE, (string)($dataSource['name'] ?? ''), $dataSourceId));
    }

    /**
     * @param array<string, mixed> $dataSource
     */
    protected function isMediaExtractionConfigurationUpdateRequired(array $dataSource): bool
    {
        $mediaExtractionConfiguration = $this->aiCommerceConfig->getBedrockKnowledgeBaseMediaExtractionConfiguration();

        if ($mediaExtractionConfiguration === []) {
            return false;
        }

        $currentMediaExtractionConfiguration = $this->getMediaExtractionConfiguration($dataSource);

        return array_replace_recursive($currentMediaExtractionConfiguration, $mediaExtractionConfiguration) !== $currentMediaExtractionConfiguration;
    }

    /**
     * @param array<string, mixed> $dataSource
     *
     * @return array<string, mixed>
     */
    protected function getMediaExtractionConfiguration(array $dataSource): array
    {
        return (array)($dataSource[static::DATA_SOURCE_CONFIGURATION][static::MANAGED_KNOWLEDGE_BASE_CONNECTOR_CONFIGURATION][static::MEDIA_EXTRACTION_CONFIGURATION] ?? []);
    }

    /**
     * @param array<string, mixed> $dataSource
     *
     * @return array<string, mixed>
     */
    protected function createUpdateDataSourceRequest(string $knowledgeBaseId, string $dataSourceId, array $dataSource): array
    {
        $updateDataSourceRequest = array_filter(
            array_intersect_key($dataSource, array_flip(static::DATA_SOURCE_UPDATE_FIELDS)),
            static fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '',
        );
        $updateDataSourceRequest[static::DATA_SOURCE_CONFIGURATION][static::MANAGED_KNOWLEDGE_BASE_CONNECTOR_CONFIGURATION][static::MEDIA_EXTRACTION_CONFIGURATION] = array_replace_recursive(
            $this->getMediaExtractionConfiguration($dataSource),
            $this->aiCommerceConfig->getBedrockKnowledgeBaseMediaExtractionConfiguration(),
        );

        return ['knowledgeBaseId' => $knowledgeBaseId, 'dataSourceId' => $dataSourceId] + $updateDataSourceRequest;
    }

    /**
     * @throws \SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Exception\BedrockKnowledgeBaseSetupException
     *
     * @return array<string, mixed>
     */
    protected function waitForDataSource(string $knowledgeBaseId, string $dataSourceId): array
    {
        $statusCheckAttempts = max(1, $this->aiCommerceConfig->getBedrockKnowledgeBaseSetupStatusCheckAttempts());

        for ($attempt = 1; $attempt <= $statusCheckAttempts; $attempt++) {
            $dataSource = (array)$this->bedrockAgentClient->getDataSource(['knowledgeBaseId' => $knowledgeBaseId, 'dataSourceId' => $dataSourceId])->get('dataSource');
            $status = (string)($dataSource['status'] ?? '');

            if ($status === static::DATA_SOURCE_STATUS_AVAILABLE) {
                return $dataSource;
            }

            if (!in_array($status, static::DATA_SOURCE_PENDING_STATUSES, true)) {
                throw new BedrockKnowledgeBaseSetupException(sprintf(
                    static::ERROR_MESSAGE_DATA_SOURCE_STATUS,
                    $dataSourceId,
                    $status,
                    implode(static::FAILURE_REASON_SEPARATOR, (array)($dataSource['failureReasons'] ?? [])),
                ));
            }

            $this->waitForNextStatusCheck($attempt, $statusCheckAttempts);
        }

        throw new BedrockKnowledgeBaseSetupException(sprintf(static::ERROR_MESSAGE_DATA_SOURCE_TIMEOUT, $dataSourceId, $statusCheckAttempts));
    }

    protected function waitForNextStatusCheck(int $attempt, int $statusCheckAttempts): void
    {
        if ($attempt === $statusCheckAttempts) {
            return;
        }

        sleep(max(0, $this->aiCommerceConfig->getBedrockKnowledgeBaseSetupStatusCheckInterval()));
    }

    protected function addError(KnowledgeStoreSetupResponseTransfer $knowledgeStoreSetupResponseTransfer, string $message): KnowledgeStoreSetupResponseTransfer
    {
        return $knowledgeStoreSetupResponseTransfer
            ->setIsSuccessful(false)
            ->addError((new ErrorTransfer())->setMessage($message));
    }
}
