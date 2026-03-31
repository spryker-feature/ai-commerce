<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;

interface AiCommerceRepositoryInterface
{
    /**
     * Specification:
     * - Returns conversation records matching the given criteria conditions.
     * - Filters by userUuids, conversationReferences, or backofficeAssistantConversationIds when provided.
     * - Results are ordered by ID descending.
     */
    public function getBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCriteriaTransfer $criteriaTransfer,
    ): BackofficeAssistantConversationCollectionTransfer;

    /**
     * Specification:
     * - Finds the OMS process name and distinct item state names for the given order reference.
     * - Returns array with 'processName' (nullable string) and 'stateNames' (array of strings).
     *
     * @return array{processName: ?string, stateNames: array<string>}
     */
    public function findProcessAndStateNamesByOrderReference(string $orderReference): array;

    public function existsDiscountByDisplayName(string $displayName): bool;
}
