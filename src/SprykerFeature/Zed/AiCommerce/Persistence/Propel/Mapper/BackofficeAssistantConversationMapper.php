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
        BackofficeAssistantConversationTransfer $conversationTransfer,
        SpyBackofficeAssistantConversation $conversationEntity,
    ): SpyBackofficeAssistantConversation {
        $conversationEntity->fromArray($conversationTransfer->modifiedToArray());
        if ($conversationTransfer->getIdUser() !== null) {
            $conversationEntity->setFkUser($conversationTransfer->getIdUser());
        }

        return $conversationEntity;
    }

    public function mapBackofficeAssistantConversationEntityToBackofficeAssistantConversationTransfer(
        SpyBackofficeAssistantConversation $conversationEntity,
        BackofficeAssistantConversationTransfer $conversationTransfer,
    ): BackofficeAssistantConversationTransfer {
        $conversationTransfer->fromArray($conversationEntity->toArray(), true);
        $conversationTransfer->setIdUser($conversationEntity->getFkUser());

        return $conversationTransfer;
    }
}
