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
     * @api
     */
    public const string AI_CONFIGURATION_INTENT_ROUTER = 'AI_CONFIGURATION_INTENT_ROUTER';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_GENERAL_PURPOSE = 'AI_CONFIGURATION_GENERAL_PURPOSE';

    /**
     * @api
     */
    public const string AI_MODEL_FAST = 'gpt-4o-mini';

    /**
     * @api
     */
    public const string AI_MODEL_ADVANCED = 'gpt-4.1';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_ORDER_MANAGEMENT = 'AI_CONFIGURATION_ORDER_MANAGEMENT';

    /**
     * @api
     */
    public const string TOOL_SET_ORDER_MANAGEMENT = 'order_management_tools';

    /**
     * @api
     */
    public const string TOOL_SET_GENERAL_PURPOSE = 'general_purpose_tools';
}
