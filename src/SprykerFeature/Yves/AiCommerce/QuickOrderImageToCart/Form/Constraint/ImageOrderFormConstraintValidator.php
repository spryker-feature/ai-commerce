<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\Constraint;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ImageOrderFormConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ImageOrderFormConstraint) {
            throw new InvalidArgumentException(sprintf(
                'Expected constraint instance of %s, got %s instead.',
                ImageOrderFormConstraint::class,
                $constraint::class,
            ));
        }

        if ($value === null) {
            $this->context
                ->buildViolation($constraint->getNoImageMessage())
                ->addViolation();

            return;
        }

        if (!$constraint->getUploadedImageValidator()->isValidFileSize($value)) {
            $this->context
                ->buildViolation($constraint->getFileTooLargeMessage())
                ->addViolation();

            return;
        }

        if (!$constraint->getUploadedImageValidator()->isValidMimeType($value)) {
            $this->context
                ->buildViolation($constraint->getInvalidMimeTypeMessage())
                ->addViolation();

            return;
        }

        if (!$constraint->getUploadedImageValidator()->isValidFormat($value)) {
            $this->context
                ->buildViolation($constraint->getInvalidFormatMessage())
                ->addViolation();
        }
    }
}
