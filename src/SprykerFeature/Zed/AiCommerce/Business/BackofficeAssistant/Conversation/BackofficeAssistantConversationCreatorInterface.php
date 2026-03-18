<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;

interface BackofficeAssistantConversationCreatorInterface
{
    /**
     * Specification:
     * - Creates conversation records from the given collection request.
     * - Generates a unique conversation reference for each conversation via AiCommerceService.
     * - Returns the created conversations with generated primary keys and conversation references set.
     */
    public function createCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;
}
