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
     * AI configuration name used by the intent router to classify incoming prompts and route them to the appropriate agent.
     *
     * @api
     */
    public const string AI_CONFIGURATION_INTENT_ROUTER = 'AI_CONFIGURATION_INTENT_ROUTER';

    /**
     * AI configuration name used by the general-purpose agent for handling broad Backoffice assistant queries.
     *
     * @api
     */
    public const string AI_CONFIGURATION_GENERAL_PURPOSE = 'AI_CONFIGURATION_GENERAL_PURPOSE';

    /**
     * AI configuration name used by the order management agent for handling order-related queries and actions.
     *
     * @api
     */
    public const string AI_CONFIGURATION_ORDER_MANAGEMENT = 'AI_CONFIGURATION_ORDER_MANAGEMENT';

    /**
     * Tool set name that groups all order management tools available to the order management agent.
     *
     * @api
     */
    public const string TOOL_SET_ORDER_MANAGEMENT = 'order_management_tools';

    /**
     * Tool set name that groups all general-purpose tools available to the general-purpose agent.
     *
     * @api
     */
    public const string TOOL_SET_GENERAL_PURPOSE = 'general_purpose_tools';
}
