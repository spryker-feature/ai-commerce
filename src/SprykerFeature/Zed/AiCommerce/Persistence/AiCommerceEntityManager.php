<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversation;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommercePersistenceFactory getFactory()
 */
class AiCommerceEntityManager extends AbstractEntityManager implements AiCommerceEntityManagerInterface
{
    public function createBackofficeAssistantConversation(
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): BackofficeAssistantConversationTransfer {
        $mapper = $this->getFactory()->createBackofficeAssistantConversationMapper();

        $entity = $mapper->mapTransferToEntity($conversationTransfer, new SpyBackofficeAssistantConversation());

        $entity->save();

        return $mapper->mapEntityToTransfer($entity, $conversationTransfer);
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
            ->mapTransferToEntity($conversationTransfer, $entity);

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
}
