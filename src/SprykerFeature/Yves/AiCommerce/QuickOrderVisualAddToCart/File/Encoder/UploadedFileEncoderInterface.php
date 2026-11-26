<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Encoder;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UploadedFileEncoderInterface
{
    /**
     * Reads the uploaded file contents and returns them as a base64-encoded string.
     * Returns null when the file cannot be read.
     */
    public function encodeToBase64(UploadedFile $uploadedFile): ?string;
}
