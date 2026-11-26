<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Validator;

use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileValidator implements UploadedFileValidatorInterface
{
    public function __construct(protected AiCommerceConfig $config)
    {
    }

    public function isValidFormat(UploadedFile $file): bool
    {
        $fileExtension = $file->guessExtension();

        if ($fileExtension === null) {
            return false;
        }

        return in_array(strtolower($fileExtension), $this->config->getQuickOrderVisualAddToCartSupportedFileExtensions(), true);
    }

    public function isValidMimeType(UploadedFile $file): bool
    {
        $fileMimeType = strtolower((string)$file->getMimeType());

        return in_array($fileMimeType, $this->config->getQuickOrderVisualAddToCartSupportedFileMimeTypes(), true);
    }

    public function isValidFileSize(UploadedFile $file): bool
    {
        return $file->getSize() <= $this->config->getQuickOrderVisualAddToCartMaxFileSizeInBytes();
    }
}
