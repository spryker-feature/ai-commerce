<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business;

use Generated\Shared\Transfer\AiTranslationCollectionRequestTransfer;
use Generated\Shared\Transfer\AiTranslationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\CategorySuggestionRequestTransfer;
use Generated\Shared\Transfer\CategorySuggestionResponseTransfer;
use Generated\Shared\Transfer\ContentImprovementRequestTransfer;
use Generated\Shared\Transfer\ContentImprovementResponseTransfer;
use Generated\Shared\Transfer\ImageAltTextRequestTransfer;
use Generated\Shared\Transfer\ImageAltTextResponseTransfer;

interface AiCommerceFacadeInterface
{
    /**
     * Specification:
     * - Returns conversation records matching the given criteria conditions.
     * - Uses BackofficeAssistantConversationCriteriaTransfer.backofficeAssistantConversationConditions for filtering.
     * - Filters by BackofficeAssistantConversationConditionsTransfer.idUsers when provided.
     * - Filters by BackofficeAssistantConversationConditionsTransfer.conversationReferences when provided.
     * - Filters by BackofficeAssistantConversationConditionsTransfer.backofficeAssistantConversationIds when provided.
     * - Limits the number of results by BackofficeAssistantConversationConditionsTransfer.limit when provided.
     * - When BackofficeAssistantConversationConditionsTransfer.withMessages is true, fetches conversation messages from AiFoundation.
     * - Extracts structured JSON content from assistant messages when present.
     * - Filters out messages with empty content and no tool invocations when fetching messages.
     *
     * @api
     */
    public function getBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCriteriaTransfer $criteriaTransfer,
    ): BackofficeAssistantConversationCollectionTransfer;

    /**
     * Specification:
     * - Creates conversation records from BackofficeAssistantConversationCollectionRequestTransfer.backofficeAssistantConversations.
     * - Requires BackofficeAssistantConversationTransfer.idUser to be set.
     * - Generates a unique conversation reference for each conversation.
     * - Persists each conversation.
     * - Returns BackofficeAssistantConversationCollectionResponseTransfer with created conversations including generated primary keys and conversation references.
     *
     * @api
     */
    public function createBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;

    /**
     * Specification:
     * - Updates conversations from BackofficeAssistantConversationCollectionRequestTransfer.backofficeAssistantConversations.
     * - Identifies conversations by BackofficeAssistantConversationTransfer.conversationReference.
     * - Persists updated conversation data via entity manager.
     * - Returns BackofficeAssistantConversationCollectionResponseTransfer with updated conversations.
     *
     * @api
     */
    public function updateBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;

    /**
     * Specification:
     * - Deletes conversation records matching the given delete criteria.
     * - Deletes by BackofficeAssistantConversationCollectionDeleteCriteriaTransfer.conversationReferences when provided.
     * - Returns BackofficeAssistantConversationCollectionResponseTransfer.
     *
     * @api
     */
    public function deleteBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer;

    /**
     * Specification:
     * - Proposes product category suggestions based on product name and description using AI.
     *
     * @api
     */
    public function proposeCategorySuggestions(
        CategorySuggestionRequestTransfer $categorySuggestionRequestTransfer,
    ): CategorySuggestionResponseTransfer;

    /**
     * Specification:
     * - Generates an SEO-optimized alt text for a product image using AI.
     *
     * @api
     */
    public function generateImageAltText(
        ImageAltTextRequestTransfer $imageAltTextRequestTransfer,
    ): ImageAltTextResponseTransfer;

    /**
     * Specification:
     * - Translates product content from a source locale to all target locales in a single AI request.
     *
     * @api
     */
    public function translateCollection(
        AiTranslationCollectionRequestTransfer $aiTranslationCollectionRequestTransfer,
    ): AiTranslationCollectionResponseTransfer;

    /**
     * Specification:
     * - Improves product content clarity, grammar, and readability using AI.
     *
     * @api
     */
    public function improveContent(ContentImprovementRequestTransfer $contentImprovementRequestTransfer): ContentImprovementResponseTransfer;
}
