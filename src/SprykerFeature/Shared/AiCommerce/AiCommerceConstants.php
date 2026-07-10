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

    /**
     * Configuration key for the Smart CMS system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_CMS_SYSTEM_PROMPT = 'ai_commerce:smart_cms:system_prompts:system_prompt';

    /**
     * Configuration key for the Smart PIM content improver prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_CONTENT_IMPROVER_PROMPT = 'ai_commerce:smart_pim:system_prompts:content_improver_prompt';

    /**
     * Configuration key for the Smart PIM translation prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_TRANSLATION_PROMPT = 'ai_commerce:smart_pim:system_prompts:translation_prompt';

    /**
     * Configuration key for the Smart PIM translation collection prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_TRANSLATION_COLLECTION_PROMPT = 'ai_commerce:smart_pim:system_prompts:translation_collection_prompt';

    /**
     * Configuration key for the Smart PIM category suggestion prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_CATEGORY_SUGGESTION_PROMPT = 'ai_commerce:smart_pim:system_prompts:category_suggestion_prompt';

    /**
     * Configuration key for the Smart PIM image alt text prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_IMAGE_ALT_TEXT_PROMPT = 'ai_commerce:smart_pim:system_prompts:image_alt_text_prompt';

    /**
     * Configuration key for the Search by Image search term prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_PROMPT = 'ai_commerce:search_by_image:system_prompts:search_term_prompt';

    /**
     * Configuration key for the Quick Order image recognition prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_QUICK_ORDER_IMAGE_RECOGNITION_PROMPT = 'ai_commerce:quick_order:system_prompts:image_recognition_prompt';
}
