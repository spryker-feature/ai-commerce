<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;

interface StorefrontAssistantProductMapperInterface
{
    /**
     * @param array<mixed> $products
     */
    public function mapProductsToStorefrontAssistantProductCollection(
        array $products,
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer
    ): StorefrontAssistantProductCollectionTransfer;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapStorefrontAssistantProductCollectionToProducts(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer
    ): array;
}
