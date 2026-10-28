<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt;

use DateTimeImmutable;
use SprykerFeature\Shared\AiCommerce\Conversation\RegeneratedPromptMarker;

/**
 * The agents read the date, the page and the form only from the prompt text, so they travel as one-line prefixes
 * before the message; a stored prompt is shown and named without them.
 */
class PromptContextFormatter implements PromptContextFormatterInterface
{
    /**
     * @var list<string>
     */
    protected const array PAGE_CONTEXT_KEYS = ['subject', 'reference', 'url'];

    protected const string PREFIX_DATE = 'Date';

    // The model has no clock: without the date, "this week" or "until the end of the month" become arbitrary dates.
    protected const string DATE_FORMAT = 'l, Y-m-d';

    protected const string PREFIX_PAGE_CONTEXT = 'Context';

    protected const string PREFIX_FORM_CONTEXT = 'Form';

    protected const string PREFIX_TEMPLATE = "[%s: %s]\n\n";

    // The prefixes carry JSON, which has no raw line break, so each one is a single line.
    protected const string PREFIX_PATTERN = '/^(?:\[(?:Date|Context|Form): [^\n]*\]\n\n)+/';

    protected const int JSON_ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

    public function __construct(protected RegeneratedPromptMarker $regeneratedPromptMarker)
    {
    }

    public function formatPrompt(string $message, mixed $pageContext, mixed $formContext, bool $isRegeneration = false): string
    {
        // The regeneration marker goes last, so stripContext() leaves it for the conversation reader.
        return $this->createDatePrefix()
            . $this->createPageContextPrefix($pageContext)
            . $this->createFormContextPrefix($formContext)
            . ($isRegeneration ? $this->regeneratedPromptMarker->addMarker($message) : $message);
    }

    public function stripContext(string $prompt): string
    {
        return (string)preg_replace(static::PREFIX_PATTERN, '', $prompt);
    }

    protected function createDatePrefix(): string
    {
        return sprintf(static::PREFIX_TEMPLATE, static::PREFIX_DATE, (new DateTimeImmutable())->format(static::DATE_FORMAT));
    }

    protected function createPageContextPrefix(mixed $pageContext): string
    {
        if (!is_array($pageContext)) {
            return '';
        }

        $fields = array_filter(
            array_intersect_key($pageContext, array_flip(static::PAGE_CONTEXT_KEYS)),
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        );

        if ($fields === []) {
            return '';
        }

        // The values come from the page URL, which anyone can craft; as JSON they stay one quoted line.
        return sprintf(static::PREFIX_TEMPLATE, static::PREFIX_PAGE_CONTEXT, json_encode($fields, static::JSON_ENCODE_FLAGS));
    }

    protected function createFormContextPrefix(mixed $formContext): string
    {
        if (!is_array($formContext) || $formContext === []) {
            return '';
        }

        return sprintf(static::PREFIX_TEMPLATE, static::PREFIX_FORM_CONTEXT, json_encode($formContext, static::JSON_ENCODE_FLAGS));
    }
}
