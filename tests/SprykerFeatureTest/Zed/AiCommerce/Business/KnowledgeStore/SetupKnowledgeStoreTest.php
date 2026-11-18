<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\KnowledgeStore;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeStoreSetupRequestTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupResponseTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument\ProductAttachmentKnowledgeDocumentSourcePlugin;
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
 * @group KnowledgeStore
 * @group SetupKnowledgeStoreTest
 * Add your own group annotations below this line
 */
class SetupKnowledgeStoreTest extends Unit
{
    protected const string DATA_SOURCE_NAME = 'knowledge-documents';

    protected const string KNOWLEDGE_BASE_ID = 'KB12345678';

    protected const string DATA_SOURCE_ID = 'DS12345678';

    protected const string SECOND_DATA_SOURCE_ID = 'DS87654321';

    protected const string OTHER_SOURCE_TYPE = 'other_source';

    protected const string DATA_SOURCE_ID_CONFIGURATION_KEY = 'BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS';

    protected const array MEDIA_EXTRACTION_CONFIGURATION = [
        'imageExtractionConfiguration' => ['imageExtractionStatus' => 'ENABLED'],
    ];

    protected AiCommerceBusinessTester $tester;

    public function testGivenNoDataSourceIdWhenSettingUpThenErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, []);
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertFalse($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeStoreSetupResponseTransfer->getIsSkipped());
        $this->assertStringContainsString(static::DATA_SOURCE_ID_CONFIGURATION_KEY, $knowledgeStoreSetupResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenRegisteredSourceTypeWithoutDataSourceWhenSettingUpThenErrorIsReturnedWithoutRequest(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore(false, [new ProductAttachmentKnowledgeDocumentSourcePlugin(), $this->createSourcePluginMock(static::OTHER_SOURCE_TYPE)]);

        // Assert
        $this->assertFalse($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertCount(1, $knowledgeStoreSetupResponseTransfer->getErrors());
        $this->assertStringContainsString(static::OTHER_SOURCE_TYPE, $knowledgeStoreSetupResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenSeveralConfiguredDataSourcesWhenSettingUpThenEachDataSourceIsCheckedOnce(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID,
            static::OTHER_SOURCE_TYPE => static::SECOND_DATA_SOURCE_ID,
            'third_source' => static::DATA_SOURCE_ID,
        ]);
        $this->tester->mockBedrockAgentClient([
            ['knowledgeBase' => ['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID, 'status' => 'ACTIVE']],
            $this->createAvailableDataSourceResult(static::MEDIA_EXTRACTION_CONFIGURATION),
            $this->createAvailableDataSourceResult(static::MEDIA_EXTRACTION_CONFIGURATION),
        ]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertTrue($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertSame(['GetKnowledgeBase', 'GetDataSource', 'GetDataSource'], $this->tester->getBedrockAgentCommandNames());
        $this->assertSame(static::DATA_SOURCE_ID, $this->tester->getBedrockAgentCommands()[1]->toArray()['dataSourceId']);
        $this->assertSame(static::SECOND_DATA_SOURCE_ID, $this->tester->getBedrockAgentCommands()[2]->toArray()['dataSourceId']);
    }

    public function testGivenConfiguredKnowledgeBaseWhenSettingUpThenKnowledgeBaseAndDataSourceAreChecked(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([
            ['knowledgeBase' => ['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID, 'status' => 'ACTIVE']],
            $this->createAvailableDataSourceResult(static::MEDIA_EXTRACTION_CONFIGURATION),
        ]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertTrue($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeStoreSetupResponseTransfer->getIsSkipped());
        $this->assertSame(['GetKnowledgeBase', 'GetDataSource'], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenUnconfiguredKnowledgeBaseWhenSettingUpThenSetupIsSkipped(): void
    {
        // Arrange
        $this->configureBedrockSetup('', [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertTrue($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertTrue($knowledgeStoreSetupResponseTransfer->getIsSkipped());
        $this->assertSame([], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenFailedKnowledgeBaseWhenSettingUpThenStatusReasonIsReturned(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([
            ['knowledgeBase' => ['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID, 'status' => 'FAILED', 'failureReasons' => ['The role cannot be assumed.']]],
        ]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertFalse($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertStringContainsString('The role cannot be assumed.', $knowledgeStoreSetupResponseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenDeniedKnowledgeBaseRequestWhenSettingUpThenErrorIsReturned(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([$this->tester->createBedrockAwsException('AccessDeniedException', 403)]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertFalse($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertNotEmpty($knowledgeStoreSetupResponseTransfer->getErrors());
        $this->assertSame(['GetKnowledgeBase'], $this->tester->getBedrockAgentCommandNames());
    }

    public function testGivenIndexRecreationWhenSettingUpThenKnowledgeBaseIsKept(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([
            ['knowledgeBase' => ['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID, 'status' => 'ACTIVE']],
            $this->createAvailableDataSourceResult(static::MEDIA_EXTRACTION_CONFIGURATION),
        ]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore(true);

        // Assert
        $this->assertTrue($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertSame(['GetKnowledgeBase', 'GetDataSource'], $this->tester->getBedrockAgentCommandNames());
        $this->assertStringContainsString('not supported', implode(' ', $knowledgeStoreSetupResponseTransfer->getSteps()));
    }

    public function testGivenDataSourceWithoutImageExtractionWhenSettingUpThenImageExtractionIsEnabled(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockBedrockAgentClient([
            ['knowledgeBase' => ['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID, 'status' => 'ACTIVE']],
            $this->createAvailableDataSourceResult([]),
            ['dataSource' => ['dataSourceId' => static::DATA_SOURCE_ID, 'status' => 'UPDATING']],
            $this->createAvailableDataSourceResult(static::MEDIA_EXTRACTION_CONFIGURATION),
        ]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertTrue($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertSame(['GetKnowledgeBase', 'GetDataSource', 'UpdateDataSource', 'GetDataSource'], $this->tester->getBedrockAgentCommandNames());

        $updateDataSourceArguments = $this->tester->getBedrockAgentCommands()[2]->toArray();
        $this->assertSame(static::KNOWLEDGE_BASE_ID, $updateDataSourceArguments['knowledgeBaseId']);
        $this->assertSame(static::DATA_SOURCE_ID, $updateDataSourceArguments['dataSourceId']);
        $this->assertSame(static::DATA_SOURCE_NAME, $updateDataSourceArguments['name']);
        $this->assertSame('DELETE', $updateDataSourceArguments['dataDeletionPolicy']);
        $this->assertSame('CUSTOM', $updateDataSourceArguments['dataSourceConfiguration']['managedKnowledgeBaseConnectorConfiguration']['connectorParameters']['type']);
        $this->assertSame(
            static::MEDIA_EXTRACTION_CONFIGURATION,
            $updateDataSourceArguments['dataSourceConfiguration']['managedKnowledgeBaseConnectorConfiguration']['mediaExtractionConfiguration'],
        );
        $this->assertStringContainsString('media extraction', implode(' ', $knowledgeStoreSetupResponseTransfer->getSteps()));
    }

    public function testGivenNoMediaExtractionConfigWhenSettingUpThenDataSourceIsKept(): void
    {
        // Arrange
        $this->configureBedrockSetup(static::KNOWLEDGE_BASE_ID, [SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => static::DATA_SOURCE_ID]);
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseMediaExtractionConfiguration', []);
        $this->tester->mockBedrockAgentClient([
            ['knowledgeBase' => ['knowledgeBaseId' => static::KNOWLEDGE_BASE_ID, 'status' => 'ACTIVE']],
            $this->createAvailableDataSourceResult([]),
        ]);

        // Act
        $knowledgeStoreSetupResponseTransfer = $this->setupKnowledgeStore();

        // Assert
        $this->assertTrue($knowledgeStoreSetupResponseTransfer->getIsSuccessful());
        $this->assertSame(['GetKnowledgeBase', 'GetDataSource'], $this->tester->getBedrockAgentCommandNames());
    }

    /**
     * @param array<string, mixed> $mediaExtractionConfiguration
     *
     * @return array<string, mixed>
     */
    protected function createAvailableDataSourceResult(array $mediaExtractionConfiguration): array
    {
        $managedKnowledgeBaseConnectorConfiguration = ['connectorParameters' => ['type' => 'CUSTOM', 'version' => '1', 'aclEnabled' => false]];

        if ($mediaExtractionConfiguration !== []) {
            $managedKnowledgeBaseConnectorConfiguration['mediaExtractionConfiguration'] = $mediaExtractionConfiguration;
        }

        return [
        'dataSource' => [
            'knowledgeBaseId' => static::KNOWLEDGE_BASE_ID,
            'dataSourceId' => static::DATA_SOURCE_ID,
            'name' => static::DATA_SOURCE_NAME,
            'status' => 'AVAILABLE',
            'dataSourceConfiguration' => [
                'type' => 'MANAGED_KNOWLEDGE_BASE_CONNECTOR',
                'managedKnowledgeBaseConnectorConfiguration' => $managedKnowledgeBaseConnectorConfiguration,
            ],
            'dataDeletionPolicy' => 'DELETE',
        ]];
    }

    /**
     * @param array<string, string> $dataSourceIds
     */
    protected function configureBedrockSetup(string $knowledgeBaseId, array $dataSourceIds): void
    {
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseId', $knowledgeBaseId);
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseDataSourceIds', $dataSourceIds);
        $this->tester->mockConfigMethod('getBedrockKnowledgeBaseSetupStatusCheckInterval', 0);
    }

    protected function createSourcePluginMock(string $sourceType): KnowledgeDocumentSourcePluginInterface
    {
        $knowledgeDocumentSourcePluginMock = $this->createMock(KnowledgeDocumentSourcePluginInterface::class);
        $knowledgeDocumentSourcePluginMock->method('getSourceType')->willReturn($sourceType);

        return $knowledgeDocumentSourcePluginMock;
    }

    /**
     * @param list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface>|null $knowledgeDocumentSourcePlugins
     */
    protected function setupKnowledgeStore(bool $isIndexRecreated = false, ?array $knowledgeDocumentSourcePlugins = null): KnowledgeStoreSetupResponseTransfer
    {
        $bedrockKnowledgeBaseKnowledgeStorePlugin = new BedrockKnowledgeBaseKnowledgeStorePlugin();
        $bedrockKnowledgeBaseKnowledgeStorePlugin->setBusinessFactory($this->tester->getFactory());
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE, $bedrockKnowledgeBaseKnowledgeStorePlugin);
        $this->tester->setDependency(
            AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE,
            $knowledgeDocumentSourcePlugins ?? [new ProductAttachmentKnowledgeDocumentSourcePlugin()],
        );

        return $this->tester->getFactory()->createKnowledgeStoreSetup()->setupKnowledgeStore((new KnowledgeStoreSetupRequestTransfer())->setIsIndexRecreated($isIndexRecreated));
    }
}
