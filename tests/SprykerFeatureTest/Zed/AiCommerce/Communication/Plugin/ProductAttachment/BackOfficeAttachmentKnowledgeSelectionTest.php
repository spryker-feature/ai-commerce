<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Plugin\ProductAttachment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Spryker\Zed\ProductAttachment\Communication\Plugin\ProductManagement\ProductAttachmentProductAbstractFormDataProviderExpanderPlugin;
use Spryker\Zed\ProductAttachment\Communication\Plugin\ProductManagement\ProductAttachmentProductAbstractTransferMapperPlugin;
use Spryker\Zed\ProductAttachment\ProductAttachmentDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentCollectionExpanderPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentCollectionPostSavePlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentFormExpanderPlugin;
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
 * @group BackOfficeAttachmentKnowledgeSelectionTest
 * Add your own group annotations below this line
 */
class BackOfficeAttachmentKnowledgeSelectionTest extends Unit
{
    /**
     * @uses \Spryker\Zed\ProductAttachment\ProductAttachmentConfig::getAttachmentFormName()
     */
    protected const string FORM_NAME_DEFAULT_LOCALE = 'attachment_default';

    /**
     * @uses \Spryker\Zed\ProductAttachment\Communication\Form\Product\AttachmentForm::FIELD_ID_PRODUCT_ATTACHMENT
     */
    protected const string FIELD_ID_PRODUCT_ATTACHMENT = 'id_product_attachment';

    protected const string FIELD_IS_ASSISTANT_KNOWLEDGE = 'is_assistant_knowledge';

    protected const string URL_NEW_ATTACHMENT = 'https://docs.example.com/new-row-%s.pdf';

    protected AiCommerceCommunicationTester $tester;

    public function testGivenStoredSelectionsWhenEditPageDataIsLoadedThenEachRowShowsItsSelectionState(): void
    {
        // Arrange
        $this->wireKnowledgeSelectionPlugins();
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        [$idSelectedAttachment, $idNotSelectedAttachment] = $this->haveSelectedAndNotSelectedAttachments($productAbstractTransfer);

        // Act
        $formData = (new ProductAttachmentProductAbstractFormDataProviderExpanderPlugin())->expand([], $productAbstractTransfer);

        // Assert
        $rowsByIdProductAttachment = $this->indexDefaultRowsByIdProductAttachment($formData);
        $this->assertTrue($rowsByIdProductAttachment[$idSelectedAttachment][static::FIELD_IS_ASSISTANT_KNOWLEDGE]);
        $this->assertFalse($rowsByIdProductAttachment[$idNotSelectedAttachment][static::FIELD_IS_ASSISTANT_KNOWLEDGE]);
    }

    public function testGivenToggledCheckboxesAndNewCheckedRowWhenFormIsSubmittedThenSelectionsFollowTheCheckboxes(): void
    {
        // Arrange
        $this->wireKnowledgeSelectionPlugins();
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        [$idSelectedAttachment, $idNotSelectedAttachment] = $this->haveSelectedAndNotSelectedAttachments($productAbstractTransfer);
        $newAttachmentUrl = sprintf(static::URL_NEW_ATTACHMENT, uniqid('', true));
        $submittedData = $this->toggleCheckboxesAndAddCheckedRow(
            (new ProductAttachmentProductAbstractFormDataProviderExpanderPlugin())->expand([], $productAbstractTransfer),
            $newAttachmentUrl,
        );

        // Act
        $this->tester->getLocator()->productAttachment()->facade()->saveProductAbstractAttachmentCollection(
            (new ProductAttachmentProductAbstractTransferMapperPlugin())->map($submittedData, $productAbstractTransfer),
        );

        // Assert
        $this->assertFalse($this->tester->isProductAttachmentKnowledgeSelected($idSelectedAttachment));
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected($idNotSelectedAttachment));
        $this->assertTrue($this->tester->isProductAttachmentKnowledgeSelected((int)$this->tester->findProductAttachmentIdByUrl($newAttachmentUrl)));
    }

    protected function wireKnowledgeSelectionPlugins(): void
    {
        $this->tester->setDependency(ProductAttachmentDependencyProvider::PLUGINS_PRODUCT_ATTACHMENT_COLLECTION_EXPANDER, [new KnowledgeSelectionProductAttachmentCollectionExpanderPlugin()]);
        $this->tester->setDependency(ProductAttachmentDependencyProvider::PLUGINS_PRODUCT_ATTACHMENT_COLLECTION_POST_SAVE, [new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin()]);
        $this->tester->setDependency(ProductAttachmentDependencyProvider::PLUGINS_PRODUCT_ATTACHMENT_FORM_EXPANDER, [new KnowledgeSelectionProductAttachmentFormExpanderPlugin()]);
    }

    /**
     * @return array{int, int}
     */
    protected function haveSelectedAndNotSelectedAttachments(ProductAbstractTransfer $productAbstractTransfer): array
    {
        $idSelectedAttachment = $this->tester->haveProductAttachment($productAbstractTransfer->getIdProductAbstractOrFail())->getIdProductAttachmentOrFail();
        $idNotSelectedAttachment = $this->tester->haveProductAttachment($productAbstractTransfer->getIdProductAbstractOrFail())->getIdProductAttachmentOrFail();
        $this->tester->haveProductAttachmentKnowledgeSelection($idSelectedAttachment);

        return [$idSelectedAttachment, $idNotSelectedAttachment];
    }

    /**
     * @param array<string, mixed> $formData
     *
     * @return array<int, array<string, mixed>>
     */
    protected function indexDefaultRowsByIdProductAttachment(array $formData): array
    {
        $rowsByIdProductAttachment = [];

        foreach ($formData[static::FORM_NAME_DEFAULT_LOCALE] as $row) {
            $rowsByIdProductAttachment[$row[static::FIELD_ID_PRODUCT_ATTACHMENT]] = $row;
        }

        return $rowsByIdProductAttachment;
    }

    /**
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>
     */
    protected function toggleCheckboxesAndAddCheckedRow(array $formData, string $newAttachmentUrl): array
    {
        foreach ($formData[static::FORM_NAME_DEFAULT_LOCALE] as $index => $row) {
            $formData[static::FORM_NAME_DEFAULT_LOCALE][$index][static::FIELD_IS_ASSISTANT_KNOWLEDGE] = !$row[static::FIELD_IS_ASSISTANT_KNOWLEDGE];
        }

        $formData[static::FORM_NAME_DEFAULT_LOCALE][] = [
            static::FIELD_ID_PRODUCT_ATTACHMENT => null,
            'label' => 'New row',
            'url' => $newAttachmentUrl,
            'sort_order' => 9,
            static::FIELD_IS_ASSISTANT_KNOWLEDGE => true,
        ];

        return $formData;
    }
}
