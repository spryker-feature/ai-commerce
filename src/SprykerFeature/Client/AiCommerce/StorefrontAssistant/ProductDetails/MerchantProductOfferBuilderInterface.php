<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;

interface MerchantProductOfferBuilderInterface
{
    /**
     * @param array<string, mixed> $productDetails
     * @param array<string, int> $productConcreteIdsBySku
     *
     * @return list<array{0: int|null, 1: array<string, mixed>}>
     */
    public function buildMerchantProductOffers(
        array $productDetails,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer,
        array $productConcreteIdsBySku,
        bool $canSeePrice
    ): array;
}
