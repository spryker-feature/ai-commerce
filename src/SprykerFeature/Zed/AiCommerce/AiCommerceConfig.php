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
}
