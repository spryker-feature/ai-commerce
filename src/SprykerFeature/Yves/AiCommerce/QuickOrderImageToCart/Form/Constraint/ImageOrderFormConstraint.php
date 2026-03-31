<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\Constraint;

use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Validator\UploadedImageValidatorInterface;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

class ImageOrderFormConstraint extends SymfonyConstraint
{
    public const string OPTION_UPLOADED_IMAGE_VALIDATOR = 'uploadedImageValidator';

    protected const string ERROR_MESSAGE_INVALID_FORMAT = 'ai-commerce.quick-order-image-to-cart.image-order.errors.invalid-format';

    protected const string ERROR_MESSAGE_INVALID_MIME_TYPE = 'ai-commerce.quick-order-image-to-cart.image-order.errors.invalid-mime-type';

    protected const string ERROR_MESSAGE_NO_IMAGE = 'ai-commerce.quick-order-image-to-cart.image-order.errors.no-image';

    protected const string ERROR_MESSAGE_FILE_TOO_LARGE = 'ai-commerce.quick-order-image-to-cart.image-order.errors.file-too-large';

    protected UploadedImageValidatorInterface $uploadedImageValidator;

    public function getUploadedImageValidator(): UploadedImageValidatorInterface
    {
        return $this->uploadedImageValidator;
    }

    public function getInvalidFormatMessage(): string
    {
        return static::ERROR_MESSAGE_INVALID_FORMAT;
    }

    public function getInvalidMimeTypeMessage(): string
    {
        return static::ERROR_MESSAGE_INVALID_MIME_TYPE;
    }

    public function getNoImageMessage(): string
    {
        return static::ERROR_MESSAGE_NO_IMAGE;
    }

    public function getFileTooLargeMessage(): string
    {
        return static::ERROR_MESSAGE_FILE_TOO_LARGE;
    }
}
