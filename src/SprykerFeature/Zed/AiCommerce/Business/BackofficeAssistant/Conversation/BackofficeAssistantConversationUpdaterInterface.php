<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;

interface BackofficeAssistantConversationUpdaterInterface
{
    /**
     * Specification:
     * - Updates conversations from the given collection request.
     * - Updates agent field when set on the conversation transfer.
     * - Updates userSelectedAgent field (supports null for Auto mode).
     * - Identifies conversations by conversationReference.
     */
    public function updateCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;
}
