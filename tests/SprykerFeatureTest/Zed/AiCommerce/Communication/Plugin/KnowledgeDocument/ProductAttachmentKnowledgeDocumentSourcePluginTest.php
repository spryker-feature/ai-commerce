<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeDocumentSourceConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Orm\Zed\ProductAttachment\Persistence\SpyProductAttachment;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\KnowledgeDocument\ProductAttachmentKnowledgeDocumentSourcePlugin;
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

    protected AiCommerceCommunicationTester $tester;

    public function testGivenAttachmentIdsWhenReadingSourcesThenAttachmentsAreMappedToSources(): void
    {
        // Arrange
        $idLocale = $this->tester->getLocator()->locale()->facade()->getLocale(static::LOCALE_NAME)->getIdLocaleOrFail();
        $idProductAttachment = $this->haveProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, $idLocale);

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

    public function testGivenAttachmentWithoutLocaleWhenReadingSourcesThenLocaleNameIsNull(): void
    {
        // Arrange
        $idProductAttachment = $this->haveProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([(string)$idProductAttachment]);

        // Assert
        $this->assertCount(1, $knowledgeDocumentSourceTransfers);
        $this->assertNull($knowledgeDocumentSourceTransfers[0]->getLocaleName());
    }

    public function testGivenOnlyNonNumericIdentifiersWhenReadingSourcesThenNoSourceIsReturned(): void
    {
        // Arrange
        $this->haveProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([static::NON_NUMERIC_SOURCE_IDENTIFIER]);

        // Assert
        $this->assertSame([], $knowledgeDocumentSourceTransfers);
    }

    public function testGivenNumericAndNonNumericIdentifiersWhenReadingSourcesThenOnlyTheNumericOnesAreRead(): void
    {
        // Arrange
        $idProductAttachment = $this->haveProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([static::NON_NUMERIC_SOURCE_IDENTIFIER, (string)$idProductAttachment]);

        // Assert
        $this->assertSame([(string)$idProductAttachment], $this->getSourceIdentifiers($knowledgeDocumentSourceTransfers));
    }

    public function testGivenPaginationWithoutIdentifiersWhenReadingSourcesThenThePageIsReturnedInIdOrder(): void
    {
        // Arrange
        $this->haveProductAttachment(static::LABEL_MANUAL, static::URL_MANUAL, null);
        $idProductAttachmentDataSheet = $this->haveProductAttachment(static::LABEL_DATA_SHEET, static::URL_DATA_SHEET, null);
        $sourceCount = count($this->readSources([]));

        // Act
        $knowledgeDocumentSourceTransfers = $this->readSources([], (new PaginationTransfer())->setLimit(2)->setOffset($sourceCount - 1));

        // Assert
        $this->assertSame([(string)$idProductAttachmentDataSheet], $this->getSourceIdentifiers($knowledgeDocumentSourceTransfers));
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
