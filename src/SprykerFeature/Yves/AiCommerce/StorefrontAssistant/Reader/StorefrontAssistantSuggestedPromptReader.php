<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Reader;

use Spryker\Client\Locale\LocaleClientInterface;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class StorefrontAssistantSuggestedPromptReader implements StorefrontAssistantSuggestedPromptReaderInterface
{
    protected const int MAX_PROMPTS = 4;

    protected const int MAX_PROMPT_LENGTH = 120;

    public function __construct(
        protected AiCommerceConfig $config,
        protected LocaleClientInterface $localeClient,
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function getSuggestedPrompts(): array
    {
        $suggestedPrompts = [];
        $configuredSuggestedPrompts = $this->config->getStorefrontAssistantSuggestedPrompts($this->localeClient->getCurrentLocale());

        foreach ($configuredSuggestedPrompts as $group => $configuredPrompts) {
            $suggestedPrompts[$group] = $this->normalizePrompts($configuredPrompts);
        }

        return $suggestedPrompts;
    }

    /**
     * @param list<string> $configuredPrompts
     *
     * @return list<string>
     */
    protected function normalizePrompts(array $configuredPrompts): array
    {
        $prompts = [];

        foreach ($configuredPrompts as $prompt) {
            $prompt = trim($prompt);

            if ($prompt === '') {
                continue;
            }

            $prompts[] = mb_substr($prompt, 0, static::MAX_PROMPT_LENGTH);

            if (count($prompts) === static::MAX_PROMPTS) {
                break;
            }
        }

        return $prompts;
    }
}
