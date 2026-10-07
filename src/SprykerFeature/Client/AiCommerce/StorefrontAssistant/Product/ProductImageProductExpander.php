<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\ProductImageSetStorageTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductTransfer;
use Spryker\Client\ProductImageStorage\ProductImageStorageClientInterface;

class ProductImageProductExpander implements ProductExpanderInterface
{
    public function __construct(protected ProductImageStorageClientInterface $productImageStorageClient)
    {
    }

    public function expand(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer,
        string $localeName,
        string $storeName
    ): StorefrontAssistantProductCollectionTransfer {
        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $imageUrl = $this->findSmallImageUrl($storefrontAssistantProductTransfer, $localeName);

            if ($imageUrl !== null) {
                $storefrontAssistantProductTransfer->setImageUrl($imageUrl);
            }
        }

        return $storefrontAssistantProductCollectionTransfer;
    }

    protected function findSmallImageUrl(
        StorefrontAssistantProductTransfer $storefrontAssistantProductTransfer,
        string $localeName
    ): ?string {
        $idProductAbstract = $storefrontAssistantProductTransfer->getIdProductAbstract();

        if ($idProductAbstract === null || $idProductAbstract <= 0) {
            return null;
        }

        $productAbstractImageStorageTransfer = $this->productImageStorageClient
            ->findProductImageAbstractStorageTransfer($idProductAbstract, $localeName);

        if ($productAbstractImageStorageTransfer === null) {
            return null;
        }

        foreach ($productAbstractImageStorageTransfer->getImageSets() as $productImageSetStorageTransfer) {
            $externalUrlSmall = $this->findSmallImageUrlInImageSet($productImageSetStorageTransfer);

            if ($externalUrlSmall !== null) {
                return $externalUrlSmall;
            }
        }

        return null;
    }

    protected function findSmallImageUrlInImageSet(ProductImageSetStorageTransfer $productImageSetStorageTransfer): ?string
    {
        foreach ($productImageSetStorageTransfer->getImages() as $productImageStorageTransfer) {
            $externalUrlSmall = trim((string)$productImageStorageTransfer->getExternalUrlSmall());

            if ($externalUrlSmall !== '') {
                return $externalUrlSmall;
            }
        }

        return null;
    }
}
