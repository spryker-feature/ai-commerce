<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\KnowledgeDocument;

use Aws\CommandInterface;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeStore\BedrockKnowledgeBaseKnowledgeStorePlugin;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group KnowledgeDocument
 * @group RefreshKnowledgeDocumentStatusesTest
 * Add your own group annotations below this line
 */
class RefreshKnowledgeDocumentStatusesTest extends Unit
{
    protected const string SOURCE_TYPE = 'test_source';

    protected const string DATA_SOURCE_ID = 'DS12345678';

    protected const string STATUS_REASON = 'The file is encrypted.';

    protected AiCommerceBusinessTester $tester;

    public function testGivenProcessingDocumentsWhenRefreshingThenKnowledgeBaseStatusesAreSaved(): void
    {
        // Arrange
        $this->arrangeBedrockKnowledgeStorePlugin();
        $sourceIdentifiers = $this->haveProcessingDocuments(3);
        $this->tester->mockBedrockAgentClient([$this->createDocumentStatusResponse([
            $sourceIdentifiers[0] => 'INDEXED',
            $sourceIdentifiers[1] => 'FAILED',
            $sourceIdentifiers[2] => 'IN_PROGRESS',
        ])]);

        // Act
        $knowledgeDocumentStatusRefreshResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentStatusRefresher()->refreshStatuses();

        // Assert
        $this->assertTrue($knowledgeDocumentStatusRefreshResponseTransfer->getIsSuccessful());
        $this->assertSame(3, $knowledgeDocumentStatusRefreshResponseTransfer->getCheckedDocumentCount());
        $this->assertSame(1, $knowledgeDocumentStatusRefreshResponseTransfer->getIndexedDocumentCount());
        $this->assertSame(1, $knowledgeDocumentStatusRefreshResponseTransfer->getFailedDocumentCount());
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED, $this->findDocument($sourceIdentifiers[0])->getStatus());
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED, $this->findDocument($sourceIdentifiers[1])->getStatus());
        $this->assertStringContainsString(static::STATUS_REASON, (string)$this->findDocument($sourceIdentifiers[1])->getErrorMessage());
        $this->assertNull($this->findDocument($sourceIdentifiers[1])->getContentHash());
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING, $this->findDocument($sourceIdentifiers[2])->getStatus());
    }

    public function testGivenDocumentsOnSeveralPagesWhenRefreshingThenAllPagesAreReadAndProcessingDocumentsAreSkipped(): void
    {
        // Arrange
        $this->arrangeBedrockKnowledgeStorePlugin();
        $this->tester->mockConfigMethod('getKnowledgeDocumentStatusRefreshPageSize', 2);
        $sourceIdentifiers = $this->haveProcessingDocuments(5);
        $statusesBySourceIdentifier = array_combine($sourceIdentifiers, ['IN_PROGRESS', 'INDEXED', 'IN_PROGRESS', 'INDEXED', 'INDEXED']);
        $this->tester->mockBedrockAgentClient(array_fill(0, 3, fn (CommandInterface $command): array => $this->createDocumentStatusResponseForCommand($command, $statusesBySourceIdentifier)));

        // Act
        $knowledgeDocumentStatusRefreshResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentStatusRefresher()->refreshStatuses();

        // Assert
        $this->assertSame(5, $knowledgeDocumentStatusRefreshResponseTransfer->getCheckedDocumentCount());
        $this->assertSame(3, $knowledgeDocumentStatusRefreshResponseTransfer->getIndexedDocumentCount());
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING, $this->findDocument($sourceIdentifiers[0])->getStatus());
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED, $this->findDocument($sourceIdentifiers[4])->getStatus());
    }

    public function testGivenFailingKnowledgeBaseWhenRefreshingThenDocumentsStayProcessing(): void
    {
        // Arrange
        $this->arrangeBedrockKnowledgeStorePlugin();
        $sourceIdentifiers = $this->haveProcessingDocuments(1);
        $this->tester->mockBedrockAgentClient([$this->tester->createBedrockAwsException('AccessDeniedException', 403)]);

        // Act
        $knowledgeDocumentStatusRefreshResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentStatusRefresher()->refreshStatuses();

        // Assert
        $this->assertFalse($knowledgeDocumentStatusRefreshResponseTransfer->getIsSuccessful());
        $this->assertCount(1, $knowledgeDocumentStatusRefreshResponseTransfer->getErrors());
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING, $this->findDocument($sourceIdentifiers[0])->getStatus());
    }

    public function testGivenSourceTypeWithoutDataSourceWhenRefreshingThenItsDocumentsAreFailed(): void
    {
        // Arrange
        $this->arrangeBedrockKnowledgeStorePlugin(['other_source' => static::DATA_SOURCE_ID]);
        $sourceIdentifiers = $this->haveProcessingDocuments(1);
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $knowledgeDocumentStatusRefreshResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentStatusRefresher()->refreshStatuses();

        // Assert
        $this->assertTrue($knowledgeDocumentStatusRefreshResponseTransfer->getIsSuccessful());
        $this->assertSame(1, $knowledgeDocumentStatusRefreshResponseTransfer->getFailedDocumentCount());
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED, $this->findDocument($sourceIdentifiers[0])->getStatus());
        $this->assertStringContainsString(static::SOURCE_TYPE, (string)$this->findDocument($sourceIdentifiers[0])->getErrorMessage());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenNoProcessingDocumentsWhenRefreshingThenNoRequestIsSent(): void
    {
        // Arrange
        $this->arrangeBedrockKnowledgeStorePlugin();
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $knowledgeDocumentStatusRefreshResponseTransfer = $this->tester->getFactory()->createKnowledgeDocumentStatusRefresher()->refreshStatuses();

        // Assert
        $this->assertTrue($knowledgeDocumentStatusRefreshResponseTransfer->getIsSuccessful());
        $this->assertSame(0, $knowledgeDocumentStatusRefreshResponseTransfer->getCheckedDocumentCount());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    /**
     * @param array<string, string>|null $dataSourceIds
     */
    protected function arrangeBedrockKnowledgeStorePlugin(?array $dataSourceIds = null): void
    {
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseId', 'KB12345678');
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseDataSourceIds', $dataSourceIds ?? [static::SOURCE_TYPE => static::DATA_SOURCE_ID]);

        $bedrockKnowledgeBaseKnowledgeStorePlugin = new BedrockKnowledgeBaseKnowledgeStorePlugin();
        $bedrockKnowledgeBaseKnowledgeStorePlugin->setBusinessFactory($this->tester->getFactory());
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $bedrockKnowledgeBaseKnowledgeStorePlugin);
    }

    /**
     * @return list<string>
     */
    protected function haveProcessingDocuments(int $documentCount): array
    {
        $sourceIdentifiers = [];

        for ($i = 0; $i < $documentCount; $i++) {
            $sourceIdentifiers[] = $this->tester->haveKnowledgeDocument([
                KnowledgeDocumentTransfer::SOURCE_TYPE => static::SOURCE_TYPE,
                KnowledgeDocumentTransfer::STATUS => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING,
                KnowledgeDocumentTransfer::CONTENT_HASH => 'content-hash',
            ])->getSourceIdentifierOrFail();
        }

        return $sourceIdentifiers;
    }

    /**
     * @param array<string, string> $statusesBySourceIdentifier
     *
     * @return array<string, mixed>
     */
    protected function createDocumentStatusResponse(array $statusesBySourceIdentifier): array
    {
        $documentDetails = [];

        foreach ($statusesBySourceIdentifier as $sourceIdentifier => $status) {
            $documentDetails[] = [
                'status' => $status,
                'statusReason' => static::STATUS_REASON,
                'identifier' => ['dataSourceType' => 'CUSTOM', 'custom' => ['id' => sprintf('%s:%s', static::SOURCE_TYPE, $sourceIdentifier)]],
            ];
        }

        return ['documentDetails' => $documentDetails];
    }

    /**
     * @param array<string, string> $statusesBySourceIdentifier
     *
     * @return array<string, mixed>
     */
    protected function createDocumentStatusResponseForCommand(CommandInterface $command, array $statusesBySourceIdentifier): array
    {
        $requestedStatusesBySourceIdentifier = [];

        foreach ($command->toArray()['documentIdentifiers'] as $documentIdentifier) {
            $sourceIdentifier = substr($documentIdentifier['custom']['id'], strlen(static::SOURCE_TYPE) + 1);
            $requestedStatusesBySourceIdentifier[$sourceIdentifier] = $statusesBySourceIdentifier[$sourceIdentifier];
        }

        return $this->createDocumentStatusResponse($requestedStatusesBySourceIdentifier);
    }

    protected function findDocument(string $sourceIdentifier): KnowledgeDocumentTransfer
    {
        return $this->tester->findKnowledgeDocument(static::SOURCE_TYPE, $sourceIdentifier);
    }
}
