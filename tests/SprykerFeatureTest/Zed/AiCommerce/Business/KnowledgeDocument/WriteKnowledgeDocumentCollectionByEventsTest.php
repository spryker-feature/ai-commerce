<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\KnowledgeDocument;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Orm\Zed\AiCommerce\Persistence\Map\SpyProductAttachmentKnowledgeSelectionTableMap;
use Spryker\Client\Queue\QueueClientInterface;
use Spryker\Zed\ProductAttachment\Dependency\ProductAttachmentEvents;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentIndexer;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument\ProductAttachmentKnowledgeDocumentSourcePlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\AiCommerceEvents;
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
 * @group WriteKnowledgeDocumentCollectionByEventsTest
 * Add your own group annotations below this line
 */
class WriteKnowledgeDocumentCollectionByEventsTest extends Unit
{
    protected const int ID_PRODUCT_ATTACHMENT_FIRST = 999999901;

    protected const int ID_PRODUCT_ATTACHMENT_SECOND = 999999902;

    protected const int ID_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION = 999999801;

    protected const string EVENT_NAME_UNSUBSCRIBED = 'Entity.spy_unrelated_table.create';

    protected AiCommerceBusinessTester $tester;

    /**
     * @var list<\Generated\Shared\Transfer\QueueSendMessageTransfer>
     */
    protected array $sentQueueSendMessageTransfers = [];

    protected function _before(): void
    {
        parent::_before();

        $this->sentQueueSendMessageTransfers = [];
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE, [
            new ProductAttachmentKnowledgeDocumentSourcePlugin(),
        ]);
        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_QUEUE, $this->createQueueClientMock());
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock(true));
    }

    public function testGivenUnconfiguredKnowledgeStoreWhenWritingThenNothingIsWrittenOrQueued(): void
    {
        // Arrange
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock(false));

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents(
            [(new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST)],
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_CREATE,
        );

        // Assert
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
        $this->assertNull($this->tester->findKnowledgeDocument(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
        ));
    }

    public function testGivenUnconfiguredKnowledgeStoreAndFailedDocumentWhenWritingThenDocumentIsNotChanged(): void
    {
        // Arrange
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $this->createKnowledgeStorePluginMock(false));
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_FAILED,
            KnowledgeDocumentTransfer::ATTEMPT_COUNT => 3,
        ]);

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents(
            [(new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST)],
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_UPDATE,
        );

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
        );

        $this->assertSame(KnowledgeDocumentIndexer::STATUS_FAILED, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(3, $knowledgeDocumentTransfer->getAttemptCount());
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
    }

    public function testGivenSubscribedEventWhenWritingThenPendingDocumentIsCreatedAndOneMessageIsQueued(): void
    {
        // Arrange
        $eventEntityTransfers = [(new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST)];

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents(
            $eventEntityTransfers,
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_CREATE,
        );

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
        );

        $this->assertNotNull($knowledgeDocumentTransfer);
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_PENDING, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(0, $knowledgeDocumentTransfer->getAttemptCount());
        $this->assertCount(1, $this->sentQueueSendMessageTransfers);
        $this->assertSame(
            [
                'sourceType' => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
                'sourceIdentifier' => (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
            ],
            json_decode((string)$this->sentQueueSendMessageTransfers[0]->getBody(), true),
        );
    }

    /**
     * @dataProvider provideSelectionEntityEventNames
     */
    public function testGivenSelectionEntityEventWhenWritingThenPendingDocumentOfTheProductAttachmentIsCreatedAndQueued(string $eventName): void
    {
        // Arrange
        $eventEntityTransfers = [
            (new EventEntityTransfer())
                ->setId(static::ID_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION)
                ->setForeignKeys([SpyProductAttachmentKnowledgeSelectionTableMap::COL_FK_PRODUCT_ATTACHMENT => static::ID_PRODUCT_ATTACHMENT_FIRST]),
        ];

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents($eventEntityTransfers, $eventName);

        // Assert
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_PENDING, $this->findProductAttachmentKnowledgeDocument(static::ID_PRODUCT_ATTACHMENT_FIRST)?->getStatus());
        $this->assertNull($this->findProductAttachmentKnowledgeDocument(static::ID_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION));
        $this->assertSame([(string)static::ID_PRODUCT_ATTACHMENT_FIRST], $this->getQueuedSourceIdentifiers());
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideSelectionEntityEventNames(): array
    {
        return [
            'selection create' => [AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CREATE],
            'selection delete' => [AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_DELETE],
        ];
    }

    public function testGivenSelectionPublishEventWhenWritingThenPendingDocumentOfTheEventIdIsCreatedAndQueued(): void
    {
        // Arrange
        $eventEntityTransfers = [(new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST)];

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents(
            $eventEntityTransfers,
            AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH,
        );

        // Assert
        $this->assertSame(KnowledgeDocumentIndexer::STATUS_PENDING, $this->findProductAttachmentKnowledgeDocument(static::ID_PRODUCT_ATTACHMENT_FIRST)?->getStatus());
        $this->assertSame([(string)static::ID_PRODUCT_ATTACHMENT_FIRST], $this->getQueuedSourceIdentifiers());
    }

    public function testGivenUnsubscribedEventWhenWritingThenNothingIsWrittenOrQueued(): void
    {
        // Arrange
        $eventEntityTransfers = [(new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST)];

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents($eventEntityTransfers, static::EVENT_NAME_UNSUBSCRIBED);

        // Assert
        $this->assertSame([], $this->sentQueueSendMessageTransfers);
        $this->assertNull($this->tester->findKnowledgeDocument(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
        ));
    }

    public function testGivenDuplicateIdsWhenWritingThenOneMessagePerDocumentIsQueued(): void
    {
        // Arrange
        $eventEntityTransfers = [
            (new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST),
            (new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST),
            (new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_SECOND),
        ];

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents(
            $eventEntityTransfers,
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_UPDATE,
        );

        // Assert
        $this->assertCount(2, $this->sentQueueSendMessageTransfers);
    }

    public function testGivenFailedDocumentWhenWritingThenDocumentIsPendingWithResetAttemptCount(): void
    {
        // Arrange
        $this->tester->haveKnowledgeDocument([
            KnowledgeDocumentTransfer::SOURCE_TYPE => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
            KnowledgeDocumentTransfer::STATUS => KnowledgeDocumentIndexer::STATUS_FAILED,
            KnowledgeDocumentTransfer::ATTEMPT_COUNT => 3,
        ]);

        // Act
        $this->tester->getFactory()->createKnowledgeDocumentEventWriter()->writeCollectionByEvents(
            [(new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_FIRST)],
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_DELETE,
        );

        // Assert
        $knowledgeDocumentTransfer = $this->tester->findKnowledgeDocument(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            (string)static::ID_PRODUCT_ATTACHMENT_FIRST,
        );

        $this->assertSame(KnowledgeDocumentIndexer::STATUS_PENDING, $knowledgeDocumentTransfer->getStatus());
        $this->assertSame(0, $knowledgeDocumentTransfer->getAttemptCount());
        $this->assertCount(1, $this->sentQueueSendMessageTransfers);
    }

    protected function createKnowledgeStorePluginMock(bool $isConfigured): KnowledgeStorePluginInterface
    {
        $knowledgeStorePluginMock = $this->createMock(KnowledgeStorePluginInterface::class);
        $knowledgeStorePluginMock->method('isConfigured')->willReturn($isConfigured);

        return $knowledgeStorePluginMock;
    }

    protected function findProductAttachmentKnowledgeDocument(int $idProductAttachment): ?KnowledgeDocumentTransfer
    {
        return $this->tester->findKnowledgeDocument(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT,
            (string)$idProductAttachment,
        );
    }

    /**
     * @return list<string>
     */
    protected function getQueuedSourceIdentifiers(): array
    {
        $sourceIdentifiers = [];

        foreach ($this->sentQueueSendMessageTransfers as $queueSendMessageTransfer) {
            $sourceIdentifiers[] = json_decode((string)$queueSendMessageTransfer->getBody(), true)['sourceIdentifier'];
        }

        return $sourceIdentifiers;
    }

    protected function createQueueClientMock(): QueueClientInterface
    {
        $queueClientMock = $this->createMock(QueueClientInterface::class);
        $queueClientMock->method('sendMessages')->willReturnCallback(function (string $queueName, array $queueSendMessageTransfers): void {
            $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_QUEUE, $queueName);
            $this->sentQueueSendMessageTransfers = array_merge($this->sentQueueSendMessageTransfers, $queueSendMessageTransfers);
        });

        return $queueClientMock;
    }
}
