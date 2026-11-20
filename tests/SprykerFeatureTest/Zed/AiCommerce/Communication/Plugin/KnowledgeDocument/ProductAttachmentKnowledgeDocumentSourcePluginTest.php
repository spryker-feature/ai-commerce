<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Orm\Zed\AiCommerce\Persistence\Map\SpyProductAttachmentKnowledgeSelectionTableMap;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachment;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachmentQuery;
use Spryker\Zed\ProductAttachment\Dependency\ProductAttachmentEvents;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument\ProductAttachmentKnowledgeDocumentSourcePlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\AiCommerceEvents;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group Plugin
 * @group KnowledgeDocument
 * @group ProductAttachmentKnowledgeDocumentSourcePluginTest
 * Add your own group annotations below this line
 */
class ProductAttachmentKnowledgeDocumentSourcePluginTest extends Unit
{
    protected const string LOCALE_NAME = 'en_US';

    protected const string LABEL_MANUAL = 'Manual';

    protected const string LABEL_DATA_SHEET = 'Data sheet';

    protected const string URL_MANUAL = 'https://docs.example.com/manual.pdf';

    protected const string URL_DATA_SHEET = 'https://docs.example.com/data-sheet.pdf';

    protected const string NON_NUMERIC_SOURCE_IDENTIFIER = 'manual';

    protected const int ID_PRODUCT_ATTACHMENT = 999999901;

    protected const int ID_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION = 999999801;

    protected const int PAGE_SIZE = 2;

    protected AiCommerceCommunicationTester $tester;

    public function testGivenSelectedAttachmentIdsWhenReadingSourcesThenAttachmentsAreMappedToSources(): void
    {
        // Arrange
        $idLocale = $this->tester->getLocator()->locale()->facade()->getLocale(static::LOCALE_NAME)->getIdLocaleOrFail();
        $idProductAttachment = $this->haveSelectedProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, $idLocale);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([(string)$idProductAttachment]);

        // Assert
        $this->assertCount(1, $knowledgeDocumentSourceTransfers);
        $this->assertSame(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT, $knowledgeDocumentSourceTransfers[0]->getSourceType());
        $this->assertSame((string)$idProductAttachment, $knowledgeDocumentSourceTransfers[0]->getSourceIdentifier());
        $this->assertSame(static::LABEL_MANUAL, $knowledgeDocumentSourceTransfers[0]->getDocumentName());
        $this->assertSame(static::URL_MANUAL, $knowledgeDocumentSourceTransfers[0]->getUrl());
        $this->assertSame(static::LOCALE_NAME, $knowledgeDocumentSourceTransfers[0]->getLocaleName());
    }

    public function testGivenSelectedAttachmentWithoutLocaleWhenReadingSourcesThenLocaleNameIsNull(): void
    {
        // Arrange
        $idProductAttachment = $this->haveSelectedProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([(string)$idProductAttachment]);

        // Assert
        $this->assertCount(1, $knowledgeDocumentSourceTransfers);
        $this->assertNull($knowledgeDocumentSourceTransfers[0]->getLocaleName());
    }

    public function testGivenSelectedAndNotSelectedAttachmentIdsWhenReadingSourcesThenOnlySelectedAttachmentsAreReturned(): void
    {
        // Arrange
        $idProductAttachmentManual = $this->haveSelectedProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);
        $idProductAttachmentNotSelected = $this->haveProductAttachment(static::LABEL_DATA_SHEET, static::URL_DATA_SHEET, null);
        $idProductAttachmentDataSheet = $this->haveSelectedProductAttachment(static::LABEL_DATA_SHEET, static::URL_DATA_SHEET, null);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([
            (string)$idProductAttachmentManual,
            (string)$idProductAttachmentNotSelected,
            (string)$idProductAttachmentDataSheet,
        ]);

        // Assert
        $this->assertSame(
            [(string)$idProductAttachmentManual, (string)$idProductAttachmentDataSheet],
            $this->getSourceIdentifiers($knowledgeDocumentSourceTransfers),
        );
    }

    public function testGivenDeletedSelectedAttachmentWhenReadingSourcesThenNoSourceIsReturned(): void
    {
        // Arrange
        $idProductAttachment = $this->haveSelectedProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);
        SpyProductAttachmentQuery::create()->filterByIdProductAttachment($idProductAttachment)->delete();

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([(string)$idProductAttachment]);

        // Assert
        $this->assertSame([], $knowledgeDocumentSourceTransfers);
    }

    public function testGivenOnlyNonNumericIdentifiersWhenReadingSourcesThenNoSourceIsReturned(): void
    {
        // Arrange
        $this->haveSelectedProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([static::NON_NUMERIC_SOURCE_IDENTIFIER]);

        // Assert
        $this->assertSame([], $knowledgeDocumentSourceTransfers);
    }

    public function testGivenNumericAndNonNumericIdentifiersWhenReadingSourcesThenOnlyTheNumericOnesAreRead(): void
    {
        // Arrange
        $idProductAttachment = $this->haveSelectedProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([static::NON_NUMERIC_SOURCE_IDENTIFIER, (string)$idProductAttachment]);

        // Assert
        $this->assertSame([(string)$idProductAttachment], $this->getSourceIdentifiers($knowledgeDocumentSourceTransfers));
    }

    public function testGivenSelectedAndNotSelectedAttachmentsWhenReadingAllPagesThenOnlySelectedAttachmentsAreReturnedOnceInIdOrder(): void
    {
        // Arrange
        [$selectedSourceIdentifiers, $notSelectedSourceIdentifiers] = $this->haveSelectedAndNotSelectedProductAttachments(5);

        // Act
        $sourceIdentifiers = $this->readAllPages(static::PAGE_SIZE);

        // Assert
        $this->assertSame(array_values(array_unique($sourceIdentifiers)), $sourceIdentifiers);
        $this->assertSame([], array_values(array_intersect($notSelectedSourceIdentifiers, $sourceIdentifiers)));
        $this->assertSame($selectedSourceIdentifiers, array_values(array_intersect($sourceIdentifiers, $selectedSourceIdentifiers)));
    }

    public function testGivenSelectedAttachmentsWithoutLocaleWhenReadingByIdentifiersThenOneQueryRuns(): void
    {
        // Arrange
        [$selectedSourceIdentifiers, $notSelectedSourceIdentifiers] = $this->haveSelectedAndNotSelectedProductAttachments(3);

        // Act
        $queryCount = $this->tester->countQueries(fn () => $this->readSources(array_merge($selectedSourceIdentifiers, $notSelectedSourceIdentifiers)));

        // Assert
        $this->assertSame(1, $queryCount);
    }

    public function testGivenSelectedAttachmentsWithoutLocaleWhenReadingOnePageThenOneQueryRuns(): void
    {
        // Arrange
        [$selectedSourceIdentifiers] = $this->haveSelectedAndNotSelectedProductAttachments(static::PAGE_SIZE);
        $lastPageOffset = count($this->readSources([])) - static::PAGE_SIZE;
        $knowledgeDocumentSourceTransfers = [];

        // Act
        $queryCount = $this->tester->countQueries(
            function () use (&$knowledgeDocumentSourceTransfers, $lastPageOffset): void {
                $knowledgeDocumentSourceTransfers = $this->readSources(
                    [],
                    (new PaginationTransfer())->setLimit(static::PAGE_SIZE)->setOffset($lastPageOffset),
                );
            },
        );

        // Assert
        $this->assertSame(1, $queryCount);
        $this->assertSame($selectedSourceIdentifiers, $this->getSourceIdentifiers($knowledgeDocumentSourceTransfers));
    }

    /**
     * @dataProvider provideSelectionEntityEventNames
     */
    public function testGivenSelectionEntityEventWhenExtractingIdentifiersThenProductAttachmentIdIsTakenFromForeignKeys(string $eventName): void
    {
        // Arrange
        $eventEntityTransfer = (new EventEntityTransfer())
            ->setId(static::ID_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION)
            ->setForeignKeys([SpyProductAttachmentKnowledgeSelectionTableMap::COL_FK_PRODUCT_ATTACHMENT => static::ID_PRODUCT_ATTACHMENT]);

        // Act
        $sourceIdentifiers = (new ProductAttachmentKnowledgeDocumentSourcePlugin())->extractSourceIdentifiers([$eventEntityTransfer], $eventName);

        // Assert
        $this->assertSame([(string)static::ID_PRODUCT_ATTACHMENT], $sourceIdentifiers);
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

    /**
     * @dataProvider provideProductAttachmentIdEventNames
     */
    public function testGivenEventWithProductAttachmentIdWhenExtractingIdentifiersThenEventIdIsUsed(string $eventName): void
    {
        // Arrange
        $eventEntityTransfer = (new EventEntityTransfer())
            ->setId(static::ID_PRODUCT_ATTACHMENT)
            ->setForeignKeys([SpyProductAttachmentKnowledgeSelectionTableMap::COL_FK_PRODUCT_ATTACHMENT => static::ID_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION]);

        // Act
        $sourceIdentifiers = (new ProductAttachmentKnowledgeDocumentSourcePlugin())->extractSourceIdentifiers([$eventEntityTransfer], $eventName);

        // Assert
        $this->assertSame([(string)static::ID_PRODUCT_ATTACHMENT], $sourceIdentifiers);
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideProductAttachmentIdEventNames(): array
    {
        return [
            'attachment create' => [ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_CREATE],
            'attachment update' => [ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_UPDATE],
            'attachment delete' => [ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_DELETE],
            'product abstract attachment publish' => [ProductAttachmentEvents::PRODUCT_ABSTRACT_ATTACHMENT_PUBLISH],
            'selection publish' => [AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH],
        ];
    }

    public function testGivenSelectionEntityEventWithoutForeignKeyWhenExtractingIdentifiersThenEventIsSkipped(): void
    {
        // Arrange
        $eventEntityTransfer = (new EventEntityTransfer())->setId(static::ID_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION);

        // Act
        $sourceIdentifiers = (new ProductAttachmentKnowledgeDocumentSourcePlugin())->extractSourceIdentifiers(
            [$eventEntityTransfer],
            AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CREATE,
        );

        // Assert
        $this->assertSame([], $sourceIdentifiers);
    }

    public function testWhenGettingSubscribedEventsThenAttachmentAndSelectionEventsAreReturned(): void
    {
        // Act
        $subscribedEvents = (new ProductAttachmentKnowledgeDocumentSourcePlugin())->getSubscribedEvents();

        // Assert
        $this->assertEqualsCanonicalizing([
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_CREATE,
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_UPDATE,
            ProductAttachmentEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_DELETE,
            ProductAttachmentEvents::PRODUCT_ABSTRACT_ATTACHMENT_PUBLISH,
            AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_CREATE,
            AiCommerceEvents::ENTITY_SPY_PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_DELETE,
            AiCommerceEvents::PRODUCT_ATTACHMENT_KNOWLEDGE_SELECTION_PUBLISH,
        ], $subscribedEvents);
    }

    protected function haveProductAttachment(string $label, string $url, ?int $idLocale): int
    {
        $productAttachmentEntity = (new SpyProductAttachment())
            ->setLabel($label)
            ->setUrl($url)
            ->setFkLocale($idLocale);
        $productAttachmentEntity->save();

        return $productAttachmentEntity->getIdProductAttachment();
    }

    protected function haveSelectedProductAttachment(string $label, string $url, ?int $idLocale): int
    {
        $idProductAttachment = $this->haveProductAttachment($label, $url, $idLocale);
        $this->tester->haveProductAttachmentKnowledgeSelection($idProductAttachment);

        return $idProductAttachment;
    }

    /**
     * @return array{list<string>, list<string>}
     */
    protected function haveSelectedAndNotSelectedProductAttachments(int $count): array
    {
        $selectedSourceIdentifiers = [];
        $notSelectedSourceIdentifiers = [];

        for ($i = 0; $i < $count; $i++) {
            $selectedSourceIdentifiers[] = (string)$this->haveSelectedProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);
            $notSelectedSourceIdentifiers[] = (string)$this->haveProductAttachment(static::LABEL_DATA_SHEET, static::URL_DATA_SHEET, null);
        }

        return [$selectedSourceIdentifiers, $notSelectedSourceIdentifiers];
    }

    /**
     * @return list<string>
     */
    protected function readAllPages(int $pageSize): array
    {
        $sourceIdentifiers = [];
        $offset = 0;

        do {
            $knowledgeDocumentSourceTransfers = $this->readSources([], (new PaginationTransfer())->setLimit($pageSize)->setOffset($offset));
            $sourceIdentifiers = array_merge($sourceIdentifiers, $this->getSourceIdentifiers($knowledgeDocumentSourceTransfers));
            $offset += $pageSize;
        } while (count($knowledgeDocumentSourceTransfers) === $pageSize);

        return $sourceIdentifiers;
    }

    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer>
     */
    protected function readSources(array $sourceIdentifiers, ?PaginationTransfer $paginationTransfer = null): array
    {
        $knowledgeDocumentSourceCriteriaTransfer = (new KnowledgeDocumentSourceCriteriaTransfer())
            ->setKnowledgeDocumentSourceConditions((new KnowledgeDocumentSourceConditionsTransfer())->setSourceIdentifiers($sourceIdentifiers))
            ->setPagination($paginationTransfer);

        return array_values((new ProductAttachmentKnowledgeDocumentSourcePlugin())
            ->getDocumentSourceCollection($knowledgeDocumentSourceCriteriaTransfer)
            ->getKnowledgeDocumentSources()
            ->getArrayCopy());
    }

    /**
     * @param list<\Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer> $knowledgeDocumentSourceTransfers
     *
     * @return list<string>
     */
    protected function getSourceIdentifiers(array $knowledgeDocumentSourceTransfers): array
    {
        return array_map(
            static fn (KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): string => (string)$knowledgeDocumentSourceTransfer->getSourceIdentifier(),
            $knowledgeDocumentSourceTransfers,
        );
    }
}
