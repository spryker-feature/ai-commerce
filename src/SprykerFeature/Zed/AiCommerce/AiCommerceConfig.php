<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce;

use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

class AiCommerceConfig extends AbstractBundleConfig
{
    protected const string CONFIGURATION_KEY_AI_COMMERCE_BACKOFFICE_ASSISTANT_GENERAL_IS_ENABLED = 'ai_commerce:backoffice_assistant:general:is_enabled';

    protected const string CONFIGURATION_KEY_ORDER_MANAGEMENT_AGENT_IS_ENABLED = 'ai_commerce:backoffice_assistant:general:is_order_management_agent_enabled';

    protected const string CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_AGENT_IS_ENABLED = 'ai_commerce:backoffice_assistant:general:is_discount_management_agent_enabled';

    protected const string CONFIGURATION_KEY_FORM_FILL_AGENT_IS_ENABLED = 'ai_commerce:backoffice_assistant:general:is_form_fill_agent_enabled';

    protected const bool BACKOFFICE_ASSISTANT_DEFAULT_IS_ENABLED = false;

    protected const int BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_FILE_SIZE_BYTES = 5242880;

    protected const int BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_TOTAL_SIZE_BYTES = 10485760;

    protected const int BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_COUNT = 5;

    protected const int CONVERSATION_LIST_LIMIT = 50;

    /**
     * @var array<string>
     */
    protected const array BACKOFFICE_ASSISTANT_ATTACHMENT_ALLOWED_MEDIA_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'text/csv',
    ];

    protected const string AI_TRANSLATION_PROMPT_TEMPLATE = 'Support me in translating the following text `%s` from %s to %s locale(s) for an online shop, ensuring native speaker fluency.
        Generate accurate and contextually fitting translations to enhance the user experience.
        The texts to be translated may contain URLs, URL paths, HTML, unicode characters or some word enclosed by the character "%%", please don\'t translate them.';

    protected const string AI_TRANSLATION_COLLECTION_PROMPT_TEMPLATE = 'Support me in translating the following text `%s` from %s to each of the following locales: %s, for an online shop, ensuring native speaker fluency.
        Generate accurate and contextually fitting translations for each locale to enhance the user experience. Return one translation item per requested locale.
        The texts to be translated may contain URLs, URL paths, HTML, unicode characters or some word enclosed by the character "%%", please don\'t translate them.';

    protected const string PRODUCT_CATEGORY_SUGGESTION_PROMPT_TEMPLATE = 'Based on the provided product name and description, suggest the most fitting product categories from the existing categories list for optimal placement in an e-commerce store.
        Product name: %s
        Product description: %s
        Existing categories in format {"categoryName": "categoryId", ...}:
        %s';

    protected const string CONTENT_IMPROVER_PROMPT_TEMPLATE = 'Improve the following text for an e-commerce product by enhancing clarity, grammar, structure, and readability while maintaining the original meaning and tone.
        Make it more professional and engaging for potential customers.
        Text to improve: %s';

    protected const int PROMPT_MAX_RETRIES = 2;

    protected const string ERROR_CODE_AI_PROVIDER_REQUEST_ERROR = 'AI_PROVIDER_REQUEST_ERROR';

    protected const string ERROR_MESSAGE_AI_PROVIDER_REQUEST_ERROR_TEMPLATE = 'AI %s is not available because an error occurred while trying to reach out to the AI provider.';

    /**
     * Specification:
     * - Returns true if the Backoffice Assistant feature is enabled.
     *
     * @api
     */
    public function isBackofficeAssistantEnabled(): bool
    {
        return (bool)filter_var($this->getModuleConfig(static::CONFIGURATION_KEY_AI_COMMERCE_BACKOFFICE_ASSISTANT_GENERAL_IS_ENABLED, static::BACKOFFICE_ASSISTANT_DEFAULT_IS_ENABLED), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Specification:
     * - Returns the maximum allowed file size in bytes for a single Backoffice Assistant attachment.
     *
     * @api
     */
    public function getBackofficeAssistantAttachmentMaxFileSizeBytes(): int
    {
        return static::BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_FILE_SIZE_BYTES;
    }

    /**
     * Specification:
     * - Returns the maximum allowed total size in bytes for all Backoffice Assistant attachments combined.
     *
     * @api
     */
    public function getBackofficeAssistantAttachmentMaxTotalSizeBytes(): int
    {
        return static::BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_TOTAL_SIZE_BYTES;
    }

    /**
     * Specification:
     * - Returns the maximum number of attachments allowed per Backoffice Assistant message.
     *
     * @api
     */
    public function getBackofficeAssistantAttachmentMaxCount(): int
    {
        return static::BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_COUNT;
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getBackofficeAssistantAttachmentAllowedMediaTypes(): array
    {
        return static::BACKOFFICE_ASSISTANT_ATTACHMENT_ALLOWED_MEDIA_TYPES;
    }

    /**
     * Specification:
     * - Returns the absolute path to the Backoffice navigation cache file.
     *
     * @api
     */
    public function getBackofficeNavigationCachePath(): string
    {
        return APPLICATION_ROOT_DIR . '/src/Generated/Zed/Navigation/codeBucket/navigation.cache';
    }

    /**
     * Specification:
     * - Returns true if the Order Management Agent is enabled.
     *
     * @api
     */
    public function isOrderManagementAgentEnabled(): bool
    {
        return (bool)filter_var($this->getModuleConfig(static::CONFIGURATION_KEY_ORDER_MANAGEMENT_AGENT_IS_ENABLED, true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Specification:
     * - Returns true if the Discount Management Agent is enabled.
     *
     * @api
     */
    public function isDiscountManagementAgentEnabled(): bool
    {
        return (bool)filter_var($this->getModuleConfig(static::CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_AGENT_IS_ENABLED, true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Specification:
     * - Returns the conversation list limit for the Backoffice Assistant.
     *
     * @api
     */
    public function getConversationListLimit(): int
    {
        return static::CONVERSATION_LIST_LIMIT;
    }

    /**
     * Specification:
     * - Returns the list of available discount types for AI tool parameters.
     *
     * @link \Spryker\Shared\Discount\DiscountConstants::TYPE_VOUCHER
     * @link \Spryker\Shared\Discount\DiscountConstants::TYPE_CART_RULE
     *
     * @api
     *
     * @return array<string>
     */
    public function getDiscountTypes(): array
    {
        return [
            'voucher',
            'cart_rule',
        ];
    }

    /**
     * Specification:
     * - Returns the list of available calculator plugin names for AI tool parameters.
     *
     * @link \Spryker\Zed\Discount\DiscountDependencyProvider::PLUGIN_CALCULATOR_PERCENTAGE
     * @link \Spryker\Zed\Discount\DiscountDependencyProvider::PLUGIN_CALCULATOR_FIXED
     *
     * @api
     *
     * @return array<string>
     */
    public function getCalculatorPluginNames(): array
    {
        return [
            'PLUGIN_CALCULATOR_PERCENTAGE',
            'PLUGIN_CALCULATOR_FIXED',
        ];
    }

    /**
     * @var array<string>
     */
    protected const array FORM_FILL_EXCLUDED_FORM_NAMES = [
        'user',
        'customer',
        'api-key',
        'merchant',
        'address',
        'shipment_group_form',
    ];

    /**
     * Specification:
     * - Returns true if the Form Fill Agent is enabled.
     *
     * @api
     */
    public function isFormFillAgentEnabled(): bool
    {
        return (bool)filter_var($this->getModuleConfig(static::CONFIGURATION_KEY_FORM_FILL_AGENT_IS_ENABLED, false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Specification:
     * - Returns form names whose field values must not be captured into the assistant context.
     * - Protects personal and sensitive data from being sent to the LLM.
     *
     * @api
     *
     * @return array<string>
     */
    public function getFormFillExcludedFormNames(): array
    {
        return static::FORM_FILL_EXCLUDED_FORM_NAMES;
    }

    /**
     * Specification:
     * - Returns the names of AI configurations that should be used for the Backoffice Assistant SSE event.
     *
     * @api
     *
     * @return array<string>
     */
    public function getBackofficeAssistantSseAiConfigurationNames(): array
    {
        return [
            AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT,
            AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT,
            AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT,
            AiCommerceConstants::AI_CONFIGURATION_FORM_FILL,
        ];
    }

    /**
     * Specification:
     * - Returns the prompt text used to generate an HTML alt text for a product image for the given locale.
     *
     * @api
     */
    public function getImageAltTextPrompt(string $locale): string
    {
        return sprintf(
            'Describe the most important characteristics of the main object you can identify in the image e.g. manufacturer, model, color, part number or any identification number that help me to define the HTML alt text for best SEO using the language from locale %s.',
            $locale,
        );
    }

    /**
     * Specification:
     * - Returns the prompt template used to request AI-powered product content translation.
     *
     * @api
     */
    public function getAiTranslationPromptTemplate(): string
    {
        return static::AI_TRANSLATION_PROMPT_TEMPLATE;
    }

    /**
     * Specification:
     * - Returns the prompt template used to request AI-powered product content translation to a collection of locales in a single request.
     *
     * @api
     */
    public function getAiTranslationCollectionPromptTemplate(): string
    {
        return static::AI_TRANSLATION_COLLECTION_PROMPT_TEMPLATE;
    }

    /**
     * Specification:
     * - Returns the prompt template used to request AI-powered product category suggestions.
     *
     * @api
     */
    public function getProductCategorySuggestionPromptTemplate(): string
    {
        return static::PRODUCT_CATEGORY_SUGGESTION_PROMPT_TEMPLATE;
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for the category suggestion feature, or null to use the default.
     *
     * @api
     */
    public function getCategorySuggestionAiConfigurationName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for the translation feature, or null to use the default.
     *
     * @api
     */
    public function getTranslationAiConfigurationName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for the image alt text feature, or null to use the default.
     *
     * @api
     */
    public function getImageAltTextAiConfigurationName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the prompt template used to request AI-powered product content improvement.
     *
     * @api
     */
    public function getContentImproverPromptTemplate(): string
    {
        return static::CONTENT_IMPROVER_PROMPT_TEMPLATE;
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for the content improver feature, or null to use the default.
     *
     * @api
     */
    public function getContentImproverAiConfigurationName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the maximum number of retries for AI prompt requests.
     *
     * @api
     */
    public function getPromptMaxRetries(): int
    {
        return static::PROMPT_MAX_RETRIES;
    }

    /**
     * Specification:
     * - Returns the error code used when an AI provider request fails.
     *
     * @api
     */
    public function getErrorCodeAiProviderRequestError(): string
    {
        return static::ERROR_CODE_AI_PROVIDER_REQUEST_ERROR;
    }

    /**
     * Specification:
     * - Returns the error message template used when an AI provider request fails, with a placeholder for the operation name.
     *
     * @api
     */
    public function getErrorMessageAiProviderRequestErrorTemplate(): string
    {
        return static::ERROR_MESSAGE_AI_PROVIDER_REQUEST_ERROR_TEMPLATE;
    }

    /**
     * Specification:
     * - Returns true if Smart Product Management AI features are visible in the product form.
     *
     * @api
     */
    public function isSmartProductManagementEnabled(): bool
    {
        return true;
    }
}
