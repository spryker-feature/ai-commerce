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
use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversationQuery;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderItemStateTableMap;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderProcessTableMap;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommercePersistenceFactory getFactory()
 */
class AiCommerceRepository extends AbstractRepository implements AiCommerceRepositoryInterface
{
    protected const string PROCESS_NAME = 'processName';

    protected const string STATE_NAMES = 'stateNames';

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
            $conversations[] = $mapper->mapEntityToTransfer($entity, new BackofficeAssistantConversationTransfer());
        }

        return (new BackofficeAssistantConversationCollectionTransfer())
            ->setBackofficeAssistantConversations(new ArrayObject($conversations));
    }

    /**
     * @module Sales
     * @module Oms
     */
    public function findProcessNameByOrderReference(string $orderReference): ?string
    {
        /** @var string|null $result */
        $result = $this->getFactory()->getSalesOrderPropelQuery()
            ->filterByOrderReference($orderReference)
            ->useItemQuery()
                ->useProcessQuery()
                    ->withColumn(SpyOmsOrderProcessTableMap::COL_NAME, static::PROCESS_NAME)
                ->endUse()
                ->groupBy(SpyOmsOrderProcessTableMap::COL_NAME)
            ->endUse()
            ->select([static::PROCESS_NAME])
            ->findOne();

        return $result;
    }

    /**
     * @module Sales
     * @module Oms
     *
     * @return array{processName: ?string, stateNames: array<string>}
     */
    public function findProcessAndStateNamesByOrderReference(string $orderReference): array
    {
        /** @var array<string, string|null>|null $result */
        $result = $this->getFactory()->getSalesOrderPropelQuery()
            ->filterByOrderReference($orderReference)
            ->withItemQuery(function (SpySalesOrderItemQuery $itemQuery): SpySalesOrderItemQuery {
                $itemQuery
                    ->useProcessQuery()
                        ->withColumn(SpyOmsOrderProcessTableMap::COL_NAME, static::PROCESS_NAME)
                    ->endUse();

                /** @var \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $itemQueryWithProcess */
                $itemQueryWithProcess = $itemQuery;
                $itemQueryWithProcess
                    ->useStateQuery()
                        ->withColumn(sprintf('GROUP_CONCAT(DISTINCT %s)', SpyOmsOrderItemStateTableMap::COL_NAME), static::STATE_NAMES)
                    ->endUse()
                    ->groupBy(SpyOmsOrderProcessTableMap::COL_NAME);

                return $itemQuery;
            })
            ->select([static::PROCESS_NAME, static::STATE_NAMES])
            ->findOne();

        if ($result === null) {
            return ['processName' => null, 'stateNames' => []];
        }

        $processName = $result[static::PROCESS_NAME];
        $stateNamesRaw = $result[static::STATE_NAMES];
        /** @var array<string> $stateNames */
        $stateNames = $stateNamesRaw ? explode(',', $stateNamesRaw) : [];

        return ['processName' => $processName, 'stateNames' => $stateNames];
    }

    protected function applyConditionsToQuery(
        SpyBackofficeAssistantConversationQuery $query,
        BackofficeAssistantConversationConditionsTransfer $conditions,
    ): SpyBackofficeAssistantConversationQuery {
        $userUuids = $conditions->getUserUuids();

        if ($userUuids !== []) {
            $query->filterByUserUuid_In($userUuids);
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

        return $query;
    }
}
