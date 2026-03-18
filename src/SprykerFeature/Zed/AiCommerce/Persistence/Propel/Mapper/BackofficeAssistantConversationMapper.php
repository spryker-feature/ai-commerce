<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversation;
use Propel\Runtime\Collection\Collection;

class BackofficeAssistantConversationMapper
{
    public function mapTransferToEntity(
        BackofficeAssistantConversationTransfer $transfer,
        SpyBackofficeAssistantConversation $entity,
    ): SpyBackofficeAssistantConversation {
        $entity->fromArray($transfer->modifiedToArray());

        return $entity;
    }

    public function mapEntityToTransfer(
        SpyBackofficeAssistantConversation $entity,
        BackofficeAssistantConversationTransfer $transfer,
    ): BackofficeAssistantConversationTransfer {
        return $transfer->fromArray($entity->toArray(), true);
    }

    /**
     * @param \Propel\Runtime\Collection\Collection<\Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversation> $entityCollection
     * @param array<\Generated\Shared\Transfer\BackofficeAssistantConversationTransfer> $transferCollection
     *
     * @return array<\Generated\Shared\Transfer\BackofficeAssistantConversationTransfer>
     */
    public function mapEntityCollectionToTransferCollection(
        Collection $entityCollection,
        array $transferCollection,
    ): array {
        foreach ($entityCollection as $entity) {
            $transferCollection[] = $this->mapEntityToTransfer($entity, new BackofficeAssistantConversationTransfer());
        }

        return $transferCollection;
    }
}
