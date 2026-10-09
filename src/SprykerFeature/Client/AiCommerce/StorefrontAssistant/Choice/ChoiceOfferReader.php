<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class ChoiceOfferReader implements ChoiceOfferReaderInterface
{
    public const string PARAMETER_QUESTION = 'question';

    public const string PARAMETER_CHOICES = 'choices';

    public const int MIN_CHOICES = 2;

    public const int MAX_CHOICES = 6;

    public const int MAX_CHOICE_LENGTH = 40;

    public const int MAX_QUESTION_LENGTH = 160;

    protected const string RESULT_KEY_QUESTION = 'question';

    protected const string RESULT_KEY_CHOICES = 'choices';

    protected const string RESULT_KEY_ERROR = 'error';

    protected const string RESULT_KEY_NEXT_STEP = 'nextStep';

    protected const string NEXT_STEP_WAIT_FOR_ANSWER = 'Shown with its question. Your reply ends here: no more tools, no text. The tapped answer is the next message.';

    protected const string ERROR_TOO_FEW_CHOICES = 'Nothing was shown: pass 2 to 6 distinct, non-empty choices.';

    protected const string ERROR_CHOICE_ALREADY_OFFERED = 'Nothing was shown: this reply already asks a question. End your reply now and wait for the customer\'s answer.';

    protected const string ELLIPSIS = '…';

    public function __construct(
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected ChoiceOfferRegistryInterface $choiceOfferRegistry
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function offerChoices(array $arguments): array
    {
        if ($this->choiceOfferRegistry->hasChoiceOffer()) {
            return [static::RESULT_KEY_ERROR => static::ERROR_CHOICE_ALREADY_OFFERED];
        }

        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $choices = $this->extractChoices($arguments[static::PARAMETER_CHOICES] ?? null);

        if (count($choices) < static::MIN_CHOICES) {
            return [static::RESULT_KEY_ERROR => static::ERROR_TOO_FEW_CHOICES];
        }

        $this->choiceOfferRegistry->registerChoiceOffer();

        return [
            static::RESULT_KEY_QUESTION => $this->truncate(
                $this->sanitize($arguments[static::PARAMETER_QUESTION] ?? null),
                static::MAX_QUESTION_LENGTH,
            ),
            static::RESULT_KEY_CHOICES => $choices,
            static::RESULT_KEY_NEXT_STEP => static::NEXT_STEP_WAIT_FOR_ANSWER,
        ];
    }

    /**
     * @return list<string>
     */
    protected function extractChoices(mixed $choices): array
    {
        if (is_string($choices)) {
            $decodedChoices = json_decode($choices, true);
            $choices = is_array($decodedChoices) ? $decodedChoices : [$choices];
        }

        if (!is_array($choices)) {
            return [];
        }

        $extractedChoices = [];

        foreach ($choices as $choice) {
            $choice = $this->truncate($this->sanitize($choice), static::MAX_CHOICE_LENGTH);
            $normalizedChoice = mb_strtolower($choice);

            if ($choice === '' || isset($extractedChoices[$normalizedChoice])) {
                continue;
            }

            $extractedChoices[$normalizedChoice] = $choice;

            if (count($extractedChoices) === static::MAX_CHOICES) {
                break;
            }
        }

        return array_values($extractedChoices);
    }

    protected function sanitize(mixed $value): string
    {
        if (!is_scalar($value) || is_bool($value)) {
            return '';
        }

        return trim((string)preg_replace('/[\s\[\]]+/u', ' ', strip_tags((string)$value)));
    }

    protected function truncate(string $value, int $maxLength): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $maxLength - mb_strlen(static::ELLIPSIS))) . static::ELLIPSIS;
    }
}
