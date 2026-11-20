<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Plugin\ProductAttachment;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductAttachmentTransfer;
use Spryker\Zed\ProductAttachment\Business\ProductAttachmentFacadeInterface;
use Spryker\Zed\ProductAttachment\ProductAttachmentDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentCollectionPostSavePlugin;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group Plugin
 * @group ProductAttachment
 * @group KnowledgeSelectionProductAttachmentCollectionPostSavePluginTest
 * Add your own group annotations below this line
 */
class KnowledgeSelectionProductAttachmentCollectionPostSavePluginTest extends Unit
{
    protected const string URL_NEW_ATTACHMENT = 'https://docs.example.com/new-%s.pdf';

    protected AiCommerceCommunicationTester $tester;

    public function testGivenTrueForNotSelectedAttachmentWhenPostSaveThenSelectionIsStored(): void
    {
        // Arrange
        $productAttachmentTransfer = $this->tester->haveProductAttachment()->setIsAssistantKnowledge(true);

        // Act
        (new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin())->postSave($this->tester->createProductAttachmentCollection($productAttachmentTransfer));

        // Assert
        $this->assertSame(1, $this->tester->countProductAttachmentKnowledgeSelections($productAttachmentTransfer->getIdProductAttachmentOrFail()));
    }

    public function testGivenFalseForSelectedAttachmentWhenPostSaveThenSelectionIsDeleted(): void
    {
        // Arrange
        $productAttachmentTransfer = $this->haveSelectedProductAttachment()->setIsAssistantKnowledge(false);

        // Act
        (new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin())->postSave($this->tester->createProductAttachmentCollection($productAttachmentTransfer));

        // Assert
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($productAttachmentTransfer->getIdProductAttachmentOrFail()));
    }

    public function testGivenNullSelectionWhenPostSaveThenStoredSelectionsAreNotChanged(): void
    {
        // Arrange
        $selectedAttachment = $this->haveSelectedProductAttachment()->setIsAssistantKnowledge(null);
        $notSelectedAttachment = $this->tester->haveProductAttachment()->setIsAssistantKnowledge(null);

        // Act
        (new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin())->postSave($this->tester->createProductAttachmentCollection($selectedAttachment, $notSelectedAttachment));

        // Assert
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($selectedAttachment->getIdProductAttachmentOrFail()));
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($notSelectedAttachment->getIdProductAttachmentOrFail()));
    }

    public function testGivenTrueTwiceWhenPostSaveThenOneSelectionIsStored(): void
    {
        // Arrange
        $productAttachmentTransfer = $this->tester->haveProductAttachment()->setIsAssistantKnowledge(true);
        $plugin = new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin();

        // Act
        $plugin->postSave($this->tester->createProductAttachmentCollection($productAttachmentTransfer, clone $productAttachmentTransfer));
        $plugin->postSave($this->tester->createProductAttachmentCollection($productAttachmentTransfer));

        // Assert
        $this->assertSame(1, $this->tester->countProductAttachmentKnowledgeSelections($productAttachmentTransfer->getIdProductAttachmentOrFail()));
    }

    public function testGivenNewAttachmentsSavedThroughFacadeWhenPostSaveRunsThenOnlyTheSelectedNewAttachmentIsStored(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $selectedUrl = sprintf(static::URL_NEW_ATTACHMENT, uniqid('', true));
        $notSelectedUrl = sprintf(static::URL_NEW_ATTACHMENT, uniqid('', true));

        // Act
        $this->getProductAttachmentFacadeWithPostSavePlugin()->saveProductAbstractAttachmentCollection(
            $this->setProductAttachments($productAbstractTransfer, $this->createNewAttachment($selectedUrl, true), $this->createNewAttachment($notSelectedUrl, false)),
        );

        // Assert
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected((int)$this->tester->findProductAttachmentIdByUrl($selectedUrl)));
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected((int)$this->tester->findProductAttachmentIdByUrl($notSelectedUrl)));
    }

    public function testGivenSelectedAttachmentRemovedFromItsOnlyProductWhenSaveThenAttachmentAndSelectionAreDeleted(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $productAttachmentTransfer = $this->haveSelectedProductAttachment($productAbstractTransfer->getIdProductAbstractOrFail());

        // Act
        $this->getProductAttachmentFacadeWithPostSavePlugin()->saveProductAbstractAttachmentCollection($this->setProductAttachments($productAbstractTransfer));

        // Assert
        $this->assertFalse($this->tester->hasProductAttachment($productAttachmentTransfer->getIdProductAttachmentOrFail()));
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($productAttachmentTransfer->getIdProductAttachmentOrFail()));
    }

    public function testGivenSharedSelectedAttachmentWhenRemovedFromOneProductThenSelectionStaysAndClearingOnOtherProductDeletesIt(): void
    {
        // Arrange
        $productAbstractTransferA = $this->tester->haveProductAbstract();
        $productAbstractTransferB = $this->tester->haveProductAbstract();
        $productAttachmentTransfer = $this->haveSelectedProductAttachment($productAbstractTransferA->getIdProductAbstractOrFail());
        $this->tester->haveProductAbstractAttachmentRelation($productAbstractTransferB->getIdProductAbstractOrFail(), $productAttachmentTransfer->getIdProductAttachmentOrFail());
        $productAttachmentFacade = $this->getProductAttachmentFacadeWithPostSavePlugin();

        // Act
        $productAttachmentFacade->saveProductAbstractAttachmentCollection($this->setProductAttachments($productAbstractTransferA));
        $isSelectedAfterRemovalFromA = $this->tester->isProductAttachmentKnowledgeSelected($productAttachmentTransfer->getIdProductAttachmentOrFail());
        $productAttachmentFacade->saveProductAbstractAttachmentCollection(
            $this->setProductAttachments($productAbstractTransferB, (clone $productAttachmentTransfer)->setIsAssistantKnowledge(false)),
        );

        // Assert
        $this->assertTrue($this->tester->hasProductAttachment($productAttachmentTransfer->getIdProductAttachmentOrFail()));
        $this->assertTrue($isSelectedAfterRemovalFromA);
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($productAttachmentTransfer->getIdProductAttachmentOrFail()));
    }

    public function testGivenNoSelectionChangeWhenPostSaveThenExactlyOneQueryRuns(): void
    {
        // Arrange
        $selectedAttachment = $this->haveSelectedProductAttachment()->setIsAssistantKnowledge(true);
        $notSelectedAttachment = $this->tester->haveProductAttachment()->setIsAssistantKnowledge(false);
        $productAttachmentCollectionTransfer = $this->tester->createProductAttachmentCollection($selectedAttachment, $notSelectedAttachment);

        // Act
        $queryCount = $this->tester->countQueries(fn () => (new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin())->postSave($productAttachmentCollectionTransfer));

        // Assert
        $this->assertSame(1, $queryCount);
    }

    public function testGivenCreatesAndDeletesWhenPostSaveThenQueryCountStaysWithinTheBudget(): void
    {
        // Arrange
        $createdAttachments = [$this->tester->haveProductAttachment()->setIsAssistantKnowledge(true), $this->tester->haveProductAttachment()->setIsAssistantKnowledge(true)];
        $deletedAttachments = [$this->haveSelectedProductAttachment()->setIsAssistantKnowledge(false), $this->haveSelectedProductAttachment()->setIsAssistantKnowledge(false)];
        $productAttachmentCollectionTransfer = $this->tester->createProductAttachmentCollection(...$createdAttachments, ...$deletedAttachments);

        // Act
        $queryCount = $this->tester->countQueries(fn () => (new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin())->postSave($productAttachmentCollectionTransfer));

        // Assert
        $this->assertLessThanOrEqual($this->calculateQueryBudget(count($createdAttachments), count($deletedAttachments)), $queryCount);
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($createdAttachments[0]->getIdProductAttachmentOrFail()));
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($deletedAttachments[0]->getIdProductAttachmentOrFail()));
    }

    protected function haveSelectedProductAttachment(?int $idProductAbstract = null): ProductAttachmentTransfer
    {
        $productAttachmentTransfer = $this->tester->haveProductAttachment($idProductAbstract);
        $this->tester->haveProductAttachmentKnowledgeSelection($productAttachmentTransfer->getIdProductAttachmentOrFail());

        return $productAttachmentTransfer;
    }

    protected function createNewAttachment(string $url, bool $isAssistantKnowledge): ProductAttachmentTransfer
    {
        return (new ProductAttachmentTransfer())
            ->setLabel(uniqid('Attachment ', true))
            ->setUrl($url)
            ->setIsAssistantKnowledge($isAssistantKnowledge);
    }

    protected function setProductAttachments(
        ProductAbstractTransfer $productAbstractTransfer,
        ProductAttachmentTransfer ...$productAttachmentTransfers
    ): ProductAbstractTransfer {
        return $productAbstractTransfer->setProductAttachments(new ArrayObject($productAttachmentTransfers));
    }

    protected function getProductAttachmentFacadeWithPostSavePlugin(): ProductAttachmentFacadeInterface
    {
        $this->tester->setDependency(
            ProductAttachmentDependencyProvider::PLUGINS_PRODUCT_ATTACHMENT_COLLECTION_POST_SAVE,
            [new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin()],
        );

        return $this->tester->getLocator()->productAttachment()->facade();
    }

    protected function calculateQueryBudget(int $createCount, int $deleteCount): int
    {
        $selectionQueryCount = 1 + ($deleteCount > 0 ? 1 : 0) + $createCount + $deleteCount;
        $eventChangeLogInsertCount = $createCount + $deleteCount;
        $sequenceReadCount = $this->tester->isPostgreSql() ? $createCount : 0;

        return $selectionQueryCount + $eventChangeLogInsertCount + $sequenceReadCount;
    }
}
