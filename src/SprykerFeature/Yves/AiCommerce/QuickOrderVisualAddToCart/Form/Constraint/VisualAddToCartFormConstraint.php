<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\Constraint;

use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Validator\UploadedFileValidatorInterface;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

class VisualAddToCartFormConstraint extends SymfonyConstraint
{
    public const string OPTION_UPLOADED_FILE_VALIDATOR = 'uploadedFileValidator';

    protected const string ERROR_MESSAGE_INVALID_FORMAT = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.invalid-format';

    protected const string ERROR_MESSAGE_INVALID_MIME_TYPE = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.invalid-mime-type';

    protected const string ERROR_MESSAGE_NO_FILE = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.no-file';

    protected const string ERROR_MESSAGE_FILE_TOO_LARGE = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.file-too-large';

    protected UploadedFileValidatorInterface $uploadedFileValidator;

    public function getUploadedFileValidator(): UploadedFileValidatorInterface
    {
        return $this->uploadedFileValidator;
    }

    public function getInvalidFormatMessage(): string
    {
        return static::ERROR_MESSAGE_INVALID_FORMAT;
    }

    public function getInvalidMimeTypeMessage(): string
    {
        return static::ERROR_MESSAGE_INVALID_MIME_TYPE;
    }

    public function getNoFileMessage(): string
    {
        return static::ERROR_MESSAGE_NO_FILE;
    }

    public function getFileTooLargeMessage(): string
    {
        return static::ERROR_MESSAGE_FILE_TOO_LARGE;
    }
}
