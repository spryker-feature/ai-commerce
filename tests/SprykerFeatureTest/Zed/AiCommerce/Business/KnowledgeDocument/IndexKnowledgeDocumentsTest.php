<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\KnowledgeDocument;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachmentQuery;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Spryker\Client\Queue\QueueClientInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentIndexer;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;
use SprykerFeatureTest\Zed\AiCommerce\Stub\ProductAttachmentKnowledgeDocumentSourcePluginStub;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group KnowledgeDocument
 * @group IndexKnowledgeDocumentsTest
 * Add your own group annotations below this line
 */
class IndexKnowledgeDocumentsTest extends Unit
{
    protected const string SOURCE_TYPE = 'test_source';

    protected const string DOCUMENT_NAME = 'Installation manual';

    protected const string DOCUMENT_CONTENT = 'First sentence of the manual. Second sentence of the manual.';

    protected const string MIME_TYPE_TEXT_PLAIN = 'text/plain';

    protected const string ERROR_MESSAGE_TIMEOUT = 'Connection timed out.';

    protected const string QUEUE_ROUTING_KEY_ERROR = 'error';

    protected const int MAX_ATTEMPTS = 3;

    protected const int MAX_FILE_SIZE = 10;

    protected const int STORE_MAX_DOCUMENT_SIZE = 1048576;

    protected const string ERROR_MESSAGE_STORE_NOT_CONFIGURED = 'The knowledge store is not configured.';

    protected const string EXCEPTION_MESSAGE_SOURCE_FAILED = 'The source database is not available.';

    protected AiCommerceBusinessTester $tester;

    protected KnowledgeDocumentSourcePluginInterface&MockObject $sourcePluginMock;

    protected KnowledgeStorePluginInterface&MockObject $knowledgeStorePluginMock;

    /**
     * @var list<\Generated\Shared\Transfer\QueueSendMessageTransfer>
     */
    protected array $sentQueueSendMessageTransfers = [];

    /**
     * @var list<string>
     */
    protected array $knowledgeStoreCalls = [];

    /**
     * @var list<int|null>
     */
    protected array $requestedMaxContentSizes = [];

    protected function _before(): void
    {
        parent::_before();

        $this->sentQueueSendMessageTransfers = [];
        $this->knowledgeStoreCalls = [];
        $this->requestedMaxContentSizes = [];

        $this->sourcePluginMock = $this->createMock(KnowledgeDocumentSourcePluginInterface::class);
        $this->sourcePluginMock->method('getSourceType')->willReturn(static::SOURCE_TYPE);
        $this->knowledgeStorePluginMock = $this->createKnowledgeStorePluginMock();

        $this->tester->mockConfigMethod('getKnowledgeDocumentIndexMaxAttempts', static::MAX_ATTEMPTS);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [$this->sourcePluginMock]);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->knowledgeStorePluginMock);
        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_QUEUE, $this->createQueueClientMock());
    }

    public function testGivenDeletedSourceWhenIndexingThenStoreDocumentAndTrackingRowAreDeleted(): void
    {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifier,
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_INDEXED,
        ]);
        $this->sourcePluginMock->method('getDocumentSourceCollection')->willReturn(new KnowledgeDocumentSourceCollectionTransfer());
        $this->sourcePluginMock->expects($this->never())->method('readDocumentContent');

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());
        $this->assertSame([sprintf('delete:%s:%s', static::SOURCE_TYPE, $sourceIdentifier)], $this->knowledgeStoreCalls);
        $this->assertNull($this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier));
    }

    public function testGivenTemporaryFailureWhenIndexingThenDocumentIsRequeued(): void
    {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $this->mockExistingSource($sourceIdentifier);
        $this->mockTemporaryContentFailure();

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());

        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_PENDING, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(1, $knowledgeDocumentTransfer->getAttemptCount());
        $this->assertSame(static::ERROR_MESSAGE_TIMEOUT, $knowledgeDocumentTransfer->getErrorMessage());
        $this->assertCount(1, $this->sentQueueSendMessageTransfers);
    }

    public function testGivenMaxAttemptsReachedWhenIndexingThenDocumentIsFailed(): void
    {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifier,
            KnowledgeDocumentTransfer::ATTEMPT_COUNT => static::MAX_ATTEMPTS - 1,
        ]);
        $this->mockExistingSource($sourceIdentifier);
        $this->mockTemporaryContentFailure();

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());

        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_FAILED, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(static::MAX_ATTEMPTS, $knowledgeDocumentTransfer->getAttemptCount());
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
    }

    public function testGivenContentLargerThanMaxFileSizeWhenIndexingThenDocumentIsFailed(): void
    {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $this->tester->mockConfigMethod('getKnowledgeDocumentMaxFileSize', static::MAX_FILE_SIZE);
        $this->mockExistingSource($sourceIdentifier);
        $this->mockSuccessfulContent(static::DOCUMENT_CONTENT);

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());

        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_FAILED, $knowledgeDocumentTransfer->getStatus());
        $this->assertNotEmpty($knowledgeDocumentTransfer->getErrorMessage());
        $this->assertNull($knowledgeDocumentTransfer->getContentHash());
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
        $this->assertSame([], $this->knowledgeStoreCalls);
    }

    public function testGivenMalformedMessageWhenIndexingThenMessageIsRejectedToErrorQueue(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = (new QueueReceiveMessageTransfer())
            ->setQueueMessage((new QueueSendMessageTransfer())->setBody('{"unexpected":true}'));

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([$queueReceiveMessageTransfer]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getReject());
        $this->assertTrue($queueReceiveMessageTransfers[0]->getHasError());
        $this->assertNotTrue($queueReceiveMessageTransfers[0]->getAcknowledge());
        $this->assertSame(static::QUEUE_ROUTING_KEY_ERROR, $queueReceiveMessageTransfers[0]->getRoutingKey());
    }

    public function testGivenSmallerStoreLimitWhenIndexingThenSourceGetsStoreLimitAndLargerContentFails(): void
    {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $knowledgeStorePluginMock = $this->createKnowledgeStorePluginMock(static::MAX_FILE_SIZE);
        $knowledgeStorePluginMock->expects($this->never())->method('indexDocument');
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $knowledgeStorePluginMock);
        $this->mockExistingSource($sourceIdentifier);
        $this->mockSuccessfulContent(static::DOCUMENT_CONTENT);

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([$this->createQueueReceiveMessageTransfer($sourceIdentifier)]);

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame([static::MAX_FILE_SIZE], $this->requestedMaxContentSizes);
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_FAILED, $knowledgeDocumentTransfer->getStatus());
    }

    public function testGivenIndexingExceptionForOneMessageWhenIndexingThenOnlyThatMessageIsRejected(): void
    {
        // Arrange
        $failingSourceIdentifier = uniqid('failing-', true);
        $deletedSourceIdentifier = uniqid('deleted-', true);
        $this->sourcePluginMock->method('getDocumentSourceCollection')->willReturnCallback(
            function (KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer) use ($failingSourceIdentifier): KnowledgeDocumentSourceCollectionTransfer {
                if ($knowledgeDocumentSourceCriteriaTransfer->getKnowledgeDocumentSourceConditionsOrFail()->getSourceIdentifiers() === [$failingSourceIdentifier]) {
                    throw new RuntimeException(static::EXCEPTION_MESSAGE_SOURCE_FAILED);
                }

                return new KnowledgeDocumentSourceCollectionTransfer();
            },
        );

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($failingSourceIdentifier),
            $this->createQueueReceiveMessageTransfer($deletedSourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getReject());
        $this->assertSame(static::QUEUE_ROUTING_KEY_ERROR, $queueReceiveMessageTransfers[0]->getRoutingKey());
        $this->assertTrue($queueReceiveMessageTransfers[1]->getAcknowledge());
        $this->assertSame([sprintf('delete:%s:%s', static::SOURCE_TYPE, $deletedSourceIdentifier)], $this->knowledgeStoreCalls);
    }

    public function testGivenUnconfiguredKnowledgeStoreWhenIndexingThenMessageIsAcknowledgedWithoutDownloadAndPendingRowIsKept(): void
    {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $knowledgeStorePluginMock = $this->createKnowledgeStorePluginMock(isConfigured: false);
        $knowledgeStorePluginMock->expects($this->never())->method('indexDocument');
        $knowledgeStorePluginMock->expects($this->never())->method('deleteDocuments');
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $knowledgeStorePluginMock);
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifier,
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_PENDING,
            KnowledgeDocumentTransfer::ATTEMPT_COUNT => 0,
        ]);
        $this->sourcePluginMock->expects($this->never())->method('getDocumentSourceCollection');
        $this->sourcePluginMock->expects($this->never())->method('readDocumentContent');

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());
        $this->assertNotTrue($queueReceiveMessageTransfers[0]->getReject());
        $this->assertNotTrue($queueReceiveMessageTransfers[0]->getHasError());

        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_PENDING, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(0, $knowledgeDocumentTransfer->getAttemptCount());
        $this->assertNull($knowledgeDocumentTransfer->getErrorMessage());
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
    }

    public function testGivenUnconfiguredKnowledgeStoreAndNoTrackingRowWhenIndexingThenNoRowIsCreated(): void
    {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock(isConfigured: false));
        $this->sourcePluginMock->expects($this->never())->method('readDocumentContent');

        // Act
        $queueReceiveMessageTransfers = $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([
            $this->createQueueReceiveMessageTransfer($sourceIdentifier),
        ]);

        // Assert
        $this->assertTrue($queueReceiveMessageTransfers[0]->getAcknowledge());
        $this->assertNull($this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier));
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
    }

    public function testGivenSelectedProductAttachmentWhenIndexingThenContentIsReadAndIndexedOnce(): void
    {
        // Arrange
        $sourceIdentifier = $this->haveProductAttachmentKnowledgeDocument(true, KnowledgeDocumentIndexer::STATUS_PENDING);
        $productAttachmentSourcePluginStub = $this->setProductAttachmentSourcePluginStub();
        $this->knowledgeStorePluginMock->expects($this->once())->method('indexDocument')->willReturn($this->createIndexedResponseTransfer());

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([$this->createProductAttachmentQueueReceiveMessageTransfer($sourceIdentifier)]);

        // Assert
        $this->assertSame([$sourceIdentifier], $productAttachmentSourcePluginStub->getReadSourceIdentifiers());
        $this->assertSame([], $this->knowledgeStoreCalls);
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_INDEXED, $this->findProductAttachmentKnowledgeDocument($sourceIdentifier)?->getStatus());
    }

    public function testGivenNotSelectedProductAttachmentWhenIndexingThenContentIsNotReadAndStoreDocumentIsDeleted(): void
    {
        // Arrange
        $sourceIdentifier = $this->haveProductAttachmentKnowledgeDocument(false, KnowledgeDocumentIndexer::STATUS_INDEXED);
        $productAttachmentSourcePluginStub = $this->setProductAttachmentSourcePluginStub();
        $this->knowledgeStorePluginMock->expects($this->never())->method('indexDocument');

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([$this->createProductAttachmentQueueReceiveMessageTransfer($sourceIdentifier)]);

        // Assert
        $this->assertSame([], $productAttachmentSourcePluginStub->getReadSourceIdentifiers());
        $this->assertSame([$this->createDeleteCall($sourceIdentifier)], $this->knowledgeStoreCalls);
        $this->assertNull($this->findProductAttachmentKnowledgeDocument($sourceIdentifier));
    }

    public function testGivenSelectedProductAttachmentRemovedBeforeWorkerWhenIndexingThenContentIsNotReadAndStoreDocumentIsDeleted(): void
    {
        // Arrange
        $sourceIdentifier = $this->haveProductAttachmentKnowledgeDocument(true, KnowledgeDocumentIndexer::STATUS_PENDING);
        SpyProductAttachmentQuery::create()->filterByIdProductAttachment((int)$sourceIdentifier)->delete();
        $productAttachmentSourcePluginStub = $this->setProductAttachmentSourcePluginStub();

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([$this->createProductAttachmentQueueReceiveMessageTransfer($sourceIdentifier)]);

        // Assert
        $this->assertSame([], $productAttachmentSourcePluginStub->getReadSourceIdentifiers());
        $this->assertSame([$this->createDeleteCall($sourceIdentifier)], $this->knowledgeStoreCalls);
        $this->assertNull($this->findProductAttachmentKnowledgeDocument($sourceIdentifier));
    }

    /**
     * @dataProvider provideDeleteFailures
     */
    public function testGivenFailedDeleteWhenIndexingDeletedSourceThenDocumentIsRequeuedOnlyOnTemporaryFailure(
        ?bool $isTemporaryFailure,
        string $expectedStatus,
        int $expectedSentMessageCount
    ): void {
        // Arrange
        $sourceIdentifier = uniqid('document-', true);
        $knowledgeStorePluginMock = $this->createMock(KnowledgeStorePluginInterface::class);
        $knowledgeStorePluginMock->method('isConfigured')->willReturn(true);
        $knowledgeStorePluginMock->method('deleteDocuments')->willReturn(
            (new KnowledgeDocumentCollectionResponseTransfer())
                ->setIsSuccessful(false)
                ->setIsTemporaryFailure($isTemporaryFailure)
                ->addError((new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_STORE_NOT_CONFIGURED)),
        );
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $knowledgeStorePluginMock);
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifier,
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_INDEXED,
        ]);
        $this->sourcePluginMock->method('getDocumentSourceCollection')->willReturn(new KnowledgeDocumentSourceCollectionTransfer());

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentIndexer()->indexDocuments([$this->createQueueReceiveMessageTransfer($sourceIdentifier)]);

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
        $this->assertSame($expectedStatus, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(static::ERROR_MESSAGE_STORE_NOT_CONFIGURED, $knowledgeDocumentTransfer->getErrorMessage());
        $this->assertCount($expectedSentMessageCount, $this->sentQueueSendMessageTransfers);
    }

    /**
     * @return array<string, array<bool|string|int|null>>
     */
    public function provideDeleteFailures(): array
    {
        return [
            'permanent failure' => [false, KnowledgeDocumentIndexer::STATUS_FAILED, 0],
            'temporary failure' => [true, KnowledgeDocumentIndexer::STATUS_PENDING, 1],
            'failure type not set' => [null, KnowledgeDocumentIndexer::STATUS_PENDING, 1],
        ];
    }

    protected function haveProductAttachmentKnowledgeDocument(bool $isAssistantKnowledge, string $status): string
    {
        $sourceIdentifier = (string)$this->tester->haveProductAttachment($isAssistantKnowledge)->getIdProductAttachmentOrFail();
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifier,
            KnowledgeDocumentTransfer::STATUS => $status,
        ]);

        return $sourceIdentifier;
    }

    protected function setProductAttachmentSourcePluginStub(): ProductAttachmentKnowledgeDocumentSourcePluginStub
    {
        $productAttachmentSourcePluginStub = new ProductAttachmentKnowledgeDocumentSourcePluginStub(static::DOCUMENT_CONTENT);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [$productAttachmentSourcePluginStub]);

        return $productAttachmentSourcePluginStub;
    }

    protected function createIndexedResponseTransfer(): KnowledgeDocumentIndexResponseTransfer
    {
        return (new KnowledgeDocumentIndexResponseTransfer())
            ->setIsSuccessful(true)
            ->setStatus(KnowledgeDocumentIndexer::STATUS_INDEXED);
    }

    protected function createProductAttachmentQueueReceiveMessageTransfer(string $sourceIdentifier): QueueReceiveMessageTransfer
    {
        return (new QueueReceiveMessageTransfer())->setQueueMessage(
            (new QueueSendMessageTransfer())->setBody((string)json_encode([
                'sourceType' => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
                'sourceIdentifier' => $sourceIdentifier,
            ])),
        );
    }

    protected function createDeleteCall(string $sourceIdentifier): string
    {
        return sprintf('delete:%s:%s', SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT, $sourceIdentifier);
    }

    protected function findProductAttachmentKnowledgeDocument(string $sourceIdentifier): ?KnowledgeDocumentTransfer
    {
        return $this->tester->findKnowledgeDocument(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT, $sourceIdentifier);
    }

    protected function mockExistingSource(string $sourceIdentifier): void
    {
        $this->sourcePluginMock->method('getDocumentSourceCollection')->willReturn(
            (new KnowledgeDocumentSourceCollectionTransfer())->addKnowledgeDocumentSource(
                (new KnowledgeDocumentSourceTransfer())
                    ->setSourceType(static::SOURCE_TYPE)
                    ->setSourceIdentifier($sourceIdentifier)
                    ->setDocumentName(static::DOCUMENT_NAME),
            ),
        );
    }

    protected function mockSuccessfulContent(string $content): void
    {
        $this->sourcePluginMock->method('readDocumentContent')->willReturnCallback(
            function (KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer) use ($content): KnowledgeDocumentContentTransfer {
                $this->requestedMaxContentSizes[] = $knowledgeDocumentSourceTransfer->getMaxContentSize();

                return (new KnowledgeDocumentContentTransfer())
                    ->setKnowledgeDocumentSource($knowledgeDocumentSourceTransfer)
                    ->setContent($content)
                    ->setMimeType(static::MIME_TYPE_TEXT_PLAIN)
                    ->setIsSuccessful(true);
            },
        );
    }

    protected function mockTemporaryContentFailure(): void
    {
        $this->sourcePluginMock->method('readDocumentContent')->willReturn(
            (new KnowledgeDocumentContentTransfer())
                ->setIsSuccessful(false)
                ->setIsTemporaryFailure(true)
                ->addError((new ErrorTransfer())->setMessage(static::ERROR_MESSAGE_TIMEOUT)),
        );
    }

    protected function createKnowledgeStorePluginMock(
        int $maxDocumentSize = self::STORE_MAX_DOCUMENT_SIZE,
        bool $isConfigured = true,
    ): KnowledgeStorePluginInterface&MockObject {
        $knowledgeStorePluginMock = $this->createMock(KnowledgeStorePluginInterface::class);
        $knowledgeStorePluginMock->method('isConfigured')->willReturnCallback(
            fn (string $sourceType): bool => $isConfigured && in_array($sourceType, [static::SOURCE_TYPE, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT], true),
        );
        $knowledgeStorePluginMock->method('getMaxDocumentSize')->willReturn($maxDocumentSize);
        $knowledgeStorePluginMock->method('deleteDocuments')->willReturnCallback(
            function (KnowledgeDocumentCollectionTransfer $knowledgeDocumentCollectionTransfer): KnowledgeDocumentCollectionResponseTransfer {
                foreach ($knowledgeDocumentCollectionTransfer->getKnowledgeDocuments() as $knowledgeDocumentTransfer) {
                    $this->knowledgeStoreCalls[] = sprintf('delete:%s:%s', $knowledgeDocumentTransfer->getSourceType(), $knowledgeDocumentTransfer->getSourceIdentifier());
                }

                return (new KnowledgeDocumentCollectionResponseTransfer())->setIsSuccessful(true);
            },
        );

        return $knowledgeStorePluginMock;
    }

    protected function createQueueClientMock(): QueueClientInterface
    {
        $queueClientMock = $this->createMock(QueueClientInterface::class);
        $queueClientMock->method('sendMessages')->willReturnCallback(function (string $queueName, array $queueSendMessageTransfers): void {
            $this->sentQueueSendMessageTransfers = array_merge($this->sentQueueSendMessageTransfers, $queueSendMessageTransfers);
        });

        return $queueClientMock;
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
}
