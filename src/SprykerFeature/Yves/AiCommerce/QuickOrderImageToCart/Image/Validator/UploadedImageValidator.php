<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Validator;

use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedImageValidator implements UploadedImageValidatorInterface
{
    public function __construct(protected AiCommerceConfig $config)
    {
    }

    public function isValidFormat(UploadedFile $file): bool
    {
        $supportedImageExtensions = $this->config->getQuickOrderImageToCartSupportedImageExtensions();
        $fileExtension = $file->guessExtension();

        if ($fileExtension === null) {
            return false;
        }

        return in_array(strtolower($fileExtension), $supportedImageExtensions, true);
    }

    public function isValidMimeType(UploadedFile $file): bool
    {
        $supportedImageMimeTypes = $this->config->getQuickOrderImageToCartSupportedMimeTypes();
        $fileMimeType = strtolower((string)$file->getMimeType());

        return in_array($fileMimeType, $supportedImageMimeTypes, true);
    }

    public function isValidFileSize(UploadedFile $file): bool
    {
        return $file->getSize() <= $this->config->getQuickOrderImageToCartMaxFileSizeInBytes();
    }
}
