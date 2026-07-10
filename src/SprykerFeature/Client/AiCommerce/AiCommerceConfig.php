<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Spryker\Client\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

class AiCommerceConfig extends AbstractBundleConfig
{
    protected const string SEARCH_BY_IMAGE_PROMPT_TEMPLATE = 'Identify the main product in this image and respond with only the most relevant product search term. One to three words maximum.';

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
}
