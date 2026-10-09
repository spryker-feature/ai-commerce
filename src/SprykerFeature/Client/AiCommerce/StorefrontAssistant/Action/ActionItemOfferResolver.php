<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Action;

use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;

class ActionItemOfferResolver implements ActionItemOfferResolverInterface
{
    public function __construct(protected ProductOfferStorageClientInterface $productOfferStorageClient)
    {
    }

    public function findProductOffer(string $productOfferReference, string $productConcreteSku): ?ProductOfferStorageTransfer
    {
        $productOfferReference = trim($productOfferReference) !== ''
            ? trim($productOfferReference)
            : $this->resolveSellableDefaultProductOfferReference($productConcreteSku);

        if ($productOfferReference === '') {
            return null;
        }

        $productOfferStorageTransfer = $this->productOfferStorageClient->findProductOfferStorageByReference($productOfferReference);

        if ($productOfferStorageTransfer === null || mb_strtolower((string)$productOfferStorageTransfer->getProductConcreteSku()) !== mb_strtolower($productConcreteSku)) {
            return null;
        }

        return $productOfferStorageTransfer;
    }

    protected function resolveSellableDefaultProductOfferReference(string $productConcreteSku): string
    {
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())->setProductConcreteSkus([$productConcreteSku]);
        $defaultProductOfferReference = (string)$this->productOfferStorageClient->findProductConcreteDefaultProductOffer($productOfferStorageCriteriaTransfer);

        if ($defaultProductOfferReference === '') {
            return '';
        }

        $sellableProductOfferReferences = [];
        $hasProductOffers = false;

        foreach ($this->productOfferStorageClient->getProductOfferStoragesBySkus($productOfferStorageCriteriaTransfer)->getProductOffers() as $productOfferStorageTransfer) {
            $hasProductOffers = true;

            if ($this->isSellable($productOfferStorageTransfer)) {
                $sellableProductOfferReferences[] = (string)$productOfferStorageTransfer->getProductOfferReference();
            }
        }

        if (!$hasProductOffers || in_array($defaultProductOfferReference, $sellableProductOfferReferences, true)) {
            return $defaultProductOfferReference;
        }

        return $sellableProductOfferReferences[0] ?? '';
    }

    protected function isSellable(ProductOfferStorageTransfer $productOfferStorageTransfer): bool
    {
        $stockQuantity = $productOfferStorageTransfer->getStockQuantity();

        return $productOfferStorageTransfer->getIsNeverOutOfStock() === true || $stockQuantity === null || $stockQuantity > 0;
    }
}
