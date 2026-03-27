<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Orm\Zed\AiCommerce\Persistence\SpyBackofficeAssistantConversation;

class BackofficeAssistantConversationMapper
{
    public function mapBackofficeAssistantConversationTransferToBackofficeAssistantConversationEntity(
        BackofficeAssistantConversationTransfer $transfer,
        SpyBackofficeAssistantConversation $entity,
    ): SpyBackofficeAssistantConversation {
        $entity->fromArray($transfer->modifiedToArray());

        return $entity;
    }

    public function mapBackofficeAssistantConversationEntityToBackofficeAssistantConversationTransfer(
        SpyBackofficeAssistantConversation $entity,
        BackofficeAssistantConversationTransfer $transfer,
    ): BackofficeAssistantConversationTransfer {
        return $transfer->fromArray($entity->toArray(), true);
    }
}
