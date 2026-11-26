<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\Validator\Rule;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;

class NonEmptyProductCollectionProductValidationRule implements ProductValidationRuleInterface
{
    protected const string GLOSSARY_KEY_EMPTY_PRODUCT_COLLECTION = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.no-products-recognized';

    public function validate(ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer): bool
    {
        return $productRecognitionCollectionTransfer->getProductRecognitions()->count() > 0;
    }

    public function getErrorMessage(): string
    {
        return static::GLOSSARY_KEY_EMPTY_PRODUCT_COLLECTION;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrorMessageParameters(): array
    {
        return [];
    }
}
