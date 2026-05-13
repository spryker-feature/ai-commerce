<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface AiCommerceConstants
{
    /**
     * Configuration key for the general-purpose agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_GENERAL_PURPOSE_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:general_purpose_system_prompt';

    /**
     * Configuration key for the order management agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_ORDER_MANAGEMENT_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:order_management_system_prompt';

    /**
     * Configuration key for the discount management agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:discount_management_system_prompt';

    /**
     * Configuration key for the form fill agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_FORM_FILL_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:form_fill_system_prompt';
}
