<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\Rule;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class ProductLimitProductValidationRule implements ProductValidationRuleInterface
{
    protected const string GLOSSARY_KEY_PRODUCT_LIMIT_EXCEEDED = 'ai-commerce.quick-order-image-to-cart.image-order.errors.product-limit-exceeded';

    public function __construct(protected AiCommerceConfig $config)
    {
    }

    public function validate(ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer): bool
    {
        return $productRecognitionCollectionTransfer->getProductRecognitions()->count() <= $this->config->getQuickOrderImageToCartMaxProducts();
    }

    public function getErrorMessage(): string
    {
        return static::GLOSSARY_KEY_PRODUCT_LIMIT_EXCEEDED;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrorMessageParameters(): array
    {
        return ['%maxProducts%' => $this->config->getQuickOrderImageToCartMaxProducts()];
    }
}
