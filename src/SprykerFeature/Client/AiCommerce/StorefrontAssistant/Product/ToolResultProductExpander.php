<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapperInterface;

class ToolResultProductExpander implements ToolResultProductExpanderInterface
{
    /**
     * @var list<string>
     */
    protected const array AGENT_EXCLUDED_KEYS = [StorefrontAssistantProductMapper::KEY_IMAGE_URL];

    public function __construct(
        protected StorefrontAssistantProductMapperInterface $storefrontAssistantProductMapper,
        protected ProductCollectionExpanderInterface $productCollectionExpander
    ) {
    }

    public function expandProducts(array $products): array
    {
        if ($products === []) {
            return [];
        }

        return $this->storefrontAssistantProductMapper->mapStorefrontAssistantProductCollectionToProducts(
            $this->productCollectionExpander->expand(
                $this->storefrontAssistantProductMapper->mapProductsToStorefrontAssistantProductCollection(
                    $products,
                    new StorefrontAssistantProductCollectionTransfer(),
                ),
            ),
        );
    }

    public function expandProductsForAgent(array $products): array
    {
        return array_map(
            static fn (array $product): array => array_diff_key(
                $product,
                array_flip(static::AGENT_EXCLUDED_KEYS),
            ),
            $this->expandProducts($products),
        );
    }
}
