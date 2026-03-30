<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Encoder;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedImageEncoder implements UploadedImageEncoderInterface
{
    /**
     * {@inheritDoc}
     */
    public function encodeToBase64(UploadedFile $uploadedFile): ?string
    {
        $contents = file_get_contents($uploadedFile->getPathname());

        if ($contents === false) {
            return null;
        }

        return base64_encode($contents);
    }
}
