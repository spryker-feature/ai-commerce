<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\KnowledgeDocument;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeDocumentReindexRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Spryker\Client\Queue\QueueClientInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentIndexer;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group KnowledgeDocument
 * @group ReindexKnowledgeDocumentsTest
 * Add your own group annotations below this line
 */
class ReindexKnowledgeDocumentsTest extends Unit
{
    protected const string SOURCE_TYPE_FIRST = 'test_source_first';

    protected const string SOURCE_TYPE_SECOND = 'test_source_second';

    protected const string SOURCE_TYPE_UNKNOWN = 'test_source_unknown';

    protected const string CONTENT_HASH = 'previous-content-hash';

    protected AiCommerceBusinessTester $tester;

    /**
     * @var list<string>
     */
    protected array $queuedSourceIdentifiers = [];

    protected function _before(): void
    {
        parent::_before();

        $this->queuedSourceIdentifiers = [];
        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_QUEUE, $this->createQueueClientMock());
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock([
            static::SOURCE_TYPE_FIRST,
            static::SOURCE_TYPE_SECOND,
        ]));
    }

    public function testGivenUnconfiguredKnowledgeStoreWhenReindexingThenErrorIsReturnedAndNothingIsQueued(): void
    {
        // Arrange
        $sourceIdentifiers = $this->createSourceIdentifiers(2);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock([]));
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $sourceIdentifiers),
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            new KnowledgeDocumentReindexRequestTransfer(),
        );

        // Assert
        $this->assertFalse($knowledgeDocumentReindexResponseTransfer->getIsSuccessful());
        $this->assertCount(1, $knowledgeDocumentReindexResponseTransfer->getErrors());
        $this->assertStringContainsString(static::SOURCE_TYPE_FIRST, $knowledgeDocumentReindexResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertStringContainsString('not configured', $knowledgeDocumentReindexResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame([], $this->queuedSourceIdentifiers);
        $this->assertNull($this->tester->findKnowledgeDocument(static::SOURCE_TYPE_FIRST, $sourceIdentifiers[0]));
    }

    public function testGivenOneOfTwoSourceTypesUnconfiguredWhenReindexingThenErrorNamesItAndNothingIsQueued(): void
    {
        // Arrange
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock([static::SOURCE_TYPE_FIRST]));
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $this->createSourceIdentifiers(1)),
            $this->createSourcePluginMock(static::SOURCE_TYPE_SECOND, $this->createSourceIdentifiers(1)),
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            new KnowledgeDocumentReindexRequestTransfer(),
        );

        // Assert
        $this->assertFalse($knowledgeDocumentReindexResponseTransfer->getIsSuccessful());
        $this->assertCount(1, $knowledgeDocumentReindexResponseTransfer->getErrors());
        $this->assertStringContainsString(static::SOURCE_TYPE_SECOND, $knowledgeDocumentReindexResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame([], $this->queuedSourceIdentifiers);
    }

    public function testGivenUnconfiguredSourceTypeThatIsNotRequestedWhenReindexingThenRequestedSourceTypeIsQueued(): void
    {
        // Arrange
        $firstSourceIdentifiers = $this->createSourceIdentifiers(2);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock([static::SOURCE_TYPE_FIRST]));
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $firstSourceIdentifiers),
            $this->createSourcePluginMock(static::SOURCE_TYPE_SECOND, $this->createSourceIdentifiers(1)),
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            (new KnowledgeDocumentReindexRequestTransfer())->addSourceType(static::SOURCE_TYPE_FIRST),
        );

        // Assert
        $this->assertTrue($knowledgeDocumentReindexResponseTransfer->getIsSuccessful());
        $this->assertEqualsCanonicalizing($firstSourceIdentifiers, $this->queuedSourceIdentifiers);
    }

    /**
     * @dataProvider providePageSizes
     */
    public function testGivenSourcesOnSeveralPagesWhenReindexingThenAllSourcesAreQueued(int $pageSize): void
    {
        // Arrange
        $sourceIdentifiers = $this->createSourceIdentifiers(5);
        $this->tester->mockConfigMethod('getKnowledgeDocumentReindexPageSize', $pageSize);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $sourceIdentifiers),
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            new KnowledgeDocumentReindexRequestTransfer(),
        );

        // Assert
        $this->assertTrue($knowledgeDocumentReindexResponseTransfer->getIsSuccessful());
        $this->assertSame(5, $knowledgeDocumentReindexResponseTransfer->getQueuedDocumentCount());
        $this->assertSame([static::SOURCE_TYPE_FIRST => 5], $knowledgeDocumentReindexResponseTransfer->getQueuedDocumentCountsBySourceType());
        $this->assertEqualsCanonicalizing($sourceIdentifiers, $this->queuedSourceIdentifiers);

        foreach ($sourceIdentifiers as $sourceIdentifier) {
            $this->assertSame(
                KnowledgeDocumentIndexer::STATUS_PENDING,
                $this->tester->findKnowledgeDocument(static::SOURCE_TYPE_FIRST, $sourceIdentifier)->getStatus(),
            );
        }
    }

    /**
     * @return array<string, array<int>>
     */
    public function providePageSizes(): array
    {
        return [
            'page size smaller than the source count' => [2],
            'page size zero' => [0],
            'page size larger than the source count' => [100],
        ];
    }

    public function testGivenRequestedSourceTypesWhenReindexingThenOnlyTheirSourcesAreQueued(): void
    {
        // Arrange
        $firstSourceIdentifiers = $this->createSourceIdentifiers(2);
        $secondSourcePluginMock = $this->createMock(KnowledgeDocumentSourcePluginInterface::class);
        $secondSourcePluginMock->method('getSourceType')->willReturn(static::SOURCE_TYPE_SECOND);
        $secondSourcePluginMock->expects($this->never())->method('getDocumentSourceCollection');
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $firstSourceIdentifiers),
            $secondSourcePluginMock,
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            (new KnowledgeDocumentReindexRequestTransfer())->addSourceType(static::SOURCE_TYPE_FIRST),
        );

        // Assert
        $this->assertTrue($knowledgeDocumentReindexResponseTransfer->getIsSuccessful());
        $this->assertEqualsCanonicalizing($firstSourceIdentifiers, $this->queuedSourceIdentifiers);
        $this->assertSame([static::SOURCE_TYPE_FIRST => 2], $knowledgeDocumentReindexResponseTransfer->getQueuedDocumentCountsBySourceType());
    }

    public function testGivenUnknownSourceTypeWhenReindexingThenErrorIsReturned(): void
    {
        // Arrange
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $this->createSourceIdentifiers(1)),
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            (new KnowledgeDocumentReindexRequestTransfer())->addSourceType(static::SOURCE_TYPE_UNKNOWN),
        );

        // Assert
        $this->assertFalse($knowledgeDocumentReindexResponseTransfer->getIsSuccessful());
        $this->assertCount(1, $knowledgeDocumentReindexResponseTransfer->getErrors());
        $this->assertSame([], $this->queuedSourceIdentifiers);
    }

    public function testGivenForcedRequestWhenReindexingThenContentHashIsCleared(): void
    {
        // Arrange
        $sourceIdentifiers = $this->createSourceIdentifiers(1);
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE_FIRST,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifiers[0],
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_INDEXED,
            KnowledgeDocumentTransfer::CONTENT_HASH => static::CONTENT_HASH,
        ]);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $sourceIdentifiers),
        ]);

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            (new KnowledgeDocumentReindexRequestTransfer())->setIsForced(true),
        );

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(static::SOURCE_TYPE_FIRST, $sourceIdentifiers[0]);
        $this->assertNull($knowledgeDocumentTransfer->getContentHash());
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_PENDING, $knowledgeDocumentTransfer->getStatus());
    }

    public function testGivenNotForcedRequestWhenReindexingThenContentHashIsKept(): void
    {
        // Arrange
        $sourceIdentifiers = $this->createSourceIdentifiers(1);
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE_FIRST,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifiers[0],
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_INDEXED,
            KnowledgeDocumentTransfer::CONTENT_HASH => static::CONTENT_HASH,
        ]);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $sourceIdentifiers),
        ]);

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(new KnowledgeDocumentReindexRequestTransfer());

        // Assert
        $this->assertSame(
            static::CONTENT_HASH,
            $this->tester->findKnowledgeDocument(static::SOURCE_TYPE_FIRST, $sourceIdentifiers[0])->getContentHash(),
        );
    }

    /**
     * @dataProvider providePageSizes
     */
    public function testGivenDeletedSourcesOnSeveralPagesWhenReindexingThenAllTheirDocumentsAreQueued(int $pageSize): void
    {
        // Arrange
        $deletedSourceIdentifiers = $this->haveIndexedKnowledgeDocuments(static::SOURCE_TYPE_FIRST, $this->createSourceIdentifiers(3));
        $this->tester->mockConfigMethod('getKnowledgeDocumentReindexPageSize', $pageSize);
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $this->createSourceIdentifiers(2)),
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            new KnowledgeDocumentReindexRequestTransfer(),
        );

        // Assert
        $this->assertTrue($knowledgeDocumentReindexResponseTransfer->getIsSuccessful());
        $this->assertSame(3, $knowledgeDocumentReindexResponseTransfer->getQueuedDeletedSourceDocumentCount());
        $this->assertSame([], array_diff($deletedSourceIdentifiers, $this->queuedSourceIdentifiers));
        $this->assertSame(count($deletedSourceIdentifiers) + 2, count($this->queuedSourceIdentifiers));

        foreach ($deletedSourceIdentifiers as $deletedSourceIdentifier) {
            $this->assertSame(
                KnowledgeDocumentIndexer::STATUS_PENDING,
                $this->tester->findKnowledgeDocument(static::SOURCE_TYPE_FIRST, $deletedSourceIdentifier)->getStatus(),
            );
        }
    }

    public function testGivenIndexedDocumentOfExistingSourceWhenReindexingThenDocumentIsQueuedOnce(): void
    {
        // Arrange
        $sourceIdentifiers = $this->haveIndexedKnowledgeDocuments(static::SOURCE_TYPE_FIRST, $this->createSourceIdentifiers(1));
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            $this->createSourcePluginMock(static::SOURCE_TYPE_FIRST, $sourceIdentifiers),
        ]);

        // Act
        $knowledgeDocumentReindexResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentReindexer()->reindex(
            new KnowledgeDocumentReindexRequestTransfer(),
        );

        // Assert
        $this->assertSame($sourceIdentifiers, $this->queuedSourceIdentifiers);
        $this->assertSame(1, $knowledgeDocumentReindexResponseTransfer->getQueuedDocumentCount());
        $this->assertSame(0, $knowledgeDocumentReindexResponseTransfer->getQueuedDeletedSourceDocumentCount());
    }

    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return list<string>
     */
    protected function haveIndexedKnowledgeDocuments(string $sourceType, array $sourceIdentifiers): array
    {
        foreach ($sourceIdentifiers as $sourceIdentifier) {
            $this->tester->haveKnowledgeDocument([
                KnowledgeDocumentTransfer::SOURCE_TYPE => $sourceType,
                KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => $sourceIdentifier,
                KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_INDEXED,
                KnowledgeDocumentTransfer::CONTENT_HASH => static::CONTENT_HASH,
            ]);
        }

        return $sourceIdentifiers;
    }

    /**
     * @param list<string> $sourceIdentifiers
     */
    protected function createSourcePluginMock(string $sourceType, array $sourceIdentifiers): KnowledgeDocumentSourcePluginInterface
    {
        $sourcePluginMock = $this->createMock(KnowledgeDocumentSourcePluginInterface::class);
        $sourcePluginMock->method('getSourceType')->willReturn($sourceType);
        $sourcePluginMock->method('getDocumentSourceCollection')->willReturnCallback(
            function (KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer) use ($sourceType, $sourceIdentifiers): KnowledgeDocumentSourceCollectionTransfer {
                $knowledgeDocumentSourceCollectionTransfer = new KnowledgeDocumentSourceCollectionTransfer();

                foreach ($this->getRequestedSourceIdentifiers($knowledgeDocumentSourceCriteriaTransfer, $sourceIdentifiers) as $sourceIdentifier) {
                    $knowledgeDocumentSourceCollectionTransfer->addKnowledgeDocumentSource(
                        (new KnowledgeDocumentSourceTransfer())->setSourceType($sourceType)->setSourceIdentifier($sourceIdentifier),
                    );
                }

                return $knowledgeDocumentSourceCollectionTransfer;
            },
        );

        return $sourcePluginMock;
    }

    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return list<string>
     */
    protected function getRequestedSourceIdentifiers(
        KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer,
        array $sourceIdentifiers,
    ): array {
        $requestedSourceIdentifiers = $knowledgeDocumentSourceCriteriaTransfer->getKnowledgeDocumentSourceConditions()?->getSourceIdentifiers() ?? [];

        if ($requestedSourceIdentifiers !== []) {
            return array_values(array_intersect($sourceIdentifiers, $requestedSourceIdentifiers));
        }

        $paginationTransfer = $knowledgeDocumentSourceCriteriaTransfer->getPaginationOrFail();

        return array_slice($sourceIdentifiers, (int)$paginationTransfer->getOffset(), (int)$paginationTransfer->getLimit());
    }

    /**
     * @return list<string>
     */
    protected function createSourceIdentifiers(int $count): array
    {
        $sourceIdentifiers = [];

        for ($i = 0; $i < $count; $i++) {
            $sourceIdentifiers[] = uniqid('document-', true);
        }

        return $sourceIdentifiers;
    }

    /**
     * @param list<string> $configuredSourceTypes
     */
    protected function createKnowledgeStorePluginMock(array $configuredSourceTypes): KnowledgeStorePluginInterface
    {
        $knowledgeStorePluginMock = $this->createMock(KnowledgeStorePluginInterface::class);
        $knowledgeStorePluginMock->method('isConfigured')->willReturnCallback(
            fn (string $sourceType): bool => in_array($sourceType, $configuredSourceTypes, true),
        );

        return $knowledgeStorePluginMock;
    }

    protected function createQueueClientMock(): QueueClientInterface
    {
        $queueClientMock = $this->createMock(QueueClientInterface::class);
        $queueClientMock->method('sendMessages')->willReturnCallback(function (string $queueName, array $queueSendMessageTransfers): void {
            foreach ($queueSendMessageTransfers as $queueSendMessageTransfer) {
                $this->queuedSourceIdentifiers[] = json_decode((string)$queueSendMessageTransfer->getBody(), true)['sourceIdentifier'];
            }
        });

        return $queueClientMock;
    }
}
