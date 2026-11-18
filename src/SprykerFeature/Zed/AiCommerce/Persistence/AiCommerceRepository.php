<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use ArrayObject;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCollectionTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemConditionsTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCriteriaTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemTransfer;
use Orm\Zed\AiCommerce\Persistence\Map\SpyKnowledgeDocumentTableMap;
use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversationQuery;
use Orm\Zed\AiCommerce\Persistence\SpyKnowledgeDocumentQuery;
use Orm\Zed\Content\Persistence\SpyContentQuery;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommercePersistenceFactory getFactory()
 */
class AiCommerceRepository extends AbstractRepository implements AiCommerceRepositoryInterface
{
    /**
     * Generic persistence safety cap, applied only when a caller provides no pagination limit, to avoid loading the entire content table. The feature-level content-item lookup limit is owned by AiCommerceConfig::getSmartCmsContentItemLookupLimit() and is always set on the criteria by the business layer.
     */
    protected const int CONTENT_ITEM_SAFETY_LIMIT = 1000;

    /**
     * @var array<string, string>
     */
    protected const array KNOWLEDGE_DOCUMENT_SORTABLE_FIELDS = [
        KnowledgeDocumentTransfer::ID_KNOWLEDGE_DOCUMENT => SpyKnowledgeDocumentTableMap::COL_ID_KNOWLEDGE_DOCUMENT,
        KnowledgeDocumentTransfer::SOURCE_TYPE => SpyKnowledgeDocumentTableMap::COL_SOURCE_TYPE,
        KnowledgeDocumentTransfer::SOURCE_IDENTIFIER => SpyKnowledgeDocumentTableMap::COL_SOURCE_IDENTIFIER,
        KnowledgeDocumentTransfer::STATUS => SpyKnowledgeDocumentTableMap::COL_STATUS,
        KnowledgeDocumentTransfer::INDEXED_AT => SpyKnowledgeDocumentTableMap::COL_INDEXED_AT,
    ];

    public function getBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCriteriaTransfer $criteriaTransfer,
    ): BackofficeAssistantConversationCollectionTransfer {
        $conditions = $criteriaTransfer->getBackofficeAssistantConversationConditions();

        if ($conditions === null) {
            return new BackofficeAssistantConversationCollectionTransfer();
        }

        $query = $this->applyConditionsToQuery(
            $this->getFactory()->createBackofficeAssistantConversationQuery(),
            $conditions,
        );

        $mapper = $this->getFactory()->createBackofficeAssistantConversationMapper();
        $conversations = [];

        foreach ($query->find() as $entity) {
            $conversations[] = $mapper->mapBackofficeAssistantConversationEntityToBackofficeAssistantConversationTransfer($entity, new BackofficeAssistantConversationTransfer());
        }

        return (new BackofficeAssistantConversationCollectionTransfer())
            ->setBackofficeAssistantConversations(new ArrayObject($conversations));
    }

    /**
     * {@inheritDoc}
     *
     * @module Content
     */
    public function getSmartCmsContentItemCollection(
        SmartCmsContentItemCriteriaTransfer $smartCmsContentItemCriteriaTransfer,
    ): SmartCmsContentItemCollectionTransfer {
        $query = $this->getFactory()->getContentPropelQuery();
        $smartCmsContentItemConditionsTransfer = $smartCmsContentItemCriteriaTransfer->getSmartCmsContentItemConditions();

        if ($smartCmsContentItemConditionsTransfer !== null) {
            $query = $this->applyContentItemConditionsToQuery($query, $smartCmsContentItemConditionsTransfer);
        }

        $limit = $smartCmsContentItemCriteriaTransfer->getPagination()?->getLimit() ?? static::CONTENT_ITEM_SAFETY_LIMIT;
        $query->limit($limit);

        $query->orderByName(Criteria::ASC);

        $mapper = $this->getFactory()->createSmartCmsContentItemMapper();
        $smartCmsContentItemTransfers = [];

        foreach ($query->find() as $contentEntity) {
            $smartCmsContentItemTransfers[] = $mapper->mapContentEntityToSmartCmsContentItemTransfer(
                $contentEntity,
                new SmartCmsContentItemTransfer(),
            );
        }

        return (new SmartCmsContentItemCollectionTransfer())
            ->setContentItems(new ArrayObject($smartCmsContentItemTransfers));
    }

    /**
     * @module Content
     */
    protected function applyContentItemConditionsToQuery(
        SpyContentQuery $query,
        SmartCmsContentItemConditionsTransfer $smartCmsContentItemConditionsTransfer,
    ): SpyContentQuery {
        $contentTypeKeys = $smartCmsContentItemConditionsTransfer->getContentTypeKeys();

        if ($contentTypeKeys !== []) {
            $query->filterByContentTypeKey_In($contentTypeKeys);
        }

        return $query;
    }

    /**
     * @module Sales
     * @module Oms
     *
     * @return array{processName: ?string, stateNames: array<string>}
     */
    public function findProcessAndStateNamesByOrderReference(string $orderReference): array
    {
        $salesOrderItemQuery = $this->getFactory()->getSalesOrderItemPropelQuery();
        $salesOrderItemQuery
            ->joinWithProcess()
            ->joinWithState()
            ->useOrderQuery()
                ->filterByOrderReference($orderReference)
            ->endUse();

        $salesOrderItemEntities = $salesOrderItemQuery->find();

        if ($salesOrderItemEntities->count() === 0) {
            return ['processName' => null, 'stateNames' => []];
        }

        $processName = null;
        $stateNames = [];

        foreach ($salesOrderItemEntities as $salesOrderItemEntity) {
            /** @var \Orm\Zed\Sales\Persistence\SpySalesOrderItem $salesOrderItemEntity */
            $processName = $processName ?? $salesOrderItemEntity->getProcess()?->getName();
            $stateName = (string)$salesOrderItemEntity->getState()->getName();

            if (!in_array($stateName, $stateNames, true)) {
                $stateNames[] = $stateName;
            }
        }

        return ['processName' => $processName, 'stateNames' => $stateNames];
    }

    /**
     * {@inheritDoc}
     *
     * @module Discount
     */
    public function existsDiscountByDisplayName(string $displayName): bool
    {
        return $this->getFactory()
            ->getDiscountPropelQuery()
            ->filterByDisplayName($displayName)
            ->exists();
    }

    protected function applyConditionsToQuery(
        SpyBackofficeAssistantConversationQuery $query,
        BackofficeAssistantConversationConditionsTransfer $conditions,
    ): SpyBackofficeAssistantConversationQuery {
        $idUsers = $conditions->getIdUsers();

        if ($idUsers !== []) {
            $query->filterByFkUser_In($idUsers);
        }

        $conversationReferences = $conditions->getConversationReferences();

        if ($conversationReferences !== []) {
            $query->filterByConversationReference_In($conversationReferences);
        }

        $conversationIds = $conditions->getBackofficeAssistantConversationIds();

        if ($conversationIds !== []) {
            $query->filterByIdBackofficeAssistantConversation_In($conversationIds);
        }

        $query->orderByIdBackofficeAssistantConversation(Criteria::DESC);

        if ($conditions->getLimit() !== null) {
            $query->limit($conditions->getLimit());
        }

        return $query;
    }

    public function getKnowledgeDocumentCollection(
        KnowledgeDocumentCriteriaTransfer $knowledgeDocumentCriteriaTransfer,
    ): KnowledgeDocumentCollectionTransfer {
        $knowledgeDocumentQuery = $this->getFactory()->createKnowledgeDocumentQuery();
        $knowledgeDocumentConditionsTransfer = $knowledgeDocumentCriteriaTransfer->getKnowledgeDocumentConditions();

        if ($knowledgeDocumentConditionsTransfer !== null) {
            $knowledgeDocumentQuery = $this->applyKnowledgeDocumentConditions(
                $knowledgeDocumentQuery,
                $knowledgeDocumentConditionsTransfer,
            );
        }

        $knowledgeDocumentQuery = $this->applyKnowledgeDocumentSorting(
            $knowledgeDocumentQuery,
            $knowledgeDocumentCriteriaTransfer,
        );

        $knowledgeDocumentCollectionTransfer = new KnowledgeDocumentCollectionTransfer();
        $paginationTransfer = $knowledgeDocumentCriteriaTransfer->getPagination();

        if ($paginationTransfer !== null) {
            $knowledgeDocumentQuery = $this->applyKnowledgeDocumentPagination($knowledgeDocumentQuery, $paginationTransfer);
            $knowledgeDocumentCollectionTransfer->setPagination($paginationTransfer);
        }

        $knowledgeDocumentMapper = $this->getFactory()->createKnowledgeDocumentMapper();

        foreach ($knowledgeDocumentQuery->find() as $knowledgeDocumentEntity) {
            $knowledgeDocumentCollectionTransfer->addKnowledgeDocument(
                $knowledgeDocumentMapper->mapKnowledgeDocumentEntityToKnowledgeDocumentTransfer(
                    $knowledgeDocumentEntity,
                    new KnowledgeDocumentTransfer(),
                ),
            );
        }

        return $knowledgeDocumentCollectionTransfer;
    }

    protected function applyKnowledgeDocumentConditions(
        SpyKnowledgeDocumentQuery $knowledgeDocumentQuery,
        KnowledgeDocumentConditionsTransfer $knowledgeDocumentConditionsTransfer,
    ): SpyKnowledgeDocumentQuery {
        if ($knowledgeDocumentConditionsTransfer->getSourceTypes() !== []) {
            $knowledgeDocumentQuery->filterBySourceType_In($knowledgeDocumentConditionsTransfer->getSourceTypes());
        }

        if ($knowledgeDocumentConditionsTransfer->getSourceIdentifiers() !== []) {
            $knowledgeDocumentQuery->filterBySourceIdentifier_In($knowledgeDocumentConditionsTransfer->getSourceIdentifiers());
        }

        if ($knowledgeDocumentConditionsTransfer->getStatuses() !== []) {
            $knowledgeDocumentQuery->filterByStatus_In($knowledgeDocumentConditionsTransfer->getStatuses());
        }

        return $knowledgeDocumentQuery;
    }

    protected function applyKnowledgeDocumentSorting(
        SpyKnowledgeDocumentQuery $knowledgeDocumentQuery,
        KnowledgeDocumentCriteriaTransfer $knowledgeDocumentCriteriaTransfer,
    ): SpyKnowledgeDocumentQuery {
        foreach ($knowledgeDocumentCriteriaTransfer->getSortCollection() as $sortTransfer) {
            $columnName = static::KNOWLEDGE_DOCUMENT_SORTABLE_FIELDS[$sortTransfer->getField()] ?? null;

            if ($columnName === null) {
                continue;
            }

            $knowledgeDocumentQuery->orderBy($columnName, $sortTransfer->getIsAscending() === false ? Criteria::DESC : Criteria::ASC);
        }

        return $knowledgeDocumentQuery->orderByIdKnowledgeDocument(Criteria::ASC);
    }

    protected function applyKnowledgeDocumentPagination(
        SpyKnowledgeDocumentQuery $knowledgeDocumentQuery,
        PaginationTransfer $paginationTransfer,
    ): SpyKnowledgeDocumentQuery {
        if ($paginationTransfer->getLimit() !== null) {
            $knowledgeDocumentQuery->limit(max(1, $paginationTransfer->getLimit()));
        }

        if ($paginationTransfer->getOffset() !== null) {
            $knowledgeDocumentQuery->offset(max(0, $paginationTransfer->getOffset()));
        }

        return $knowledgeDocumentQuery;
    }
}
