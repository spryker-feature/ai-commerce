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

    protected const string CONFIGURATION_KEY_PLACE_ORDER_AGENT_IS_ENABLED = 'ai_commerce:backoffice_assistant:general:is_place_order_agent_enabled';

    protected const bool BACKOFFICE_ASSISTANT_DEFAULT_IS_ENABLED = false;

    protected const int BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_FILE_SIZE_BYTES = 5242880;

    protected const int BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_TOTAL_SIZE_BYTES = 10485760;

    protected const int BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_COUNT = 5;

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
     * @api
     */
    public function getBackofficeAssistantAttachmentMaxFileSizeBytes(): int
    {
        return static::BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_FILE_SIZE_BYTES;
    }

    /**
     * @api
     */
    public function getBackofficeAssistantAttachmentMaxTotalSizeBytes(): int
    {
        return static::BACKOFFICE_ASSISTANT_ATTACHMENT_MAX_TOTAL_SIZE_BYTES;
    }

    /**
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
     * - Returns true if the Place Order Agent is enabled.
     *
     * @api
     */
    public function isPlaceOrderAgentEnabled(): bool
    {
        return (bool)filter_var($this->getModuleConfig(static::CONFIGURATION_KEY_PLACE_ORDER_AGENT_IS_ENABLED, true), FILTER_VALIDATE_BOOLEAN);
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
            AiCommerceConstants::AI_CONFIGURATION_GENERAL_PURPOSE,
            AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT,
            AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT,
            AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER,
        ];
    }
}
