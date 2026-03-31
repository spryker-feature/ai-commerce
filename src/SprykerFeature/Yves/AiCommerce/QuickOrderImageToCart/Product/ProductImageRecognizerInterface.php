<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;

interface ProductImageRecognizerInterface
{
    /**
     * Sends a base64-encoded image to the AI service and returns the recognised products.
     * On failure, returns the transfer with isSuccessful set to false instead of throwing.
     */
    public function recognizeProducts(string $base64Image, string $mimeType): ProductRecognitionCollectionTransfer;
}
