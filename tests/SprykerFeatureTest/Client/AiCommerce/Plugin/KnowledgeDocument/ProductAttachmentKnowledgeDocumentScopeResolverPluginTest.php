<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\KnowledgeDocument;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\ProductAbstractAttachmentStorageTransfer;
use Generated\Shared\Transfer\ProductAttachmentStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttachmentStorageTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\ProductAttachmentStorage\ProductAttachmentStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Client\AiCommerce\AiCommerceFactory;
use SprykerFeature\Client\AiCommerce\Plugin\KnowledgeDocument\ProductAttachmentKnowledgeDocumentScopeResolverPlugin;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group KnowledgeDocument
 * @group ProductAttachmentKnowledgeDocumentScopeResolverPluginTest
 */
class ProductAttachmentKnowledgeDocumentScopeResolverPluginTest extends Unit
{
    protected const string LOCALE_NAME = 'en_US';

    protected const string STORE_NAME = 'DE';

    protected const string OTHER_STORE_NAME = 'AT';

    protected const string ABSTRACT_SKU = 'doc-abstract-001';

    protected const string CONCRETE_SKU = 'doc-concrete-001_1';

    protected const string UNKNOWN_SKU = 'doc-unknown-001';

    protected const string RESTRICTED_ABSTRACT_SKU = 'doc-restricted-001';

    protected const int ID_PRODUCT_ABSTRACT = 501;

    protected const int ID_PRODUCT_ABSTRACT_RESTRICTED = 502;

    protected const int ID_PRODUCT_ABSTRACT_WITHOUT_DOCUMENTS = 503;

    protected const int ID_PRODUCT_ATTACHMENT_MANUAL = 11;

    protected const int ID_PRODUCT_ATTACHMENT_DATA_SHEET = 12;

    protected const string DOCUMENT_KEY_MANUAL = 'product_attachment:11';

    protected const string DOCUMENT_KEY_DATA_SHEET = 'product_attachment:12';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected AiCommerceClientTester $tester;

    public function testGivenAbstractSkuWhenResolvingScopeThenAttachmentDocumentKeysAreReturned(): void
    {
        // Arrange
        $this->haveProductStorage();
        $this->haveProductAttachmentStorage();

        // Act
        $knowledgeDocumentScopeTransfer = $this->createPlugin()->resolveDocumentScope($this->createScopeRequest()->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU, [static::ABSTRACT_SKU]));

        // Assert
        $this->assertSame([static::DOCUMENT_KEY_MANUAL, static::DOCUMENT_KEY_DATA_SHEET], $knowledgeDocumentScopeTransfer->getDocumentKeys());
        $this->assertSame([], $knowledgeDocumentScopeTransfer->getMessages());
    }

    public function testGivenConcreteSkuWhenResolvingScopeThenAttachmentsOfItsAbstractProductAreReturned(): void
    {
        // Arrange
        $this->haveProductStorage();
        $this->haveProductAttachmentStorage();

        // Act
        $knowledgeDocumentScopeTransfer = $this->createPlugin()->resolveDocumentScope($this->createScopeRequest()->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_CONCRETE_SKU, [static::CONCRETE_SKU]));

        // Assert
        $this->assertSame([static::DOCUMENT_KEY_MANUAL, static::DOCUMENT_KEY_DATA_SHEET], $knowledgeDocumentScopeTransfer->getDocumentKeys());
    }

    public function testGivenUnknownSkuWhenResolvingScopeThenScopeIsEmptyWithMessage(): void
    {
        // Arrange
        $this->haveProductStorage();
        $this->haveProductAttachmentStorage();

        // Act
        $knowledgeDocumentScopeTransfer = $this->createPlugin()->resolveDocumentScope($this->createScopeRequest()->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU, [static::UNKNOWN_SKU]));

        // Assert
        $this->assertSame([], $knowledgeDocumentScopeTransfer->getDocumentKeys());
        $this->assertCount(1, $knowledgeDocumentScopeTransfer->getMessages());
    }

    public function testGivenRestrictedProductWhenResolvingScopeThenNoDocumentKeysAreReturned(): void
    {
        // Arrange
        $this->haveProductStorage();
        $productAttachmentStorageClientMock = $this->haveProductAttachmentStorage();
        $productAttachmentStorageClientMock->expects($this->never())->method('findProductAbstractAttachmentStorage');

        // Act
        $knowledgeDocumentScopeTransfer = $this->createPlugin()->resolveDocumentScope(
            $this->createScopeRequest()
                ->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU, [static::RESTRICTED_ABSTRACT_SKU])
                ->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID, [(string)static::ID_PRODUCT_ABSTRACT_RESTRICTED]),
        );

        // Assert
        $this->assertSame([], $knowledgeDocumentScopeTransfer->getDocumentKeys());
        $this->assertNotEmpty($knowledgeDocumentScopeTransfer->getMessages());
    }

    public function testGivenProductOutsideRequestedStoreWhenResolvingScopeThenNoDocumentKeysAreReturned(): void
    {
        // Arrange
        $this->haveProductStorage();
        $this->haveProductAttachmentStorage();

        // Act
        $knowledgeDocumentScopeTransfer = $this->createPlugin()->resolveDocumentScope(
            $this->createScopeRequest()
                ->setStoreName(static::OTHER_STORE_NAME)
                ->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_ABSTRACT_SKU, [static::ABSTRACT_SKU]),
        );

        // Assert
        $this->assertSame([], $knowledgeDocumentScopeTransfer->getDocumentKeys());
        $this->assertCount(1, $knowledgeDocumentScopeTransfer->getMessages());
    }

    public function testGivenProductWithoutDocumentsWhenResolvingScopeThenScopeIsEmptyWithMessage(): void
    {
        // Arrange
        $this->haveProductStorage();
        $this->haveProductAttachmentStorage();

        // Act
        $knowledgeDocumentScopeTransfer = $this->createPlugin()->resolveDocumentScope(
            $this->createScopeRequest()->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID, [(string)static::ID_PRODUCT_ABSTRACT_WITHOUT_DOCUMENTS]),
        );

        // Assert
        $this->assertSame([], $knowledgeDocumentScopeTransfer->getDocumentKeys());
        $this->assertCount(1, $knowledgeDocumentScopeTransfer->getMessages());
    }

    public function testGivenSourceIdentifiersWhenResolvingScopeThenOnlyRequestedAttachmentsOfTheProductAreKept(): void
    {
        // Arrange
        $this->haveProductStorage();
        $this->haveProductAttachmentStorage();
        $knowledgeDocumentScopeRequestTransfer = $this->createScopeRequest()
            ->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID, [(string)static::ID_PRODUCT_ABSTRACT])
            ->setSourceIdentifiers([(string)static::ID_PRODUCT_ATTACHMENT_DATA_SHEET, '999']);

        // Act
        $knowledgeDocumentScopeTransfer = $this->createPlugin()->resolveDocumentScope($knowledgeDocumentScopeRequestTransfer);

        // Assert
        $this->assertSame([static::DOCUMENT_KEY_DATA_SHEET], $knowledgeDocumentScopeTransfer->getDocumentKeys());
    }

    public function testGivenLocaleWhenResolvingScopeThenAttachmentsAreReadForThatLocale(): void
    {
        // Arrange
        $this->haveProductStorage();
        $productAttachmentStorageClientMock = $this->haveProductAttachmentStorage();
        $productAttachmentStorageClientMock->expects($this->once())
            ->method('findProductAbstractAttachmentStorage')
            ->with($this->callback(fn (ProductAttachmentStorageCriteriaTransfer $productAttachmentStorageCriteriaTransfer): bool => $productAttachmentStorageCriteriaTransfer->getLocale() === static::LOCALE_NAME));

        // Act
        $this->createPlugin()->resolveDocumentScope($this->createScopeRequest()->addScopeReference(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SCOPE_REFERENCE_TYPE_PRODUCT_ABSTRACT_ID, [(string)static::ID_PRODUCT_ABSTRACT]));
    }

    public function testGivenOnlySourceIdentifiersWhenCheckingApplicabilityThenPluginIsNotApplicable(): void
    {
        // Arrange
        $knowledgeDocumentScopeRequestTransfer = $this->createScopeRequest()->addSourceIdentifier((string)static::ID_PRODUCT_ATTACHMENT_MANUAL);

        // Act
        $isApplicable = $this->createPlugin()->isApplicable($knowledgeDocumentScopeRequestTransfer);

        // Assert
        $this->assertFalse($isApplicable);
    }

    protected function createPlugin(): ProductAttachmentKnowledgeDocumentScopeResolverPlugin
    {
        $knowledgeDocumentScopeResolverPlugin = new ProductAttachmentKnowledgeDocumentScopeResolverPlugin();
        $knowledgeDocumentScopeResolverPlugin->setFactory($this->tester->getFactory());

        return $knowledgeDocumentScopeResolverPlugin;
    }

    protected function createScopeRequest(): KnowledgeDocumentScopeRequestTransfer
    {
        return (new KnowledgeDocumentScopeRequestTransfer())
            ->setLocaleName(static::LOCALE_NAME)
            ->setStoreName(static::STORE_NAME);
    }

    protected function haveProductStorage(): void
    {
        $productStorageClientMock = $this->createMock(ProductStorageClientInterface::class);
        $productStorageClientMock->method('findProductAbstractStorageDataByMapping')->willReturnCallback(
            fn (string $mappingType, string $sku): ?array => match ($sku) {
                static::ABSTRACT_SKU => [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT],
                static::RESTRICTED_ABSTRACT_SKU => [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT_RESTRICTED],
                default => null,
            },
        );
        $productStorageClientMock->method('findProductConcreteStorageDataByMapping')->willReturnCallback(
            fn (string $mappingType, string $sku): ?array => $sku === static::CONCRETE_SKU ? [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT] : null,
        );
        $productStorageClientMock->method('isProductAbstractRestricted')->willReturnCallback(
            fn (int $idProductAbstract): bool => $idProductAbstract === static::ID_PRODUCT_ABSTRACT_RESTRICTED,
        );
        $productStorageClientMock->method('getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore')->willReturnCallback(
            fn (array $productAbstractIds, string $localeName, string $storeName): array => $this->getProductAbstractStorageData($productAbstractIds, $storeName),
        );

        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_STORAGE, $productStorageClientMock, AiCommerceFactory::class);
    }

    /**
     * @param array<int> $productAbstractIds
     *
     * @return array<int, array<string, int>>
     */
    protected function getProductAbstractStorageData(array $productAbstractIds, string $storeName): array
    {
        if ($storeName !== static::STORE_NAME) {
            return [];
        }

        $productAbstractStorageData = [];

        foreach (array_diff($productAbstractIds, [static::ID_PRODUCT_ABSTRACT_RESTRICTED]) as $idProductAbstract) {
            $productAbstractStorageData[$idProductAbstract] = [static::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract];
        }

        return $productAbstractStorageData;
    }

    protected function haveProductAttachmentStorage(): ProductAttachmentStorageClientInterface&MockObject
    {
        $productAttachmentStorageClientMock = $this->createMock(ProductAttachmentStorageClientInterface::class);
        $productAttachmentStorageClientMock->method('findProductAbstractAttachmentStorage')->willReturnCallback(
            fn (ProductAttachmentStorageCriteriaTransfer $productAttachmentStorageCriteriaTransfer): ?ProductAbstractAttachmentStorageTransfer => $productAttachmentStorageCriteriaTransfer->getIdProductAbstract() === static::ID_PRODUCT_ABSTRACT
                ? $this->createProductAbstractAttachmentStorage()
                : null,
        );

        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_ATTACHMENT_STORAGE, $productAttachmentStorageClientMock, AiCommerceFactory::class);

        return $productAttachmentStorageClientMock;
    }

    protected function createProductAbstractAttachmentStorage(): ProductAbstractAttachmentStorageTransfer
    {
        return (new ProductAbstractAttachmentStorageTransfer())
            ->setIdProductAbstract(static::ID_PRODUCT_ABSTRACT)
            ->setAttachments(new ArrayObject([
                (new ProductAttachmentStorageTransfer())->setIdProductAttachment(static::ID_PRODUCT_ATTACHMENT_MANUAL)->setLabel('Installation manual'),
                (new ProductAttachmentStorageTransfer())->setIdProductAttachment(static::ID_PRODUCT_ATTACHMENT_DATA_SHEET)->setLabel('Data sheet'),
            ]));
    }
}
