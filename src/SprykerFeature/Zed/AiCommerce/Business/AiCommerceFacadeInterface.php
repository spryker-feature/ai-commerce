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
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;

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

    /**
     * Specification:
     * - Validates and resolves the conversation reference, creating one if needed.
     * - Routes the prompt to the appropriate agent via intent routing.
     * - Emits SSE events to stream progress to the client.
     *
     * @api
     */
    public function handleBackofficeAssistantPrompt(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
    ): void;

    /**
     * Specification:
     * - Executes the general-purpose agent for the given prompt request.
     * - Sends prompt to AI foundation using general-purpose configuration.
     *
     * @api
     */
    public function executeGeneralPurposeAgent(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
    ): BackofficeAssistantPromptResponseTransfer;

    /**
     * Specification:
     * - Executes the Order Management agent for the given prompt request.
     * - Sends prompt to AI foundation using order management configuration with OMS tools.
     *
     * @api
     */
    public function executeOrderManagementAgent(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
    ): BackofficeAssistantPromptResponseTransfer;

    /**
     * Specification:
     * - Returns current OMS state and available transitions for an order by reference.
     * - Returns JSON string with current states and transition details.
     *
     * @api
     */
    public function getOrderOmsTransitions(string $orderReference): string;

    /**
     * Specification:
     * - Returns basic order details by order reference as a JSON string.
     * - Includes items, totals, customer info, and dates.
     *
     * @api
     */
    public function getOrderDetails(string $orderReference): string;

    /**
     * Specification:
     * - Returns available manual events for an order by reference as a JSON string.
     *
     * @api
     */
    public function getOrderManualEvents(string $orderReference): string;

    /**
     * Specification:
     * - Returns the full OMS process definition for the process associated with an order.
     * - Includes all states, transitions, events, and subprocesses.
     * - Returns JSON string.
     *
     * @api
     */
    public function getOmsProcessDefinition(string $orderReference): string;

    /**
     * Specification:
     * - Returns state flags (such as cancellable or reserved) for the current states of an order.
     * - Returns JSON string mapping state names to their flags.
     *
     * @api
     */
    public function getOrderStateFlags(string $orderReference): string;
}
