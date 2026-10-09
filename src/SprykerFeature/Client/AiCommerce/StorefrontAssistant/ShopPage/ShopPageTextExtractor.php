<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage;

class ShopPageTextExtractor implements ShopPageTextExtractorInterface
{
    public const int MAX_TEXT_LENGTH = 3000;

    protected const string PATTERN_TWIG = '/\{\{.*?\}\}|\{%.*?%\}|\{#.*?#\}/s';

    protected const string PATTERN_NON_TEXT_ELEMENTS = '/<(script|style)\b[^>]*>.*?<\/\1>/is';

    protected const string PATTERN_LINE_BREAKING_TAGS = '/<br\s*\/?>|<\/(p|div|li|h[1-6]|tr|ul|ol|table)>/i';

    protected const string PATTERN_HORIZONTAL_WHITESPACE = '/[^\S\n]+/u';

    protected const string PATTERN_LINE_BREAKS = '/\s*\n\s*/u';

    protected const string LINE_BREAK = "\n";

    protected const string SPACE = ' ';

    protected const string ELLIPSIS = '…';

    /**
     * @param array<int|string, mixed> $placeholders
     */
    public function extractText(array $placeholders): string
    {
        $texts = [];

        foreach ($placeholders as $placeholder) {
            if (!is_string($placeholder)) {
                continue;
            }

            $text = $this->convertToPlainText($placeholder);

            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return $this->capText(implode(static::LINE_BREAK, $texts));
    }

    protected function convertToPlainText(string $placeholder): string
    {
        $text = (string)preg_replace(static::PATTERN_TWIG, static::SPACE, $placeholder);
        $text = (string)preg_replace(static::PATTERN_NON_TEXT_ELEMENTS, static::SPACE, $text);
        $text = (string)preg_replace(static::PATTERN_LINE_BREAKING_TAGS, static::LINE_BREAK, $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string)preg_replace(static::PATTERN_HORIZONTAL_WHITESPACE, static::SPACE, $text);
        $text = (string)preg_replace(static::PATTERN_LINE_BREAKS, static::LINE_BREAK, $text);

        return trim($text);
    }

    protected function capText(string $text): string
    {
        if (mb_strlen($text) <= static::MAX_TEXT_LENGTH) {
            return $text;
        }

        $cappedText = mb_substr($text, 0, static::MAX_TEXT_LENGTH - mb_strlen(static::ELLIPSIS));
        $lastSpacePosition = mb_strrpos($cappedText, static::SPACE);

        if ($lastSpacePosition !== false) {
            $cappedText = mb_substr($cappedText, 0, $lastSpacePosition);
        }

        return rtrim($cappedText) . static::ELLIPSIS;
    }
}
