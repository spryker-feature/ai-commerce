<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Reader;

use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class StorefrontAssistantSuggestedPromptReader implements StorefrontAssistantSuggestedPromptReaderInterface
{
    /**
     * @var non-empty-string
     */
    protected const string PROMPT_SEPARATOR = '|';

    /**
     * @var non-empty-string
     */
    protected const string SECTION_SEPARATOR = '||';

    /**
     * @var non-empty-string
     */
    protected const string TITLE_SEPARATOR = ': ';

    // Final: the storefront twig and the shared chat read these keys, so a project cannot rename them. PHPStan still
    // types `static::` by the declared `string`, hence the `@var` shapes where the sections are built and read.
    final protected const string SECTION_KEY_TITLE = 'title';

    final protected const string SECTION_KEY_PROMPTS = 'prompts';

    protected const int MAX_PROMPTS = 4;

    protected const int MAX_SECTIONS = 4;

    protected const int MAX_PROMPT_LENGTH = 120;

    public function __construct(protected AiCommerceConfig $config)
    {
    }

    /**
     * @return array<string, list<string>>
     */
    public function getSuggestedPrompts(): array
    {
        $suggestedPrompts = [];

        foreach ($this->config->getStorefrontAssistantSuggestedPromptValues() as $group => $suggestedPromptValue) {
            $suggestedPrompts[$group] = $this->getSuggestedPromptSet($suggestedPromptValue);
        }

        return $suggestedPrompts;
    }

    /**
     * @return array<string, list<array{title: string, prompts: list<string>}>>
     */
    public function getSuggestedPromptSections(): array
    {
        $suggestedPromptSections = [];

        foreach ($this->config->getStorefrontAssistantSuggestedPromptValues() as $group => $suggestedPromptValue) {
            $suggestedPromptSections[$group] = $this->parseSuggestedPromptSections($suggestedPromptValue);
        }

        return $suggestedPromptSections;
    }

    /**
     * @return list<string>
     */
    protected function getSuggestedPromptSet(string $suggestedPromptValue): array
    {
        $suggestedPrompts = [];

        foreach ($this->parseSuggestedPromptSections($suggestedPromptValue) as $section) {
            /** @var list<string> $sectionPrompts */
            $sectionPrompts = $section[static::SECTION_KEY_PROMPTS];
            $suggestedPrompts = [...$suggestedPrompts, ...$sectionPrompts];
        }

        return array_slice($suggestedPrompts, 0, static::MAX_PROMPTS);
    }

    /**
     * @return list<array{title: string, prompts: list<string>}>
     */
    protected function parseSuggestedPromptSections(string $suggestedPromptValue): array
    {
        $hasSections = str_contains($suggestedPromptValue, static::SECTION_SEPARATOR);
        $sectionValues = $hasSections
            ? explode(static::SECTION_SEPARATOR, $suggestedPromptValue)
            : [$suggestedPromptValue];

        $sections = [];

        foreach ($sectionValues as $sectionValue) {
            [$title, $promptsValue] = $hasSections ? $this->splitSectionTitle($sectionValue) : ['', $sectionValue];
            $prompts = $this->parsePromptList($promptsValue);

            if ($prompts === []) {
                continue;
            }

            /** @var array{title: string, prompts: list<string>} $section */
            $section = [
                static::SECTION_KEY_TITLE => $title,
                static::SECTION_KEY_PROMPTS => $prompts,
            ];
            $sections[] = $section;

            if (count($sections) === static::MAX_SECTIONS) {
                break;
            }
        }

        return $sections;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitSectionTitle(string $sectionValue): array
    {
        // Only a `: ` before the first prompt separator starts a title, so a colon inside a later prompt stays in it.
        $titleSeparatorPosition = mb_strpos($sectionValue, static::TITLE_SEPARATOR);
        $promptSeparatorPosition = mb_strpos($sectionValue, static::PROMPT_SEPARATOR);

        if ($titleSeparatorPosition === false || ($promptSeparatorPosition !== false && $promptSeparatorPosition < $titleSeparatorPosition)) {
            return ['', $sectionValue];
        }

        return [
            mb_substr(trim(mb_substr($sectionValue, 0, $titleSeparatorPosition)), 0, static::MAX_PROMPT_LENGTH),
            mb_substr($sectionValue, $titleSeparatorPosition + mb_strlen(static::TITLE_SEPARATOR)),
        ];
    }

    /**
     * @return list<string>
     */
    protected function parsePromptList(string $promptsValue): array
    {
        $prompts = [];

        foreach (explode(static::PROMPT_SEPARATOR, $promptsValue) as $prompt) {
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
