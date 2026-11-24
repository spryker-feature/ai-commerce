<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAbstractAttachmentStorageTransfer;
use Generated\Shared\Transfer\ProductAttachmentStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttachmentStorageTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductAttachmentStorage\ProductAttachmentStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Client\AiCommerce\AiCommerceFactory;
use SprykerFeature\Client\AiCommerce\Plugin\KnowledgeDocument\ProductAttachmentKnowledgeDocumentScopeResolverPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayKnowledgeSourcesToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group DisplayKnowledgeSourcesToolPluginTest
 */
class DisplayKnowledgeSourcesToolPluginTest extends Unit
{
    protected const string LOCALE_NAME = 'en_US';

    protected const string STORE_NAME = 'DE';

    protected const int ID_PRODUCT_ABSTRACT = 601;

    protected const int ID_PRODUCT_ABSTRACT_SECOND = 602;

    protected const int ID_PRODUCT_ABSTRACT_WITHOUT_DOCUMENTS = 603;

    protected const int ID_PRODUCT_ATTACHMENT_MANUAL = 21;

    protected const int ID_PRODUCT_ATTACHMENT_DATA_SHEET = 22;

    protected const int ID_PRODUCT_ATTACHMENT_CERTIFICATE = 23;

    protected const string LABEL_MANUAL = 'User manual';

    protected const string LABEL_DATA_SHEET = 'Data sheet';

    protected const string LABEL_CERTIFICATE = 'Certificate';

    protected const string URL_MANUAL = 'https://spryker.s3.eu-central-1.amazonaws.com/user manual.pdf';

    protected const string URL_MANUAL_ENCODED = 'https://spryker.s3.eu-central-1.amazonaws.com/user%20manual.pdf';

    protected const string URL_DATA_SHEET = 'https://spryker.s3.eu-central-1.amazonaws.com/data-sheet.pdf';

    protected const string URL_INJECTED = 'https://attacker.example.com/manual.pdf';

    protected const string SOURCE_TYPE_PRODUCT_ATTACHMENT = 'product_attachment';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string KEY_SOURCES = 'sources';

    protected const string KEY_MESSAGES = 'messages';

    protected const string KEY_DOCUMENT_NAME = 'documentName';

    protected const string KEY_SOURCE_TYPE = 'sourceType';

    protected const string KEY_SOURCE_IDENTIFIER = 'sourceIdentifier';

    protected const string KEY_URL = 'url';

    protected const int MAX_PRODUCTS = 10;

    protected const int MAX_SOURCES = 2;

    protected AiCommerceClientTester $tester;

    public function testGivenAttachmentIdsInTheProductScopeWhenToolIsExecutedThenSourcesWithSanitizedUrlsAreReturnedInRequestedOrder(): void
    {
        // Arrange
        $this->haveProductDocuments([static::ID_PRODUCT_ABSTRACT => [$this->createManual(), $this->createDataSheet()]]);

        // Act
        $result = $this->createPlugin()->execute(attachment_ids: ['22', '21'], product_abstract_ids: [static::ID_PRODUCT_ABSTRACT]);

        // Assert
        $this->assertSame(
            [
                [static::KEY_DOCUMENT_NAME => static::LABEL_DATA_SHEET, static::KEY_SOURCE_TYPE => static::SOURCE_TYPE_PRODUCT_ATTACHMENT, static::KEY_SOURCE_IDENTIFIER => '22', static::KEY_URL => static::URL_DATA_SHEET],
                [static::KEY_DOCUMENT_NAME => static::LABEL_MANUAL, static::KEY_SOURCE_TYPE => static::SOURCE_TYPE_PRODUCT_ATTACHMENT, static::KEY_SOURCE_IDENTIFIER => '21', static::KEY_URL => static::URL_MANUAL_ENCODED],
            ],
            $result[static::KEY_SOURCES],
        );
        $this->assertSame([], $result[static::KEY_MESSAGES]);
    }

    public function testGivenAttachmentIdOutsideTheProductScopeWhenToolIsExecutedThenItIsNotReturnedAndMessagesTellTheAgent(): void
    {
        // Arrange
        $this->haveProductDocuments([
            static::ID_PRODUCT_ABSTRACT => [$this->createManual()],
            static::ID_PRODUCT_ABSTRACT_SECOND => [$this->createDataSheet()],
        ]);

        // Act
        $result = $this->createPlugin()->execute(attachment_ids: ['21', '22', '999'], product_abstract_ids: [static::ID_PRODUCT_ABSTRACT]);

        // Assert
        $this->assertSame(['21'], array_column($result[static::KEY_SOURCES], static::KEY_SOURCE_IDENTIFIER));
        $this->assertStringContainsString('2 of the attachment_ids are not documents of the given products', implode(' ', $result[static::KEY_MESSAGES]));
    }

    /**
     * @dataProvider provideInvalidUrls
     */
    public function testGivenAttachmentWithoutValidHttpUrlWhenToolIsExecutedThenSourceHasNoUrlAndArgumentUrlIsIgnored(?string $url): void
    {
        // Arrange
        $this->haveProductDocuments([static::ID_PRODUCT_ABSTRACT => [$this->createManual()->setUrl($url)]]);

        // Act
        $result = $this->createPlugin()->execute(attachment_ids: ['21'], product_abstract_ids: [static::ID_PRODUCT_ABSTRACT], url: static::URL_INJECTED);

        // Assert
        $this->assertSame(
            [[static::KEY_DOCUMENT_NAME => static::LABEL_MANUAL, static::KEY_SOURCE_TYPE => static::SOURCE_TYPE_PRODUCT_ATTACHMENT, static::KEY_SOURCE_IDENTIFIER => '21']],
            $result[static::KEY_SOURCES],
        );
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function provideInvalidUrls(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(document.cookie)'],
            'data scheme' => ['data:text/html;base64,PHNjcmlwdD4='],
            's3 scheme' => ['s3://spryker/manual.pdf'],
            'user info' => ['https://user:secret@spryker.s3.eu-central-1.amazonaws.com/manual.pdf'],
            'relative path' => ['/manual.pdf'],
            'no url' => [null],
        ];
    }

    /**
     * @dataProvider provideArgumentsWithoutAValidScope
     *
     * @param array<string, mixed> $arguments
     */
    public function testGivenArgumentsWithoutAValidScopeWhenToolIsExecutedThenNoSourceIsReturnedAndMessagesTellTheAgent(array $arguments): void
    {
        // Arrange
        $productAttachmentStorageClientMock = $this->haveProductDocuments([static::ID_PRODUCT_ABSTRACT => [$this->createManual()]]);
        $productAttachmentStorageClientMock->expects($this->never())->method('findProductAbstractAttachmentStorage');

        // Act
        $result = $this->createPlugin()->execute(...$arguments);

        // Assert
        $this->assertSame([], $result[static::KEY_SOURCES]);
        $this->assertNotEmpty($result[static::KEY_MESSAGES]);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function provideArgumentsWithoutAValidScope(): array
    {
        return [
            'no product' => [['attachment_ids' => ['21']]],
            'invalid products only' => [['attachment_ids' => ['21'], 'product_abstract_ids' => ['x', -1], 'abstract_skus' => [[]]]],
            'no attachment ids' => [['product_abstract_ids' => [static::ID_PRODUCT_ABSTRACT]]],
            'invalid attachment ids' => [['attachment_ids' => [' ', ['21']], 'product_abstract_ids' => [static::ID_PRODUCT_ABSTRACT]]],
            'more products than allowed' => [['attachment_ids' => ['21'], 'product_abstract_ids' => range(1, static::MAX_PRODUCTS + 1)]],
        ];
    }

    public function testGivenProductWithoutDocumentsWhenToolIsExecutedThenNoSourceIsReturnedAndMessagesTellTheAgent(): void
    {
        // Arrange
        $this->haveProductDocuments([static::ID_PRODUCT_ABSTRACT => [$this->createManual()]]);

        // Act
        $result = $this->createPlugin()->execute(attachment_ids: ['21'], product_abstract_ids: [static::ID_PRODUCT_ABSTRACT_WITHOUT_DOCUMENTS]);

        // Assert
        $this->assertSame([], $result[static::KEY_SOURCES]);
        $this->assertNotEmpty($result[static::KEY_MESSAGES]);
    }

    public function testGivenDuplicateAttachmentIdsAndSharedAttachmentsWhenToolIsExecutedThenEachSourceIsReturnedOnceUpToTheMaximum(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getKnowledgeSourceDisplayMaxSources', static::MAX_SOURCES);
        $this->haveProductDocuments([
            static::ID_PRODUCT_ABSTRACT => [$this->createManual(), $this->createDataSheet(), $this->createCertificate()],
            static::ID_PRODUCT_ABSTRACT_SECOND => [$this->createManual(), $this->createDataSheet()],
        ]);

        // Act
        $result = $this->createPlugin()->execute(attachment_ids: '21,21,22,23', product_abstract_ids: [static::ID_PRODUCT_ABSTRACT, static::ID_PRODUCT_ABSTRACT_SECOND]);

        // Assert
        $this->assertSame(['21', '22'], array_column($result[static::KEY_SOURCES], static::KEY_SOURCE_IDENTIFIER));
        $this->assertNotFalse(json_encode($result));
    }

    protected function createPlugin(): DisplayKnowledgeSourcesToolPlugin
    {
        $displayKnowledgeSourcesToolPlugin = new DisplayKnowledgeSourcesToolPlugin();
        $displayKnowledgeSourcesToolPlugin->setFactory($this->tester->getFactory());

        return $displayKnowledgeSourcesToolPlugin;
    }

    protected function createManual(): ProductAttachmentStorageTransfer
    {
        return (new ProductAttachmentStorageTransfer())
            ->setIdProductAttachment(static::ID_PRODUCT_ATTACHMENT_MANUAL)
            ->setLabel(static::LABEL_MANUAL)
            ->setUrl(static::URL_MANUAL);
    }

    protected function createDataSheet(): ProductAttachmentStorageTransfer
    {
        return (new ProductAttachmentStorageTransfer())
            ->setIdProductAttachment(static::ID_PRODUCT_ATTACHMENT_DATA_SHEET)
            ->setLabel(static::LABEL_DATA_SHEET)
            ->setUrl(static::URL_DATA_SHEET);
    }

    protected function createCertificate(): ProductAttachmentStorageTransfer
    {
        return (new ProductAttachmentStorageTransfer())
            ->setIdProductAttachment(static::ID_PRODUCT_ATTACHMENT_CERTIFICATE)
            ->setLabel(static::LABEL_CERTIFICATE);
    }

    /**
     * @param array<int, list<\Generated\Shared\Transfer\ProductAttachmentStorageTransfer>> $attachmentsByIdProductAbstract
     */
    protected function haveProductDocuments(array $attachmentsByIdProductAbstract): ProductAttachmentStorageClientInterface&MockObject
    {
        $this->haveCurrentLocaleAndStore();
        $this->haveProductStorage([...array_keys($attachmentsByIdProductAbstract), static::ID_PRODUCT_ABSTRACT_WITHOUT_DOCUMENTS]);
        $scopeResolverPlugin = new ProductAttachmentKnowledgeDocumentScopeResolverPlugin();
        $scopeResolverPlugin->setFactory($this->tester->getFactory());
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SCOPE_RESOLVER, [$scopeResolverPlugin], AiCommerceFactory::class);

        return $this->haveProductAttachmentStorage($attachmentsByIdProductAbstract);
    }

    protected function haveCurrentLocaleAndStore(): void
    {
        $localeClientMock = $this->createMock(LocaleClientInterface::class);
        $localeClientMock->method('getCurrentLocale')->willReturn(static::LOCALE_NAME);
        $storeClientMock = $this->createMock(StoreClientInterface::class);
        $storeClientMock->method('getCurrentStore')->willReturn((new StoreTransfer())->setName(static::STORE_NAME));

        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_LOCALE, $localeClientMock, AiCommerceFactory::class);
        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_STORE, $storeClientMock, AiCommerceFactory::class);
    }

    /**
     * @param list<int> $availableProductAbstractIds
     */
    protected function haveProductStorage(array $availableProductAbstractIds): void
    {
        $productStorageClientMock = $this->createMock(ProductStorageClientInterface::class);
        $productStorageClientMock->method('getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore')->willReturnCallback(
            fn (array $productAbstractIds): array => array_map(
                static fn (int $idProductAbstract): array => [static::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
                array_combine(
                    array_values(array_intersect($productAbstractIds, $availableProductAbstractIds)),
                    array_values(array_intersect($productAbstractIds, $availableProductAbstractIds)),
                ),
            ),
        );

        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_STORAGE, $productStorageClientMock, AiCommerceFactory::class);
    }

    /**
     * @param array<int, list<\Generated\Shared\Transfer\ProductAttachmentStorageTransfer>> $attachmentsByIdProductAbstract
     */
    protected function haveProductAttachmentStorage(array $attachmentsByIdProductAbstract): ProductAttachmentStorageClientInterface&MockObject
    {
        $productAttachmentStorageClientMock = $this->createMock(ProductAttachmentStorageClientInterface::class);
        $productAttachmentStorageClientMock->method('findProductAbstractAttachmentStorage')->willReturnCallback(
            fn (ProductAttachmentStorageCriteriaTransfer $productAttachmentStorageCriteriaTransfer): ?ProductAbstractAttachmentStorageTransfer => isset($attachmentsByIdProductAbstract[$productAttachmentStorageCriteriaTransfer->getIdProductAbstract()])
                ? (new ProductAbstractAttachmentStorageTransfer())
                    ->setIdProductAbstract($productAttachmentStorageCriteriaTransfer->getIdProductAbstract())
                    ->setAttachments(new ArrayObject($attachmentsByIdProductAbstract[$productAttachmentStorageCriteriaTransfer->getIdProductAbstract()]))
                : null,
        );

        $this->tester->setDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_ATTACHMENT_STORAGE, $productAttachmentStorageClientMock, AiCommerceFactory::class);

        return $productAttachmentStorageClientMock;
    }
}
