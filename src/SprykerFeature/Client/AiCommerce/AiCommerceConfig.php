<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Spryker\Client\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

/**
 * @method \SprykerFeature\Shared\AiCommerce\AiCommerceConfig getSharedConfig()
 */
class AiCommerceConfig extends AbstractBundleConfig
{
    protected const string SEARCH_BY_IMAGE_PROMPT_TEMPLATE = 'Identify the main product in this image and respond with only the most relevant product search term. One to three words maximum.';

    protected const int STOREFRONT_ASSISTANT_CONVERSATION_TIME_TO_LIVE = 2592000;

    protected const int STOREFRONT_ASSISTANT_CONVERSATION_LIST_LIMIT = 50;

    protected const int STOREFRONT_ASSISTANT_CONVERSATION_INDEX_LIMIT = 100;

    protected const int STOREFRONT_ASSISTANT_CATEGORY_TREE_LIMIT = 200;

    protected const int STOREFRONT_ASSISTANT_INLINE_CATEGORY_TREE_LIMIT = 200;

    protected const int STOREFRONT_ASSISTANT_MAX_REFINEMENT_CHIPS = 6;

    protected const float STOREFRONT_ASSISTANT_PRICE_OUTLIER_FACTOR = 5.0;

    protected const bool STOREFRONT_ASSISTANT_IS_REASONING_STREAMED = true;

    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_GENERAL_IS_PRODUCT_DISCOVERY_AGENT_ENABLED = 'ai_commerce:storefront_assistant:general:is_product_discovery_agent_enabled';

    protected const bool PRODUCT_DISCOVERY_AGENT_DEFAULT_IS_ENABLED = true;

    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_PRODUCT_DISCOVERY_SYSTEM_PROMPT = 'ai_commerce:storefront_assistant:system_prompts:product_discovery_system_prompt';

    protected const bool STOREFRONT_ASSISTANT_AGENT_DEFAULT_IS_ENABLED = false;

    protected const string PRODUCT_DISCOVERY_SYSTEM_PROMPT_DEFAULT = 'You are a shopping assistant for an online shop. Use the catalog_search tool to find real products before naming any product, price or link. Never invent a product, price or URL. If the catalog returns no matching products, say plainly that no matching products were found. Base every product fact only on the tool results of the current turn.';

    /**
     * Specification:
     * - Returns the prompt used to turn an uploaded image into a product search term.
     * - Resolves the value from Configuration Management; falls back to the module default when unset or blank.
     *
     * @api
     */
    public function getSearchByImagePromptTemplate(): string
    {
        $systemPrompt = (string)$this->getModuleConfig(AiCommerceConstants::CONFIGURATION_KEY_SEARCH_BY_IMAGE_PROMPT, static::SEARCH_BY_IMAGE_PROMPT_TEMPLATE);
        if (trim($systemPrompt) === '') {
            return static::SEARCH_BY_IMAGE_PROMPT_TEMPLATE;
        }

        return $systemPrompt;
    }

    /**
     * Specification:
     * - Returns AI configuration name for search by image defined in \Spryker\Shared\AiFoundation\AiFoundationConstants::AI_CONFIGURATIONS.
     * - Returns null to use default AI configuration.
     * - If null is returned, the default AI configuration will be used \Spryker\Shared\AiFoundation\AiFoundationConstants::AI_CONFIGURATION_DEFAULT
     *
     * @api
     */
    public function getSearchByImageAiConfigurationName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns true when the Storefront Assistant feature is enabled.
     * - Reads the value from Configuration Management on every call so a Back Office change takes effect without a deployment.
     * - Defaults to false when the configuration key is not set.
     *
     * @api
     */
    public function isStorefrontAssistantEnabled(): bool
    {
        return (bool)filter_var(
            $this->getModuleConfig(
                $this->getSharedConfig()->getStorefrontAssistantEnabledKey(),
                $this->getSharedConfig()->isStorefrontAssistantEnabledByDefault(),
            ),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Specification:
     * - Returns the AI configuration name the Storefront Assistant resolves through the AI Foundation gateway.
     * - Returns an empty string by default: the project resolves the AI configuration name.
     * - The name must be present in \Spryker\Zed\AiFoundation\AiFoundationConfig::getClientResolvableAiConfigurationNames(),
     *   otherwise the chat session is rejected.
     *
     * @api
     */
    public function getStorefrontAssistantAiConfigurationName(): string
    {
        return '';
    }

    /**
     * Specification:
     * - Returns the names of AI configurations the Storefront Assistant emits tool call SSE events for.
     *
     * @api
     *
     * @return array<string>
     */
    public function getStorefrontAssistantSseAiConfigurationNames(): array
    {
        return [
            $this->getStorefrontAssistantAiConfigurationName(),
        ];
    }

    /**
     * Specification:
     * - Defines whether the reasoning chunks of the model, such as reasoning summaries or thinking blocks, are streamed to the customer.
     * - When disabled, the customer sees only the answer text and the tool status labels.
     *
     * @api
     */
    public function isStorefrontAssistantReasoningStreamed(): bool
    {
        return static::STOREFRONT_ASSISTANT_IS_REASONING_STREAMED;
    }

    /**
     * Specification:
     * - Returns the names of the tools whose results are shown to the customer.
     * - Returns an empty list by default: the project lists the customer-facing tools.
     * - A tool absent from this list is internal to the agent: its result is never emitted as an SSE
     *   event and never rendered in the conversation history, though it is still persisted so the
     *   agent keeps its working context across turns.
     *
     * @api
     *
     * @return array<string>
     */
    public function getStorefrontAssistantCustomerFacingToolNames(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the retention time to live in seconds applied to every customer visible conversation write.
     *
     * @api
     */
    public function getStorefrontAssistantConversationTimeToLive(): int
    {
        return static::STOREFRONT_ASSISTANT_CONVERSATION_TIME_TO_LIVE;
    }

    /**
     * Specification:
     * - Returns the maximum number of conversations the history list returns per read.
     * - Applied when the criteria carries no explicit limit.
     *
     * @api
     */
    public function getStorefrontAssistantConversationListLimit(): int
    {
        return static::STOREFRONT_ASSISTANT_CONVERSATION_LIST_LIMIT;
    }

    /**
     * Specification:
     * - Returns the maximum number of conversation references kept in the per-customer conversation index.
     * - The oldest references beyond the limit are dropped when a conversation is saved.
     * - Should not be lower than the conversation list limit, otherwise the history list returns fewer conversations than requested.
     *
     * @api
     */
    public function getStorefrontAssistantConversationIndexLimit(): int
    {
        return static::STOREFRONT_ASSISTANT_CONVERSATION_INDEX_LIMIT;
    }

    /**
     * Specification:
     * - Returns the maximum number of categories the storefront assistant category tree tool returns.
     * - Applied to the flattened category tree, so a larger catalog is cut instead of sent whole.
     *
     * @api
     */
    public function getStorefrontAssistantCategoryTreeLimit(): int
    {
        return static::STOREFRONT_ASSISTANT_CATEGORY_TREE_LIMIT;
    }

    /**
     * Specification:
     * - Returns the maximum number of category lines the Product Discovery Agent receives up front in its system prompt.
     * - A category tree with more lines is not inlined; the agent then looks categories up with the category tree tool.
     *
     * @api
     */
    public function getStorefrontAssistantInlineCategoryTreeLimit(): int
    {
        return static::STOREFRONT_ASSISTANT_INLINE_CATEGORY_TREE_LIMIT;
    }

    /**
     * Specification:
     * - Returns the maximum number of refinement chips shown under the product cards of a Storefront Assistant reply.
     *
     * @api
     */
    public function getStorefrontAssistantMaxRefinementChips(): int
    {
        return static::STOREFRONT_ASSISTANT_MAX_REFINEMENT_CHIPS;
    }

    /**
     * Specification:
     * - Returns how many times the median price of the other same-name products a product price must exceed to be flagged as a price outlier.
     *
     * @api
     */
    public function getStorefrontAssistantPriceOutlierFactor(): float
    {
        return static::STOREFRONT_ASSISTANT_PRICE_OUTLIER_FACTOR;
    }

    /**
     * Specification:
     * - Returns the names of the tools the Product Discovery Agent may call.
     * - Returns an empty list by default: the project lists the tools it enables.
     * - Every name must be registered as a tool plugin in AI Foundation.
     * - The category tree tool is left out of a turn whose system prompt already carries the inline category tree.
     *
     * @api
     *
     * @return list<string>
     */
    public function getProductDiscoveryAgentToolNames(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the Configuration Management key of the Product Discovery Agent system prompt.
     *
     * @api
     */
    public function getProductDiscoverySystemPromptKey(): string
    {
        return static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_PRODUCT_DISCOVERY_SYSTEM_PROMPT;
    }

    /**
     * Specification:
     * - Returns the Configuration Management key of the Product Discovery Agent enable toggle.
     *
     * @api
     */
    public function getProductDiscoveryAgentEnabledKey(): string
    {
        return static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_GENERAL_IS_PRODUCT_DISCOVERY_AGENT_ENABLED;
    }

    /**
     * Specification:
     * - Resolves the boolean value stored under the given agent-enabled Configuration Management key.
     * - Falls back to the default declared for that key when the value is unset.
     * - An unknown, misspelled or not yet synced key has no declared default and resolves to disabled.
     *
     * @api
     */
    public function isStorefrontAssistantAgentEnabled(string $configurationKey): bool
    {
        return (bool)filter_var(
            $this->getModuleConfig($configurationKey, $this->getStorefrontAssistantAgentDefaultIsEnabled($configurationKey)),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    protected function getStorefrontAssistantAgentDefaultIsEnabled(string $configurationKey): bool
    {
        if ($configurationKey === static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_GENERAL_IS_PRODUCT_DISCOVERY_AGENT_ENABLED) {
            return static::PRODUCT_DISCOVERY_AGENT_DEFAULT_IS_ENABLED;
        }

        return static::STOREFRONT_ASSISTANT_AGENT_DEFAULT_IS_ENABLED;
    }

    /**
     * Specification:
     * - Resolves the system prompt stored under the given Configuration Management key.
     * - Falls back to the Product Discovery Agent default when the value is unset or blank.
     *
     * @api
     */
    public function getStorefrontAssistantSystemPrompt(string $configurationKey): string
    {
        $systemPrompt = (string)$this->getModuleConfig($configurationKey, static::PRODUCT_DISCOVERY_SYSTEM_PROMPT_DEFAULT);

        if (trim($systemPrompt) === '') {
            return static::PRODUCT_DISCOVERY_SYSTEM_PROMPT_DEFAULT;
        }

        return $systemPrompt;
    }

    /**
     * Specification:
     * - Returns the MIME types a customer may attach to a Storefront Assistant message.
     * - Single source of truth shared with the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     *
     * @return list<string>
     */
    public function getStorefrontAssistantSupportedAttachmentMimeTypes(): array
    {
        return $this->getSharedConfig()->getStorefrontAssistantSupportedAttachmentMimeTypes();
    }

    /**
     * Specification:
     * - Returns the maximum decoded byte size a single Storefront Assistant attachment may reach.
     * - Single source of truth shared with the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentSizeBytes(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxAttachmentSizeBytes();
    }

    /**
     * Specification:
     * - Returns the maximum number of attachments a single Storefront Assistant message may carry.
     * - Single source of truth shared with the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentCount(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxAttachmentCount();
    }

    /**
     * Specification:
     * - Returns the binary signatures a decoded attachment is verified against, keyed by the media type the client claims.
     * - Guards against a client that declares a supported media type for bytes of another kind.
     *
     * @api
     *
     * @return array<string, list<string>>
     */
    public function getStorefrontAssistantAttachmentMimeTypeSignatures(): array
    {
        return $this->getSharedConfig()->getStorefrontAssistantAttachmentMimeTypeSignatures();
    }

    /**
     * Specification:
     * - Returns the maximum number of characters a stored attachment file name may reach.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentFilenameLength(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxAttachmentFilenameLength();
    }

    /**
     * Specification:
     * - Returns the maximum number of characters a client-provided Storefront Assistant conversation reference may reach.
     *
     * @api
     */
    public function getStorefrontAssistantMaxConversationReferenceLength(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxConversationReferenceLength();
    }

    /**
     * Specification:
     * - Returns the regular expression a client-provided Storefront Assistant conversation reference must match.
     *
     * @api
     */
    public function getStorefrontAssistantConversationReferencePattern(): string
    {
        return $this->getSharedConfig()->getStorefrontAssistantConversationReferencePattern();
    }

    /**
     * Specification:
     * - Returns the maximum number of characters a single Storefront Assistant message may reach.
     * - Derived from the conversation history context window so the limit follows a project that resizes it.
     * - Never returns less than the floor that keeps ordinary customer questions acceptable.
     * - Single source of truth shared with the Yves widget so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxMessageLengthCharacters(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxMessageLengthCharacters();
    }
}
