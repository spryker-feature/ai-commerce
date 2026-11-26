<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Collector;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use Generated\Shared\Transfer\ProductRecognitionTransfer;
use Generated\Shared\Transfer\QuickOrderItemTransfer;
use Generated\Shared\Transfer\QuickOrderPageResponseTransfer;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Finder\CatalogProductFinderInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Matcher\ProductNameMatcherInterface;

class QuickOrderItemCollector implements QuickOrderItemCollectorInterface
{
    protected const int DEFAULT_QUICK_ORDER_ITEM_QUANTITY = 1;

    public function __construct(
        protected CatalogProductFinderInterface $catalogProductFinder,
        protected ProductNameMatcherInterface $productNameMatcher,
    ) {
    }

    public function collectQuickOrderItemsByProductRecognitions(
        ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer,
    ): QuickOrderPageResponseTransfer {
        $quickOrderPageResponseTransfer = new QuickOrderPageResponseTransfer();
        $productNames = $this->collectProductNames($productRecognitionCollectionTransfer);

        if (!$productNames) {
            return $quickOrderPageResponseTransfer;
        }

        $foundProducts = $this->catalogProductFinder->findProductsByNames($productNames);

        foreach ($productRecognitionCollectionTransfer->getProductRecognitions() as $productRecognitionTransfer) {
            if (!$productRecognitionTransfer->getName()) {
                continue;
            }

            $this->processProductRecognition($productRecognitionTransfer, $quickOrderPageResponseTransfer, $foundProducts);
        }

        return $quickOrderPageResponseTransfer;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ItemTransfer|null> $foundProducts
     */
    protected function processProductRecognition(
        ProductRecognitionTransfer $productRecognitionTransfer,
        QuickOrderPageResponseTransfer $quickOrderPageResponseTransfer,
        array $foundProducts,
    ): void {
        $recognizedProductName = $productRecognitionTransfer->getNameOrFail();
        $itemTransfer = $foundProducts[$recognizedProductName] ?? null;

        if (!$itemTransfer || !$this->isMatchingProduct($itemTransfer, $recognizedProductName)) {
            $quickOrderPageResponseTransfer->addNotFoundProductName($recognizedProductName);

            return;
        }

        $quickOrderPageResponseTransfer->addQuickOrderItem(
            $this->createQuickOrderItemTransfer($itemTransfer, $productRecognitionTransfer),
        );
    }

    protected function isMatchingProduct(ItemTransfer $itemTransfer, string $recognizedProductName): bool
    {
        if ($this->isMatchingSku($itemTransfer, $recognizedProductName)) {
            return true;
        }

        return $this->productNameMatcher->isMatchingName($itemTransfer->getNameOrFail(), $recognizedProductName);
    }

    protected function isMatchingSku(ItemTransfer $itemTransfer, string $recognizedProductName): bool
    {
        $recognizedSku = mb_strtoupper(trim($recognizedProductName));

        if ($recognizedSku === '') {
            return false;
        }

        return $recognizedSku === mb_strtoupper((string)$itemTransfer->getSku());
    }

    /**
     * @return array<string>
     */
    protected function collectProductNames(
        ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer,
    ): array {
        $productNames = [];

        foreach ($productRecognitionCollectionTransfer->getProductRecognitions() as $productRecognitionTransfer) {
            if (!$productRecognitionTransfer->getName()) {
                continue;
            }

            $productNames[] = $productRecognitionTransfer->getNameOrFail();
        }

        return $productNames;
    }

    protected function createQuickOrderItemTransfer(
        ItemTransfer $itemTransfer,
        ProductRecognitionTransfer $productRecognitionTransfer,
    ): QuickOrderItemTransfer {
        return (new QuickOrderItemTransfer())
            ->setSku($itemTransfer->getSku())
            ->setQuantity($productRecognitionTransfer->getQuantity() ?? static::DEFAULT_QUICK_ORDER_ITEM_QUANTITY);
    }
}
