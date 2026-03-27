<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;

interface AiCommerceFacadeInterface
{
    /**
     * Specification:
     * - Returns conversation records matching the given criteria conditions.
     * - Filters by userUuids, conversationReferences, or backofficeAssistantConversationIds when provided.
     * - When conditions.withMessages is true, fetches and sets conversation messages from AiFoundation.
     * - Filters out messages with empty content when fetching messages.
     * - Supports sorting and pagination.
     *
     * @api
     */
    public function getBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCriteriaTransfer $criteriaTransfer,
    ): BackofficeAssistantConversationCollectionTransfer;

    /**
     * Specification:
     * - Creates conversation records from the given collection request.
     * - Generates a unique conversation reference for each conversation via AiCommerceService.
     * - Returns the created conversations with generated primary keys and conversation references set.
     *
     * @api
     */
    public function createBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;

    /**
     * Specification:
     * - Updates conversations from the given collection request.
     * - Updates agent field when set on the conversation transfer.
     * - Updates userSelectedAgent field (supports null for Auto mode).
     * - Identifies conversations by conversationReference.
     *
     * @api
     */
    public function updateBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;

    /**
     * Specification:
     * - Deletes conversation records matching the given delete criteria.
     * - Deletes by conversationReferences when provided.
     *
     * @api
     */
    public function deleteBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;
}
