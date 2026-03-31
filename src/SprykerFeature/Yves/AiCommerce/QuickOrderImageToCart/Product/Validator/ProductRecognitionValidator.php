<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use Generated\Shared\Transfer\ProductValidationErrorTransfer;
use Generated\Shared\Transfer\ProductValidationTransfer;

class ProductRecognitionValidator implements ProductRecognitionValidatorInterface
{
    /**
     * @param array<\SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\Rule\ProductValidationRuleInterface> $productValidationRules
     */
    public function __construct(protected array $productValidationRules)
    {
    }

    public function validate(ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer): ProductValidationTransfer
    {
        $productValidationTransfer = (new ProductValidationTransfer())->setIsValid(true);

        foreach ($this->productValidationRules as $productValidationRule) {
            if (!$productValidationRule->validate($productRecognitionCollectionTransfer)) {
                $productValidationTransfer->setIsValid(false);
                $productValidationTransfer->addValidationError(
                    (new ProductValidationErrorTransfer())
                        ->setGlossaryKey($productValidationRule->getErrorMessage())
                        ->setParameters($productValidationRule->getErrorMessageParameters()),
                );
            }
        }

        return $productValidationTransfer;
    }
}
