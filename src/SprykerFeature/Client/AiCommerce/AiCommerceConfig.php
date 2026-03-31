<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Spryker\Client\Kernel\AbstractBundleConfig;

class AiCommerceConfig extends AbstractBundleConfig
{
    protected const string SEARCH_BY_IMAGE_PROMPT_TEMPLATE = 'Identify the main product in this image and respond with only the most relevant product search term. One to three words maximum.';

    /**
     * @api
     */
    public function getSearchByImagePromptTemplate(): string
    {
        return static::SEARCH_BY_IMAGE_PROMPT_TEMPLATE;
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
}
