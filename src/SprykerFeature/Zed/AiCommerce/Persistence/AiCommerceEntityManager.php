<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionConditionsTransfer;
use Generated\Shared\Transfer\ProductAttachmentKnowledgeSelectionTransfer;
use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversation;
use Orm\Zed\AiCommerce\Persistence\SpyKnowledgeDocument;
use Orm\Zed\AiCommerce\Persistence\SpyProductAttachmentKnowledgeSelection;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommercePersistenceFactory getFactory()
 */
class AiCommerceEntityManager extends AbstractEntityManager implements AiCommerceEntityManagerInterface
{
    use ActiveRecordBatchProcessorTrait;

    public function createBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): BackofficeAssistantConversationTransfer {
        $mapper = $this->getFactory()->createBackofficeAssistantConversationMapper();

        $entity = $mapper->mapBackofficeAssistantConversationTransferToBackofficeAssistantConversationEntity($conversationTransfer, new SpyBackofficeAssistantConversation());

        $entity->save();

        return $mapper->mapBackofficeAssistantConversationEntityToBackofficeAssistantConversationTransfer($entity, $conversationTransfer);
    }

    public function updateBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): void {
        $entity = $this->getFactory()
            ->createBackofficeAssistantConversationQuery()
            ->filterByConversationReference($conversationTransfer->getConversationReferenceOrFail())
            ->findOne();

        if ($entity === null) {
            return;
        }

        $entity = $this->getFactory()
            ->createBackofficeAssistantConversationMapper()
            ->mapBackofficeAssistantConversationTransferToBackofficeAssistantConversationEntity($conversationTransfer, $entity);

        $entity->save();
    }

    public function deleteBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): void {
        $this->getFactory()
            ->createBackofficeAssistantConversationQuery()
            ->filterByConversationReference($conversationTransfer->getConversationReferenceOrFail())
            ->delete();
    }

    public function saveKnowledgeDocument(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
    ): KnowledgeDocumentTransfer {
        $knowledgeDocumentEntity = $this->getFactory()
            ->createKnowledgeDocumentQuery()
            ->filterBySourceType($knowledgeDocumentTransfer->getSourceTypeOrFail())
            ->filterBySourceIdentifier($knowledgeDocumentTransfer->getSourceIdentifierOrFail())
            ->findOne() ?? new SpyKnowledgeDocument();

        $knowledgeDocumentMapper = $this->getFactory()->createKnowledgeDocumentMapper();
        $knowledgeDocumentEntity = $knowledgeDocumentMapper->mapKnowledgeDocumentTransferToKnowledgeDocumentEntity(
            $knowledgeDocumentTransfer,
            $knowledgeDocumentEntity,
        );

        $knowledgeDocumentEntity->save();

        return $knowledgeDocumentMapper->mapKnowledgeDocumentEntityToKnowledgeDocumentTransfer(
            $knowledgeDocumentEntity,
            $knowledgeDocumentTransfer,
        );
    }

    /**
     * @param list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     */
    public function saveKnowledgeDocumentCollection(string $sourceType, array $knowledgeDocumentTransfers): void
    {
        if ($knowledgeDocumentTransfers === []) {
            return;
        }

        $knowledgeDocumentEntities = $this->getKnowledgeDocumentEntitiesBySourceIdentifier($sourceType, $knowledgeDocumentTransfers);
        $knowledgeDocumentMapper = $this->getFactory()->createKnowledgeDocumentMapper();

        foreach ($knowledgeDocumentTransfers as $knowledgeDocumentTransfer) {
            $this->persist($knowledgeDocumentMapper->mapKnowledgeDocumentTransferToKnowledgeDocumentEntity(
                $knowledgeDocumentTransfer->setSourceType($sourceType),
                $knowledgeDocumentEntities[$knowledgeDocumentTransfer->getSourceIdentifierOrFail()] ?? new SpyKnowledgeDocument(),
            ));
        }

        $this->commit();
    }

    /**
     * @param list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     *
     * @return array<string, \Orm\Zed\AiCommerce\Persistence\SpyKnowledgeDocument>
     */
    protected function getKnowledgeDocumentEntitiesBySourceIdentifier(string $sourceType, array $knowledgeDocumentTransfers): array
    {
        $sourceIdentifiers = array_map(
            static fn (KnowledgeDocumentTransfer $knowledgeDocumentTransfer): string => $knowledgeDocumentTransfer->getSourceIdentifierOrFail(),
            $knowledgeDocumentTransfers,
        );

        $knowledgeDocumentEntities = [];
        $knowledgeDocumentEntityCollection = $this->getFactory()
            ->createKnowledgeDocumentQuery()
            ->filterBySourceType($sourceType)
            ->filterBySourceIdentifier_In($sourceIdentifiers)
            ->find();

        foreach ($knowledgeDocumentEntityCollection as $knowledgeDocumentEntity) {
            $knowledgeDocumentEntities[$knowledgeDocumentEntity->getSourceIdentifier()] = $knowledgeDocumentEntity;
        }

        return $knowledgeDocumentEntities;
    }

    public function deleteKnowledgeDocument(KnowledgeDocumentTransfer $knowledgeDocumentTransfer): void
    {
        $this->getFactory()
            ->createKnowledgeDocumentQuery()
            ->filterBySourceType($knowledgeDocumentTransfer->getSourceTypeOrFail())
            ->filterBySourceIdentifier($knowledgeDocumentTransfer->getSourceIdentifierOrFail())
            ->delete();
    }

    public function createProductAttachmentKnowledgeSelection(
        ProductAttachmentKnowledgeSelectionTransfer $productAttachmentKnowledgeSelectionTransfer,
    ): ProductAttachmentKnowledgeSelectionTransfer {
        $productAttachmentKnowledgeSelectionMapper = $this->getFactory()->createProductAttachmentKnowledgeSelectionMapper();
        $productAttachmentKnowledgeSelectionEntity = $productAttachmentKnowledgeSelectionMapper->mapProductAttachmentKnowledgeSelectionTransferToEntity(
            $productAttachmentKnowledgeSelectionTransfer,
            new SpyProductAttachmentKnowledgeSelection(),
        );

        $productAttachmentKnowledgeSelectionEntity->save();

        return $productAttachmentKnowledgeSelectionMapper->mapProductAttachmentKnowledgeSelectionEntityToTransfer(
            $productAttachmentKnowledgeSelectionEntity,
            $productAttachmentKnowledgeSelectionTransfer,
        );
    }

    public function deleteProductAttachmentKnowledgeSelections(
        ProductAttachmentKnowledgeSelectionConditionsTransfer $productAttachmentKnowledgeSelectionConditionsTransfer,
    ): void {
        if ($productAttachmentKnowledgeSelectionConditionsTransfer->getProductAttachmentIds() === []) {
            return;
        }

        $productAttachmentKnowledgeSelectionEntities = $this->getFactory()
            ->createProductAttachmentKnowledgeSelectionQuery()
            ->filterByFkProductAttachment_In($productAttachmentKnowledgeSelectionConditionsTransfer->getProductAttachmentIds())
            ->find();

        foreach ($productAttachmentKnowledgeSelectionEntities as $productAttachmentKnowledgeSelectionEntity) {
            $productAttachmentKnowledgeSelectionEntity->delete();
        }
    }
}
