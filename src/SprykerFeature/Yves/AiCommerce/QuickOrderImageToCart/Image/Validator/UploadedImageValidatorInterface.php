<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UploadedImageValidatorInterface
{
    public function isValidFormat(UploadedFile $file): bool;

    public function isValidMimeType(UploadedFile $file): bool;

    public function isValidFileSize(UploadedFile $file): bool;
}
