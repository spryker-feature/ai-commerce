<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionTransfer;

interface AiCommerceClientInterface
{
    /**
     * Specification:
     * - Accepts an image (base64-encoded content and media type) and a locale name.
     * - Delegates to AiSearchByImageTermResolver which calls AiFoundationClient to identify the product in the image.
     * - Extracts a search term from the AI response.
     * - Returns a SearchByImageResponseTransfer with searchTerm and isSuccessful=true on success.
     * - Returns a SearchByImageResponseTransfer with isSuccessful=false and errors on AI failure.
     *
     * @api
     */
    public function getSearchTermFromImage(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer;

    /**
     * Specification:
     * - Rejects the turn when the Storefront Assistant feature is disabled, before any AI provider call.
     * - Rejects the turn when the request carries no customer reference, before any AI provider call.
     * - Initializes the chat session through the AI Foundation gateway, resolving the AI configuration and the
     *   owner scoped chat history in a single call.
     * - Streams the AI provider response directly from the Client layer, emitting every event to the output stream.
     * - Emits the selected agent, and every rejection or failure, as a stream event; the turn reports no return value.
     * - Persists the customer visible conversation and flushes the chat history through the AI Foundation gateway.
     * - Performs exactly two gateway calls per turn.
     *
     * @api
     */
    public function executeStorefrontAssistantChat(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): void;

    /**
     * Specification:
     * - Runs the turn pre-flight checks without contacting any AI provider: feature gate, customer reference, message, and attachment validation.
     * - Validates every attachment server-side by MIME type and decoded byte size against the module configuration, independently of the browser.
     * - Returns an unsuccessful response carrying the rejection reason when any check fails, so the transport can answer with an HTTP 400 before streaming.
     * - Returns a successful response and moves the validated attachments onto the request transfer when every check passes.
     * - Makes zero gateway or AI provider calls.
     *
     * @api
     */
    public function validateStorefrontAssistantChatRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): StorefrontAssistantChatResponseTransfer;

    /**
     * Specification:
     * - Reads the customer visible conversations owned by the customer reference carried on the criteria conditions.
     * - Scopes every read to that owner; a customer never receives another customer's conversations.
     * - Returns an empty collection when the owner reference is blank, never all conversations.
     * - Includes the conversation messages only when the conditions request them.
     *
     * @api
     */
    public function getStorefrontAssistantConversationCollection(
        StorefrontAssistantConversationCollectionCriteriaTransfer $storefrontAssistantConversationCollectionCriteriaTransfer
    ): StorefrontAssistantConversationCollectionTransfer;

    /**
     * Specification:
     * - Deletes the requested conversations scoped to the owner reference carried on the delete criteria.
     * - Every delete is owner scoped; a reference the owner does not hold is a no-op and the owner's data stays intact.
     * - Reports success even when a requested reference is absent, so a foreign reference reads as "no such conversation for me".
     * - Reports the number of conversations actually removed in deletedCount.
     * - Deletes the persisted chat history before the stored conversations.
     * - Reports an unsuccessful response with the chat history errors and keeps the stored conversations when the chat history delete fails.
     *
     * @api
     */
    public function deleteStorefrontAssistantConversationCollection(
        StorefrontAssistantConversationCollectionDeleteCriteriaTransfer $storefrontAssistantConversationCollectionDeleteCriteriaTransfer
    ): StorefrontAssistantConversationCollectionResponseTransfer;

    /**
     * Specification:
     * - Reads the messages of a single conversation from the persisted chat history.
     * - Verifies the owner holds the conversation in storage before reading; the persisted chat history carries no
     *   owner scope, so this check is what prevents reading another customer's messages by guessing a reference.
     * - Returns an unsuccessful response when the owner reference or conversation reference is blank, or when the
     *   owner does not hold the conversation.
     * - Returns a successful response with an empty message list when nothing has been persisted for the conversation yet.
     *
     * @api
     */
    public function getStorefrontAssistantConversationMessageCollection(
        StorefrontAssistantConversationMessageCollectionCriteriaTransfer $storefrontAssistantConversationMessageCollectionCriteriaTransfer
    ): StorefrontAssistantConversationMessageCollectionTransfer;

    /**
     * Specification:
     * - Returns the currently enabled Storefront Assistant agents, each as an array with `name`, `label` and `description`.
     * - `label` is translated from `ai_commerce.storefront_assistant.agent.<name>.label`, falling back to the humanized agent name.
     * - `description` is translated from `ai_commerce.storefront_assistant.agent.<name>.description`, falling back to the plugin description.
     * - Resolves the per-agent enable toggle fresh so a Back Office change takes effect without a deployment.
     * - Returns an empty array when no agent is enabled, so the storefront hides the agent selector.
     *
     * @api
     *
     * @return array<int, array<string, string>>
     */
    public function getStorefrontAssistantEnabledAgents(): array;
}
