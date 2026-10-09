<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductAbstractIdResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class ProductDetailsReader implements ProductDetailsReaderInterface
{
    public const string PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    public const string PARAMETER_SKU = 'sku';

    public const string PARAMETER_QUANTITY = 'quantity';

    public const string RESULT_KEY_ERROR = 'error';

    protected const string ERROR_MISSING_IDENTIFIER = 'Pass the idProductAbstract or the sku of the product.';

    protected const string ERROR_UNKNOWN_SKU = 'No product found for SKU "%s".';

    protected const string ERROR_UNKNOWN_ID_PRODUCT_ABSTRACT = 'No product found for idProductAbstract %d.';

    /**
     * @param array<\SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsExpanderInterface> $productDetailsExpanders
     */
    public function __construct(
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected ProductAbstractIdResolverInterface $productAbstractIdResolver,
        protected ProductStorageClientInterface $productStorageClient,
        protected ToolResultProductExpanderInterface $toolResultProductExpander,
        protected StoreClientInterface $storeClient,
        protected LocaleClientInterface $localeClient,
        protected array $productDetailsExpanders
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function getProductDetails(array $arguments): array
    {
        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $idProductAbstract = $this->extractIdProductAbstract($arguments);
        $sku = $this->extractSku($arguments);

        if ($idProductAbstract === null && $sku === null) {
            return [static::RESULT_KEY_ERROR => static::ERROR_MISSING_IDENTIFIER];
        }

        $localeName = $this->localeClient->getCurrentLocale();
        $idProductAbstract ??= $this->productAbstractIdResolver->resolveIdProductAbstractBySku((string)$sku, $localeName);

        if ($idProductAbstract === null) {
            return [static::RESULT_KEY_ERROR => sprintf(static::ERROR_UNKNOWN_SKU, $sku)];
        }

        $storefrontAssistantProductDetailsRequestTransfer = $this->createProductDetailsRequest($idProductAbstract, $sku, $localeName)
            ->setQuantity($this->extractQuantity($arguments));

        if ($storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData() === []) {
            return [static::RESULT_KEY_ERROR => sprintf(static::ERROR_UNKNOWN_ID_PRODUCT_ABSTRACT, $idProductAbstract)];
        }

        return $this->expandProductDetails($storefrontAssistantProductDetailsRequestTransfer);
    }

    /**
     * @return array<string, mixed>
     */
    protected function expandProductDetails(
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): array {
        $products = $this->toolResultProductExpander->expandProductsForAgent([
            [StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT => $storefrontAssistantProductDetailsRequestTransfer->getIdProductAbstractOrFail()],
        ]);
        $productDetails = $products[0] ?? [];

        foreach ($this->productDetailsExpanders as $productDetailsExpander) {
            $productDetails = $productDetailsExpander->expand($productDetails, $storefrontAssistantProductDetailsRequestTransfer);
        }

        return $productDetails;
    }

    protected function createProductDetailsRequest(int $idProductAbstract, ?string $sku, string $localeName): StorefrontAssistantProductDetailsRequestTransfer
    {
        $storeName = (string)$this->storeClient->getCurrentStore()->getName();
        $productAbstractStorageData = $this->productStorageClient->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
            [$idProductAbstract],
            $localeName,
            $storeName,
        )[$idProductAbstract] ?? null;

        return (new StorefrontAssistantProductDetailsRequestTransfer())
            ->setIdProductAbstract($idProductAbstract)
            ->setSku($sku)
            ->setLocaleName($localeName)
            ->setStoreName($storeName)
            ->setProductAbstractStorageData(is_array($productAbstractStorageData) ? $productAbstractStorageData : []);
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractIdProductAbstract(array $arguments): ?int
    {
        $idProductAbstract = $arguments[static::PARAMETER_ID_PRODUCT_ABSTRACT] ?? null;

        if (is_bool($idProductAbstract) || !is_numeric($idProductAbstract) || (int)$idProductAbstract <= 0) {
            return null;
        }

        return (int)$idProductAbstract;
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractSku(array $arguments): ?string
    {
        $sku = $arguments[static::PARAMETER_SKU] ?? null;

        if (!is_scalar($sku) || is_bool($sku) || trim((string)$sku) === '') {
            return null;
        }

        return trim((string)$sku);
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractQuantity(array $arguments): ?int
    {
        $quantity = $arguments[static::PARAMETER_QUANTITY] ?? null;

        if (is_bool($quantity) || !is_numeric($quantity) || (int)$quantity <= 0) {
            return null;
        }

        return (int)$quantity;
    }
}
