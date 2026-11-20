<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Plugin\ProductAttachment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;
use Generated\Shared\Transfer\ProductAttachmentTransfer;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentCollectionExpanderPlugin;
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
 * @group KnowledgeSelectionProductAttachmentCollectionExpanderPluginTest
 * Add your own group annotations below this line
 */
class KnowledgeSelectionProductAttachmentCollectionExpanderPluginTest extends Unit
{
    protected const int QUERY_BUDGET_ATTACHMENT_COUNT = 20;

    protected AiCommerceCommunicationTester $tester;

    public function testGivenSelectedAndNotSelectedAttachmentsWhenExpandThenEachAttachmentCarriesItsSelectionState(): void
    {
        // Arrange
        $selectedAttachment = $this->haveSelectedProductAttachment();
        $notSelectedAttachment = $this->tester->haveProductAttachment();

        // Act
        $productAttachmentCollectionTransfer = (new KnowledgeSelectionProductAttachmentCollectionExpanderPlugin())
            ->expand($this->tester->createProductAttachmentCollection($selectedAttachment, $notSelectedAttachment));

        // Assert
        $this->assertSame([true, false], $this->getSelectionStates($productAttachmentCollectionTransfer));
    }

    public function testGivenAttachmentWithoutIdWhenExpandThenSelectionStateStaysNull(): void
    {
        // Arrange
        $newAttachment = (new ProductAttachmentTransfer())->setLabel('New')->setUrl('https://docs.example.com/new.pdf');

        // Act
        $productAttachmentCollectionTransfer = (new KnowledgeSelectionProductAttachmentCollectionExpanderPlugin())
            ->expand($this->tester->createProductAttachmentCollection($newAttachment));

        // Assert
        $this->assertSame([null], $this->getSelectionStates($productAttachmentCollectionTransfer));
    }

    public function testGivenEmptyCollectionWhenExpandThenNoQueryRunsAndCollectionStaysEmpty(): void
    {
        // Arrange
        $plugin = new KnowledgeSelectionProductAttachmentCollectionExpanderPlugin();
        $productAttachmentCollectionTransfer = new ProductAttachmentCollectionTransfer();

        // Act
        $queryCount = $this->tester->countQueries(fn () => $plugin->expand($productAttachmentCollectionTransfer));

        // Assert
        $this->assertSame(0, $queryCount);
        $this->assertCount(0, $productAttachmentCollectionTransfer->getProductAttachments());
    }

    public function testGivenTwentyAttachmentsWhenExpandThenExactlyOneQueryRuns(): void
    {
        // Arrange
        $plugin = new KnowledgeSelectionProductAttachmentCollectionExpanderPlugin();
        $productAttachmentCollectionTransfer = $this->haveProductAttachmentCollectionWithEverySecondSelected(static::QUERY_BUDGET_ATTACHMENT_COUNT);

        // Act
        $queryCount = $this->tester->countQueries(fn () => $plugin->expand($productAttachmentCollectionTransfer));

        // Assert
        $this->assertSame(1, $queryCount);
        $this->assertCount(static::QUERY_BUDGET_ATTACHMENT_COUNT / 2, array_filter($this->getSelectionStates($productAttachmentCollectionTransfer)));
    }

    protected function haveSelectedProductAttachment(): ProductAttachmentTransfer
    {
        $productAttachmentTransfer = $this->tester->haveProductAttachment();
        $this->tester->haveProductAttachmentKnowledgeSelection($productAttachmentTransfer->getIdProductAttachmentOrFail());

        return $productAttachmentTransfer;
    }

    protected function haveProductAttachmentCollectionWithEverySecondSelected(int $attachmentCount): ProductAttachmentCollectionTransfer
    {
        $productAttachmentCollectionTransfer = new ProductAttachmentCollectionTransfer();

        for ($index = 0; $index < $attachmentCount; $index++) {
            $productAttachmentCollectionTransfer->addProductAttachment(
                $index % 2 === 0 ? $this->haveSelectedProductAttachment() : $this->tester->haveProductAttachment(),
            );
        }

        return $productAttachmentCollectionTransfer;
    }

    /**
     * @return list<bool|null>
     */
    protected function getSelectionStates(ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer): array
    {
        $selectionStates = [];

        foreach ($productAttachmentCollectionTransfer->getProductAttachments() as $productAttachmentTransfer) {
            $selectionStates[] = $productAttachmentTransfer->getIsAssistantKnowledge();
        }

        return $selectionStates;
    }
}
