<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\Rule;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;

interface ProductValidationRuleInterface
{
    public function validate(ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer): bool;

    public function getErrorMessage(): string;

    /**
     * @return array<string, mixed>
     */
    public function getErrorMessageParameters(): array;
}
