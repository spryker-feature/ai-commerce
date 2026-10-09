<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\ProductReviewStorage\ProductReviewStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;

class ProductReviewDetailsExpander implements ProductDetailsExpanderInterface
{
    protected const int RATING_PRECISION = 1;

    public function __construct(protected ProductReviewStorageClientInterface $productReviewStorageClient)
    {
    }

    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, mixed>
     */
    public function expand(
        array $productDetails,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): array {
        $productReviewStorageTransfer = $this->productReviewStorageClient->findProductAbstractReview(
            $storefrontAssistantProductDetailsRequestTransfer->getIdProductAbstractOrFail(),
        );
        $reviewCount = (int)$productReviewStorageTransfer?->getReviewCount();

        if ($productReviewStorageTransfer === null || $reviewCount <= 0) {
            return $productDetails;
        }

        $productDetails[StorefrontAssistantProductMapper::KEY_RATING] = round((float)$productReviewStorageTransfer->getAverageRating(), static::RATING_PRECISION);
        $productDetails[StorefrontAssistantProductMapper::KEY_REVIEW_COUNT] = $reviewCount;

        return $productDetails;
    }
}
