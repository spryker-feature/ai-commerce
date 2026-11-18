<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\KnowledgeDocument;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Queue\QueueClientInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeStore\BedrockKnowledgeBaseKnowledgeStorePlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group KnowledgeDocument
 * @group IndexKnowledgeDocumentsWithBedrockKnowledgeBaseTest
 * Add your own group annotations below this line
 */
class IndexKnowledgeDocumentsWithBedrockKnowledgeBaseTest extends Unit
{
    protected const string SOURCE_TYPE = 'test_source';

    protected const string DOCUMENT_NAME = 'Installation manual';

    protected const string LOCALE_NAME = 'de_DE';

    protected const string KNOWLEDGE_BASE_ID = 'KB12345678';

    protected const string DATA_SOURCE_ID = 'DS12345678';

    protected const string OTHER_SOURCE_TYPE = 'other_source';

    protected const string OTHER_DATA_SOURCE_ID = 'DS87654321';

    protected const string MIME_TYPE_TEXT_PLAIN = 'text/plain';

    protected const string MIME_TYPE_PDF = 'application/pdf';

    protected const string TEXT_CONTENT = 'Clean the filter every month.';

    protected const string PDF_CONTENT = "%PDF-1.7\n\x00\xFF binary";

    protected AiCommerceBusinessTester $tester;

    protected KnowledgeDocumentSourcePluginInterface&MockObject $sourcePluginMock;

    /**
     * @var list<\Generated\Shared\Transfer\QueueSendMessageTransfer>
     */
    protected array $sentQueueSendMessageTransfers = [];

    public function testGivenTextDocumentWhenIndexingThenTextIsIngestedAndDocumentIsProcessing(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([$this->createIngestResult('STARTING')]);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING, $knowledgeDocumentTransfer->getStatus());
        $this->assertNotEmpty($knowledgeDocumentTransfer->getContentHash());

        $ingestedDocument = $this->tester->getBedrockAgentCommands()[0]->toArray()['documents'][0];
        $this->assertSame($this->createDocumentKey($sourceIdentifier), $ingestedDocument['content']['custom']['customDocumentIdentifier']['id']);
        $this->assertSame(['type' => 'TEXT', 'textContent' => ['data' => static::TEXT_CONTENT]], $ingestedDocument['content']['custom']['inlineContent']);
        $this->assertSame([
            'sourceIdentifier' => $sourceIdentifier,
            'document_key' => $this->createDocumentKey($sourceIdentifier),
            'source_type' => static::SOURCE_TYPE,
            'locale' => static::LOCALE_NAME,
            'document_name' => static::DOCUMENT_NAME,
        ], $this->mapInlineAttributesToStringValues($ingestedDocument['metadata']['inlineAttributes']));
    }

    public function testGivenPdfDocumentWhenIndexingThenPdfIsIngestedAsBytes(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::PDF_CONTENT, static::MIME_TYPE_PDF, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([$this->createIngestResult('IN_PROGRESS')]);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $inlineContent = $this->tester->getBedrockAgentCommands()[0]->toArray()['documents'][0]['content']['custom']['inlineContent'];
        $this->assertSame('BYTE', $inlineContent['type']);
        $this->assertSame(static::MIME_TYPE_PDF, $inlineContent['byteContent']['mimeType']);
        $this->assertSame(static::PDF_CONTENT, $inlineContent['byteContent']['data']);
    }

    public function testGivenDuplicateMessagesWhenIndexingThenDocumentIsIngestedOnce(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([$this->createIngestResult('STARTING')]);

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());
        $this->assertTrue($queueReceiveMessageTransfers[1]->getAcknowledge());
        $this->assertSame(['IngestKnowledgeBaseDocuments'], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenDocumentLargerThanInlineLimitWhenIndexingThenDocumentIsFailed(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseMaxInlineDocumentSize', 10);
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED, $knowledgeDocumentTransfer->getStatus());
        $this->assertStringContainsString('at most 10 bytes', (string)$knowledgeDocumentTransfer->getErrorMessage());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenUnconfiguredKnowledgeBaseWhenIndexingThenMessageIsAcknowledgedWithoutTrackingRow(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, '');
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());
        $this->assertNull($this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier));
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
    }

    public function testGivenSourceTypeWithoutDataSourceWhenIndexingThenMessageIsAcknowledgedWithoutTrackingRow(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID, [static::OTHER_SOURCE_TYPE => static::OTHER_DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());
        $this->assertNull($this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier));
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
    }

    /**
     * @dataProvider provideKnowledgeBaseConfigurations
     *
     * @param array<string, string> $dataSourceIds
     */
    public function testGivenKnowledgeBaseConfigurationWhenCheckingSourceTypeThenStoreIsConfiguredOnlyWithKnowledgeBaseAndDataSource(
        string $knowledgeBaseId,
        array $dataSourceIds,
        bool $expectedIsConfigured
    ): void {
        // Arrange
        $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, $knowledgeBaseId, $dataSourceIds);

        // Act
        $isConfigured = $this->createBedrockKnowledgeBaseKnowledgeStorePlugin()->isConfigured(static::SOURCE_TYPE);

        // Assert
        $this->assertSame($expectedIsConfigured, $isConfigured);
    }

    /**
     * @return array<string, array<string|array<string, string>|bool>>
     */
    public function provideKnowledgeBaseConfigurations(): array
    {
        return [
            'knowledge base and data source of the source type' => [static::KNOWLEDGE_BASE_ID, [static::SOURCE_TYPE => static::DATA_SOURCE_ID], true],
            'no knowledge base ID' => ['', [static::SOURCE_TYPE => static::DATA_SOURCE_ID], false],
            'no data source ID' => [static::KNOWLEDGE_BASE_ID, [], false],
            'data source only for another source type' => [static::KNOWLEDGE_BASE_ID, [static::OTHER_SOURCE_TYPE => static::OTHER_DATA_SOURCE_ID], false],
            'empty data source ID of the source type' => [static::KNOWLEDGE_BASE_ID, [static::SOURCE_TYPE => ''], false],
        ];
    }

    public function testGivenThrottlingKnowledgeBaseWhenIndexingThenDocumentIsRequeued(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([$this->tester->createBedrockAwsException('ThrottlingException', 429)]);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PENDING, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(1, $knowledgeDocumentTransfer->getAttemptCount());
        $this->assertCount(1, $this->sentQueueSendMessageTransfers);
    }

    public function testGivenThrottledStatusRequestWhenIndexingUnchangedDocumentThenDocumentIsNotIngestedAgain(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([
            $this->createIngestResult('STARTING'),
            $this->tester->createBedrockAwsException('ThrottlingException', 429),
            $this->createDocumentStatusResult($sourceIdentifier, 'INDEXED'),
        ]);
        $this->indexDocument($sourceIdentifier);
        $this->indexDocument($sourceIdentifier);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $this->assertSame(
            ['IngestKnowledgeBaseDocuments', 'GetKnowledgeBaseDocuments', 'GetKnowledgeBaseDocuments'],
            $this->tester->getBedrockAgentCommandNames(),
        );
        $this->assertSame(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED,
            $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier)->getStatus(),
        );
    }

    /**
     * @dataProvider getProcessingKnowledgeBaseDocumentStatusDataProvider
     */
    public function testGivenProcessingKnowledgeBaseStatusWhenIndexingUnchangedDocumentThenDocumentStaysProcessingWithoutNewIngestion(
        string $knowledgeBaseDocumentStatus
    ): void {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([
            $this->createIngestResult('STARTING'),
            $this->createDocumentStatusResult($sourceIdentifier, $knowledgeBaseDocumentStatus),
        ]);
        $this->indexDocument($sourceIdentifier);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $this->assertSame(['IngestKnowledgeBaseDocuments', 'GetKnowledgeBaseDocuments'], $this->tester->getBedrockAgentCommandNames());
        $this->assertSame(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING,
            $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier)->getStatus(),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public function getProcessingKnowledgeBaseDocumentStatusDataProvider(): array
    {
        return [
            'in progress' => ['IN_PROGRESS'],
            // `TEXT_INDEXED` is not in the AWS SDK model.
            'text indexed, image extraction runs' => ['TEXT_INDEXED'],
        ];
    }

    public function testGivenFailedKnowledgeBaseStatusWhenIndexingUnchangedDocumentThenDocumentIsIngestedAgain(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([
            $this->createIngestResult('STARTING'),
            $this->createDocumentStatusResult($sourceIdentifier, 'FAILED'),
            $this->createIngestResult('STARTING'),
        ]);
        $this->indexDocument($sourceIdentifier);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $this->assertSame(
            ['IngestKnowledgeBaseDocuments', 'GetKnowledgeBaseDocuments', 'IngestKnowledgeBaseDocuments'],
            $this->tester->getBedrockAgentCommandNames(),
        );
        $this->assertSame(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING,
            $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier)->getStatus(),
        );
    }

    public function testGivenDeletedSourceWhenIndexingThenKnowledgeBaseDocumentAndTrackingRowAreDeleted(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->arrangeDeletedSource($sourceIdentifier);
        $this->tester->mockBedrockAgentClient([['documentDetails' => []]]);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $this->assertSame(['DeleteKnowledgeBaseDocuments'], $this->tester->getBedrockAgentCommandNames());
        $this->assertSame(static::DATA_SOURCE_ID, $this->tester->getBedrockAgentCommands()[0]->toArray()['dataSourceId']);
        $this->assertSame(
            [['dataSourceType' => 'CUSTOM', 'custom' => ['id' => $this->createDocumentKey($sourceIdentifier)]]],
            $this->tester->getBedrockAgentCommands()[0]->toArray()['documentIdentifiers'],
        );
        $this->assertNull($this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier));
    }

    public function testGivenThrottledDeleteWhenIndexingDeletedSourceThenTrackingRowIsKeptAndDocumentIsRequeued(): void
    {
        // Arrange
        $sourceIdentifier = $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->arrangeDeletedSource($sourceIdentifier);
        $this->tester->mockBedrockAgentClient([$this->tester->createBedrockAwsException('ThrottlingException', 429)]);

        // Act
        $this->indexDocument($sourceIdentifier);

        // Assert
        $this->assertNotNull($this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier));
        $this->assertCount(1, $this->sentQueueSendMessageTransfers);
    }

    public function testGivenTwelveDocumentsWhenDeletingThenEachRequestHasAtMostTenDocuments(): void
    {
        // Arrange
        $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([['documentDetails' => []], ['documentDetails' => []]]);
        $knowledgeDocumentCollectionTransfer = new KnowledgeDocumentCollectionTransfer();

        foreach (range(1, 12) as $sourceIdentifier) {
            $knowledgeDocumentCollectionTransfer->addKnowledgeDocument(
                (new KnowledgeDocumentTransfer())->setSourceType(static::SOURCE_TYPE)->setSourceIdentifier((string)$sourceIdentifier),
            );
        }

        // Act
        $knowledgeDocumentCollectionResponseTransfer = $this->createBedrockKnowledgeBaseKnowledgeStorePlugin()->deleteDocuments($knowledgeDocumentCollectionTransfer);

        // Assert
        $this->assertTrue($knowledgeDocumentCollectionResponseTransfer->getIsSuccessful());
        $this->assertSame(['DeleteKnowledgeBaseDocuments', 'DeleteKnowledgeBaseDocuments'], $this->tester->getBedrockAgentCommandNames());
        $this->assertCount(10, $this->tester->getBedrockAgentCommands()[0]->toArray()['documentIdentifiers']);
        $this->assertSame(
            [['dataSourceType' => 'CUSTOM', 'custom' => ['id' => $this->createDocumentKey('11')]], ['dataSourceType' => 'CUSTOM', 'custom' => ['id' => $this->createDocumentKey('12')]]],
            $this->tester->getBedrockAgentCommands()[1]->toArray()['documentIdentifiers'],
        );
    }

    public function testGivenDocumentsOfSeveralSourceTypesWhenDeletingThenEachDataSourceGetsItsDocuments(): void
    {
        // Arrange
        $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID, [
            static::SOURCE_TYPE => static::DATA_SOURCE_ID,
            static::OTHER_SOURCE_TYPE => static::OTHER_DATA_SOURCE_ID,
        ]);
        $this->tester->mockBedrockAgentClient([['documentDetails' => []], ['documentDetails' => []]]);

        // Act
        $knowledgeDocumentCollectionResponseTransfer = $this->createBedrockKnowledgeBaseKnowledgeStorePlugin()->deleteDocuments(
            (new KnowledgeDocumentCollectionTransfer())
                ->addKnowledgeDocument((new KnowledgeDocumentTransfer())->setSourceType(static::SOURCE_TYPE)->setSourceIdentifier('1'))
                ->addKnowledgeDocument((new KnowledgeDocumentTransfer())->setSourceType(static::OTHER_SOURCE_TYPE)->setSourceIdentifier('2')),
        );

        // Assert
        $this->assertTrue($knowledgeDocumentCollectionResponseTransfer->getIsSuccessful());
        $this->assertSame(static::DATA_SOURCE_ID, $this->tester->getBedrockAgentCommands()[0]->toArray()['dataSourceId']);
        $this->assertSame(
            [['dataSourceType' => 'CUSTOM', 'custom' => ['id' => $this->createDocumentKey('1')]]],
            $this->tester->getBedrockAgentCommands()[0]->toArray()['documentIdentifiers'],
        );
        $this->assertSame(static::OTHER_DATA_SOURCE_ID, $this->tester->getBedrockAgentCommands()[1]->toArray()['dataSourceId']);
        $this->assertSame(
            [['dataSourceType' => 'CUSTOM', 'custom' => ['id' => sprintf(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT, static::OTHER_SOURCE_TYPE, '2')]]],
            $this->tester->getBedrockAgentCommands()[1]->toArray()['documentIdentifiers'],
        );
    }

    public function testGivenSourceTypeWithoutDataSourceWhenDeletingThenErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, static::KNOWLEDGE_BASE_ID);
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $knowledgeDocumentCollectionResponseTransfer = $this->createBedrockKnowledgeBaseKnowledgeStorePlugin()->deleteDocuments(
            (new KnowledgeDocumentCollectionTransfer())
                ->addKnowledgeDocument((new KnowledgeDocumentTransfer())->setSourceType(static::SOURCE_TYPE)->setSourceIdentifier('1'))
                ->addKnowledgeDocument((new KnowledgeDocumentTransfer())->setSourceType(static::OTHER_SOURCE_TYPE)->setSourceIdentifier('2')),
        );

        // Assert
        $this->assertFalse($knowledgeDocumentCollectionResponseTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentCollectionResponseTransfer->getIsTemporaryFailure());
        $this->assertStringContainsString(static::OTHER_SOURCE_TYPE, $knowledgeDocumentCollectionResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenUnconfiguredKnowledgeBaseWhenDeletingThenErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $this->arrangeBedrockIndexer(static::TEXT_CONTENT, static::MIME_TYPE_TEXT_PLAIN, '');
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $knowledgeDocumentCollectionResponseTransfer = $this->createBedrockKnowledgeBaseKnowledgeStorePlugin()->deleteDocuments(
            (new KnowledgeDocumentCollectionTransfer())->addKnowledgeDocument(
                (new KnowledgeDocumentTransfer())->setSourceType(static::SOURCE_TYPE)->setSourceIdentifier('1'),
            ),
        );

        // Assert
        $this->assertFalse($knowledgeDocumentCollectionResponseTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentCollectionResponseTransfer->getIsTemporaryFailure());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    /**
     * @param array<string, string>|null $dataSourceIds
     */
    protected function arrangeBedrockIndexer(string $content, string $mimeType, string $knowledgeBaseId, ?array $dataSourceIds = null): string
    {
        $sourceIdentifier = uniqid('document-', true);
        $this->sentQueueSendMessageTransfers = [];

        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseId', $knowledgeBaseId);
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseDataSourceIds', $dataSourceIds ?? [static::SOURCE_TYPE => static::DATA_SOURCE_ID]);
        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_QUEUE, $this->createQueueClientMock());
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [$this->createSourcePluginMock($sourceIdentifier, $content, $mimeType)]);

        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createBedrockKnowledgeBaseKnowledgeStorePlugin());

        return $sourceIdentifier;
    }

    protected function arrangeDeletedSource(string $sourceIdentifier): void
    {
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifier,
            KnowledgeDocumentTransfer::STATUS => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED,
        ]);

        $sourcePluginMock = $this->createMock(KnowledgeDocumentSourcePluginInterface::class);
        $sourcePluginMock->method('getSourceType')->willReturn(static::SOURCE_TYPE);
        $sourcePluginMock->method('getDocumentSourceCollection')->willReturn(new KnowledgeDocumentSourceCollectionTransfer());
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [$sourcePluginMock]);
    }

    protected function createBedrockKnowledgeBaseKnowledgeStorePlugin(): BedrockKnowledgeBaseKnowledgeStorePlugin
    {
        $bedrockKnowledgeBaseKnowledgeStorePlugin = new BedrockKnowledgeBaseKnowledgeStorePlugin();
        $bedrockKnowledgeBaseKnowledgeStorePlugin->setBusinessFactory($this->tester->getFactory());

        return $bedrockKnowledgeBaseKnowledgeStorePlugin;
    }

    protected function indexDocument(string $sourceIdentifier): void
    {
        $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([$this->createQueueReceiveMessageTransfer($sourceIdentifier)]);
    }

    protected function createQueueReceiveMessageTransfer(string $sourceIdentifier): QueueReceiveMessageTransfer
    {
        return (new QueueReceiveMessageTransfer())->setQueueMessage(
            (new QueueSendMessageTransfer())->setBody((string)json_encode([
                'sourceType' => static::SOURCE_TYPE,
                'sourceIdentifier' => $sourceIdentifier,
            ])),
        );
    }

    protected function createSourcePluginMock(string $sourceIdentifier, string $content, string $mimeType): KnowledgeDocumentSourcePluginInterface
    {
        $knowledgeDocumentSourceTransfer = (new KnowledgeDocumentSourceTransfer())
            ->setSourceType(static::SOURCE_TYPE)
            ->setSourceIdentifier($sourceIdentifier)
            ->setDocumentName(static::DOCUMENT_NAME)
            ->setLocaleName(static::LOCALE_NAME);

        $sourcePluginMock = $this->createMock(KnowledgeDocumentSourcePluginInterface::class);
        $sourcePluginMock->method('getSourceType')->willReturn(static::SOURCE_TYPE);
        $sourcePluginMock->method('getDocumentSourceCollection')->willReturn(
            (new KnowledgeDocumentSourceCollectionTransfer())->addKnowledgeDocumentSource($knowledgeDocumentSourceTransfer),
        );
        $sourcePluginMock->method('readDocumentContent')->willReturn(
            (new KnowledgeDocumentContentTransfer())
                ->setKnowledgeDocumentSource($knowledgeDocumentSourceTransfer)
                ->setContent($content)
                ->setMimeType($mimeType)
                ->setIsSuccessful(true),
        );

        return $sourcePluginMock;
    }

    protected function createQueueClientMock(): QueueClientInterface
    {
        $queueClientMock = $this->createMock(QueueClientInterface::class);
        $queueClientMock->method('sendMessages')->willReturnCallback(function (string $queueName, array $queueSendMessageTransfers): void {
            $this->sentQueueSendMessageTransfers = array_merge($this->sentQueueSendMessageTransfers, $queueSendMessageTransfers);
        });

        return $queueClientMock;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createIngestResult(string $status): array
    {
        return ['documentDetails' => [['status' => $status]]];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createDocumentStatusResult(string $sourceIdentifier, string $status): array
    {
        return [
        'documentDetails' => [[
            'status' => $status,
            'identifier' => ['dataSourceType' => 'CUSTOM', 'custom' => ['id' => $this->createDocumentKey($sourceIdentifier)]],
        ]]];
    }

    protected function createDocumentKey(string $sourceIdentifier): string
    {
        return sprintf(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT, static::SOURCE_TYPE, $sourceIdentifier);
    }

    /**
     * @param list<array<string, mixed>> $inlineAttributes
     *
     * @return array<string, string>
     */
    protected function mapInlineAttributesToStringValues(array $inlineAttributes): array
    {
        return array_column(array_map(
            static fn (array $inlineAttribute): array => ['key' => $inlineAttribute['key'], 'value' => $inlineAttribute['value']['stringValue']],
            $inlineAttributes,
        ), 'value', 'key');
    }
}
