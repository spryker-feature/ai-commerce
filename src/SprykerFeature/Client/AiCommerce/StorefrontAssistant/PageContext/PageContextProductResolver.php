<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\PageContext;

use Generated\Shared\Transfer\StorefrontAssistantPageContextTransfer;
use Spryker\Client\ProductLabelStorage\ProductLabelStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductAbstractIdResolverInterface;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;

class PageContextProductResolver implements PageContextProductResolverInterface
{
    protected const string KEY_NAME = 'name';

    protected const string KEY_SKU = 'sku';

    public function __construct(
        protected ProductAbstractIdResolverInterface $productAbstractIdResolver,
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductLabelStorageClientInterface $productLabelStorageClient,
        protected StoreClientInterface $storeClient
    ) {
    }

    public function expandPageContext(
        StorefrontAssistantPageContextTransfer $storefrontAssistantPageContextTransfer,
        string $localeName
    ): StorefrontAssistantPageContextTransfer {
        $storefrontAssistantPageContextTransfer
            ->setIdProductAbstract(null)
            ->setProductLabels([]);

        $productSku = trim((string)$storefrontAssistantPageContextTransfer->getProductSku());

        if ($storefrontAssistantPageContextTransfer->getPageType() !== StorefrontAssistantPageType::Product->value || $productSku === '') {
            return $storefrontAssistantPageContextTransfer;
        }

        $idProductAbstract = $this->productAbstractIdResolver->resolveIdProductAbstractBySku($productSku, $localeName);

        if ($idProductAbstract === null) {
            return $storefrontAssistantPageContextTransfer;
        }

        $productAbstractStorageData = $this->productStorageClient->findProductAbstractStorageData($idProductAbstract, $localeName);

        if ($productAbstractStorageData === null) {
            return $storefrontAssistantPageContextTransfer;
        }

        return $storefrontAssistantPageContextTransfer
            ->setIdProductAbstract($idProductAbstract)
            ->setProductName($this->extractString($productAbstractStorageData, static::KEY_NAME) ?? $storefrontAssistantPageContextTransfer->getProductName())
            ->setProductSku($this->extractString($productAbstractStorageData, static::KEY_SKU) ?? $productSku)
            ->setProductLabels($this->resolveLabelNames($idProductAbstract, $localeName));
    }

    /**
     * @param array<string, mixed> $productAbstractStorageData
     */
    protected function extractString(array $productAbstractStorageData, string $key): ?string
    {
        $value = $productAbstractStorageData[$key] ?? null;

        if (!is_scalar($value) || trim((string)$value) === '') {
            return null;
        }

        return trim((string)$value);
    }

    /**
     * @return list<string>
     */
    protected function resolveLabelNames(int $idProductAbstract, string $localeName): array
    {
        $productLabelDictionaryItemTransfers = $this->productLabelStorageClient->findLabelsByIdProductAbstract(
            $idProductAbstract,
            $localeName,
            $this->storeClient->getCurrentStore()->getNameOrFail(),
        );

        $labelNames = [];

        foreach ($productLabelDictionaryItemTransfers as $productLabelDictionaryItemTransfer) {
            $labelName = trim((string)$productLabelDictionaryItemTransfer->getName());

            if ($labelName !== '') {
                $labelNames[] = $labelName;
            }
        }

        return array_values(array_unique($labelNames));
    }
}
