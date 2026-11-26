<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;

interface ProductRecognizerInterface
{
    public function recognizeProducts(string $base64Content, string $mimeType, ?string $fileExtension = null): ProductRecognitionCollectionTransfer;
}
