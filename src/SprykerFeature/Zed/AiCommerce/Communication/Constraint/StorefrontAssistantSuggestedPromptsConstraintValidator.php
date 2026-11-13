<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Constraint;

use Spryker\Zed\Kernel\Communication\Validator\AbstractConstraintValidator;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class StorefrontAssistantSuggestedPromptsConstraintValidator extends AbstractConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StorefrontAssistantSuggestedPromptsConstraint) {
            throw new UnexpectedTypeException($constraint, StorefrontAssistantSuggestedPromptsConstraint::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $decodedValue = is_string($value) ? json_decode($value) : null;

        if (!$decodedValue instanceof stdClass) {
            $this->context->buildViolation($constraint->invalidFormatMessage)->addViolation();

            return;
        }

        $availableLocaleNames = $this->getFactory()->getLocaleFacade()->getAvailableLocales();

        foreach (get_object_vars($decodedValue) as $localeName => $suggestedPrompts) {
            if (!in_array($localeName, $availableLocaleNames, true)) {
                $this->addLocaleViolation($constraint->unknownLocaleMessage, (string)$localeName);

                continue;
            }

            if (!$this->isTextList($suggestedPrompts)) {
                $this->addLocaleViolation($constraint->invalidPromptsMessage, (string)$localeName);
            }
        }
    }

    protected function isTextList(mixed $suggestedPrompts): bool
    {
        if (!is_array($suggestedPrompts) || !array_is_list($suggestedPrompts)) {
            return false;
        }

        return array_filter($suggestedPrompts, 'is_string') === $suggestedPrompts;
    }

    protected function addLocaleViolation(string $message, string $localeName): void
    {
        $this->context->buildViolation($message)
            ->setParameter(StorefrontAssistantSuggestedPromptsConstraint::PARAMETER_LOCALE, $localeName)
            ->addViolation();
    }
}
