<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use Generated\Shared\Transfer\ProductValidationTransfer;

interface ProductRecognitionValidatorInterface
{
    public function validate(ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer): ProductValidationTransfer;
}
