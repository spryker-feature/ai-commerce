<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Plugin\DataImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\DataImporterReportTransfer;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\DataImport\ProductAttachmentKnowledgeSelectionDataImportPlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\AiCommerceEvents;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceCommunicationTester;
use SprykerFeatureTest\Zed\AiCommerce\Stub\DataImporterPublisherStub;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group Plugin
 * @group DataImport
 * @group ProductAttachmentKnowledgeSelectionDataImportPluginTest
 * Add your own group annotations below this line
 */
class ProductAttachmentKnowledgeSelectionDataImportPluginTest extends Unit
{
    protected const string URL_PATTERN = 'https://docs.example.com/import-%s.pdf';

    protected const string LOCALE_NAME = 'en_US';

    protected const string LOCALE_DEFAULT = 'default';

    protected const string LOCALE_UNKNOWN = 'xx_XX';

    protected const string SKU_UNKNOWN = 'unknown-abstract-sku';

    protected const string VALUE_SELECTED = '1';

    protected const string VALUE_NOT_SELECTED = '0';

    protected AiCommerceCommunicationTester $tester;

    protected function _before(): void
    {
        parent::_before();

        DataImporterPublisherStub::reset();
    }

    /**
     * @disableTransaction
     */
    public function testGivenValueOneForNotSelectedAttachmentWhenImportingThenSelectionIsStoredAndPublishEventIsAdded(): void
    {
        // Arrange
        [$sku, , $url, $idProductAttachment] = $this->haveProductAbstractWithAttachment();

        // Act
        $dataImporterReportTransfer = $this->import([[$sku, static::LOCALE_NAME, $url, static::VALUE_SELECTED]]);

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idProductAttachment));
        $this->assertSame([$idProductAttachment], DataImporterPublisherStub::getImportedEntityIds(AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH));
    }

    /**
     * @disableTransaction
     */
    public function testGivenValueZeroForSelectedAttachmentWhenImportingThenSelectionIsDeletedAndPublishEventIsAdded(): void
    {
        // Arrange
        [$sku, , $url, $idProductAttachment] = $this->haveProductAbstractWithAttachment();
        $this->tester->haveProductAttachmentKnowledgeSelection($idProductAttachment);

        // Act
        $dataImporterReportTransfer = $this->import([[$sku, static::LOCALE_NAME, $url, static::VALUE_NOT_SELECTED]]);

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($idProductAttachment));
        $this->assertSame([$idProductAttachment], DataImporterPublisherStub::getImportedEntityIds(AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH));
    }

    /**
     * @disableTransaction
     */
    public function testGivenUnchangedRowsWhenImportingThenNoSelectionIsWrittenAndNoPublishEventIsAdded(): void
    {
        // Arrange
        [$sku, $idProductAbstract, $selectedUrl, $idSelectedProductAttachment] = $this->haveProductAbstractWithAttachment();
        $notSelectedUrl = sprintf(static::URL_PATTERN, uniqid('', true));
        $idNotSelectedProductAttachment = $this->tester->haveCommittedProductAttachment($idProductAbstract, $this->tester->getLocaleTransfer(static::LOCALE_NAME)->getIdLocaleOrFail(), $notSelectedUrl);
        $this->tester->haveProductAttachmentKnowledgeSelection($idSelectedProductAttachment);

        // Act
        $dataImporterReportTransfer = $this->import([
            [$sku, static::LOCALE_NAME, $selectedUrl, static::VALUE_SELECTED],
            [$sku, static::LOCALE_NAME, $notSelectedUrl, static::VALUE_NOT_SELECTED],
        ]);

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertSame(1, $this->tester->countProductAttachmentKnowledgeSelections($idSelectedProductAttachment));
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($idNotSelectedProductAttachment));
        $this->assertSame([], DataImporterPublisherStub::getImportedEntityIds(AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH));
    }

    /**
     * @disableTransaction
     */
    public function testGivenUnknownSkuUrlAndLocaleRowsWhenImportingThenEachBadRowIsReportedAndOtherRowsAreImported(): void
    {
        // Arrange
        [$sku, $idProductAbstract, $firstUrl, $idFirstProductAttachment] = $this->haveProductAbstractWithAttachment();
        $lastUrl = sprintf(static::URL_PATTERN, uniqid('', true));
        $idLastProductAttachment = $this->tester->haveCommittedProductAttachment($idProductAbstract, $this->tester->getLocaleTransfer(static::LOCALE_NAME)->getIdLocaleOrFail(), $lastUrl);
        $unknownUrl = sprintf(static::URL_PATTERN, uniqid('unknown-', true));
        $badRows = [
            [static::SKU_UNKNOWN, static::LOCALE_NAME, $firstUrl, static::VALUE_SELECTED],
            [$sku, static::LOCALE_NAME, $unknownUrl, static::VALUE_SELECTED],
            [$sku, static::LOCALE_UNKNOWN, $firstUrl, static::VALUE_SELECTED],
        ];

        // Act
        $dataImporterReportTransfer = $this->import([
            [$sku, static::LOCALE_NAME, $firstUrl, static::VALUE_SELECTED],
            ...$badRows,
            [$sku, static::LOCALE_NAME, $lastUrl, static::VALUE_SELECTED],
        ]);

        // Assert
        $this->assertFalse($dataImporterReportTransfer->getIsSuccess());
        $this->assertBadRowsAreReported($badRows, $dataImporterReportTransfer->getMessages()->getArrayCopy());
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idFirstProductAttachment));
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idLastProductAttachment));
    }

    /**
     * @disableTransaction
     */
    public function testGivenDefaultAndEmptyLocaleWhenImportingThenAttachmentsWithoutLocaleAreSelected(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $sku = $productAbstractTransfer->getSkuOrFail();
        $idProductAbstract = $productAbstractTransfer->getIdProductAbstractOrFail();
        $defaultUrl = sprintf(static::URL_PATTERN, uniqid('', true));
        $emptyUrl = sprintf(static::URL_PATTERN, uniqid('', true));
        $idDefaultProductAttachment = $this->tester->haveCommittedProductAttachment($idProductAbstract, null, $defaultUrl);
        $idEmptyProductAttachment = $this->tester->haveCommittedProductAttachment($idProductAbstract, null, $emptyUrl);

        // Act
        $dataImporterReportTransfer = $this->import([
            [$sku, static::LOCALE_DEFAULT, $defaultUrl, static::VALUE_SELECTED],
            [$sku, '', $emptyUrl, static::VALUE_SELECTED],
        ]);

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idDefaultProductAttachment));
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idEmptyProductAttachment));
    }

    /**
     * @disableTransaction
     */
    public function testGivenTwoAttachmentsWithSameUrlAndLocaleWhenImportingThenBothAreSelected(): void
    {
        // Arrange
        [$sku, $idProductAbstract, $url, $idFirstProductAttachment] = $this->haveProductAbstractWithAttachment();
        $idSecondProductAttachment = $this->tester->haveCommittedProductAttachment($idProductAbstract, $this->tester->getLocaleTransfer(static::LOCALE_NAME)->getIdLocaleOrFail(), $url);

        // Act
        $dataImporterReportTransfer = $this->import([[$sku, static::LOCALE_NAME, $url, static::VALUE_SELECTED]]);

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idFirstProductAttachment));
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idSecondProductAttachment));
    }

    public function testGivenProductAttachmentKnowledgeSelectionDataImportPluginWhenGettingImportTypeThenProductAttachmentKnowledgeSelectionTypeIsReturned(): void
    {
        // Arrange
        $productAttachmentKnowledgeSelectionDataImportPlugin = new ProductAttachmentKnowledgeSelectionDataImportPlugin();

        // Act
        $importType = $productAttachmentKnowledgeSelectionDataImportPlugin->getImportType();

        // Assert
        $this->assertSame('product-attachment-knowledge-selection', $importType);
    }

    /**
     * @return array{string, int, string, int}
     */
    protected function haveProductAbstractWithAttachment(): array
    {
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $url = sprintf(static::URL_PATTERN, uniqid('', true));
        $idProductAttachment = $this->tester->haveCommittedProductAttachment(
            $productAbstractTransfer->getIdProductAbstractOrFail(),
            $this->tester->getLocaleTransfer(static::LOCALE_NAME)->getIdLocaleOrFail(),
            $url,
        );

        return [$productAbstractTransfer->getSkuOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail(), $url, $idProductAttachment];
    }

    /**
     * @param list<list<string>> $rows
     */
    protected function import(array $rows): DataImporterReportTransfer
    {
        return (new ProductAttachmentKnowledgeSelectionDataImportPlugin())->import(
            $this->tester->createProductAttachmentKnowledgeSelectionImportConfiguration($rows),
        );
    }

    /**
     * @param list<list<string>> $badRows
     * @param list<\Generated\Shared\Transfer\DataImporterReportMessageTransfer> $dataImporterReportMessageTransfers
     */
    protected function assertBadRowsAreReported(array $badRows, array $dataImporterReportMessageTransfers): void
    {
        $this->assertCount(count($badRows), $dataImporterReportMessageTransfers);

        foreach ($badRows as $index => [$sku, $locale, $url]) {
            $message = $dataImporterReportMessageTransfers[$index]->getMessageOrFail();

            $this->assertStringContainsString($sku, $message);
            $this->assertStringContainsString($locale, $message);
            $this->assertStringContainsString($url, $message);
        }
    }
}
