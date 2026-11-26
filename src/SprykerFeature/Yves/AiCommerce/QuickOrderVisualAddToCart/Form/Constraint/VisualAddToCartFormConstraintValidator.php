<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\Constraint;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class VisualAddToCartFormConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof VisualAddToCartFormConstraint) {
            throw new InvalidArgumentException(sprintf(
                'Expected constraint instance of %s, got %s instead.',
                VisualAddToCartFormConstraint::class,
                $constraint::class,
            ));
        }

        if ($value === null) {
            $this->context
                ->buildViolation($constraint->getNoFileMessage())
                ->addViolation();

            return;
        }

        if ($value instanceof UploadedFile && !$value->isValid()) {
            return;
        }

        if (!$constraint->getUploadedFileValidator()->isValidFileSize($value)) {
            $this->context
                ->buildViolation($constraint->getFileTooLargeMessage())
                ->addViolation();

            return;
        }

        if (!$constraint->getUploadedFileValidator()->isValidMimeType($value)) {
            $this->context
                ->buildViolation($constraint->getInvalidMimeTypeMessage())
                ->addViolation();

            return;
        }

        if (!$constraint->getUploadedFileValidator()->isValidFormat($value)) {
            $this->context
                ->buildViolation($constraint->getInvalidFormatMessage())
                ->addViolation();
        }
    }
}
