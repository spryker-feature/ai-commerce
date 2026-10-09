<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\MerchantStorageCriteriaTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;

class ProductOfferDetailsExpander implements ProductDetailsExpanderInterface
{
    /**
     * @uses \Spryker\Client\CustomerAccessPermission\Plugin\SeePricePermissionPlugin::KEY
     */
    public const string PERMISSION_SEE_PRICE = 'SeePricePermissionPlugin';

    public const string KEY_OFFERS = 'offers';

    public const string KEY_LOWEST_OFFER_MERCHANT_NAME = 'lowestOfferMerchantName';

    public const string KEY_SELLER_PRICES = 'sellerPrices';

    public const string KEY_MERCHANT_NAME = 'merchantName';

    public const string KEY_MERCHANT_REFERENCE = 'merchantReference';

    public const string KEY_PRODUCT_CONCRETE_SKU = 'productConcreteSku';

    public const string KEY_PRODUCT_OFFER_REFERENCE = 'productOfferReference';

    public const string KEY_PRICE_FORMATTED = 'priceFormatted';

    public const string KEY_IS_AVAILABLE = 'isAvailable';

    public const string KEY_IS_DEFAULT = 'isDefault';

    protected const string STORAGE_KEY_MERCHANT_REFERENCE = 'merchant_reference';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const int DEFAULT_QUANTITY = 1;

    protected const string SELLER_PRICE_SEPARATOR = '; ';

    protected const string SELLER_PRICE_OUT_OF_STOCK = ' (out of stock)';

    protected const string SELLER_PRICE_UNPUBLISHED = ' (no price)';

    public function __construct(
        protected ProductOfferStorageClientInterface $productOfferStorageClient,
        protected PriceProductStorageClientInterface $priceProductStorageClient,
        protected MerchantStorageClientInterface $merchantStorageClient,
        protected PermissionClientInterface $permissionClient,
        protected StorefrontAssistantPriceFormatterInterface $storefrontAssistantPriceFormatter
    ) {
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
        $productConcreteIdsBySku = $this->extractProductConcreteIdsBySku($storefrontAssistantProductDetailsRequestTransfer);

        if ($productConcreteIdsBySku === []) {
            return $productDetails;
        }

        $canSeePrice = $this->permissionClient->can(static::PERMISSION_SEE_PRICE);
        $sortableOffers = array_merge(
            $this->buildMerchantProductOffers($productDetails, $storefrontAssistantProductDetailsRequestTransfer, $productConcreteIdsBySku, $canSeePrice),
            $this->buildProductOffers(array_map('strval', array_keys($productConcreteIdsBySku)), $canSeePrice),
        );

        if ($sortableOffers === []) {
            return $productDetails;
        }

        $offers = $this->sortOffersByLowestPrice($sortableOffers);
        $productDetails[static::KEY_OFFERS] = $offers;
        $lowestOfferMerchantName = $canSeePrice && count($offers) > 1 ? $this->findLowestOfferMerchantName($offers) : null;

        if ($lowestOfferMerchantName !== null) {
            $productDetails[static::KEY_LOWEST_OFFER_MERCHANT_NAME] = $lowestOfferMerchantName;
        }

        if ($canSeePrice && count($offers) > 1) {
            $productDetails[static::KEY_SELLER_PRICES] = $this->buildSellerPrices($offers);
        }

        return $productDetails;
    }

    /**
     * @param list<array<string, mixed>> $offers
     */
    protected function buildSellerPrices(array $offers): string
    {
        $sellerPrices = [];

        foreach ($offers as $offer) {
            $sellerPrice = isset($offer[static::KEY_PRICE_FORMATTED])
                ? sprintf('%s %s', $offer[static::KEY_MERCHANT_NAME], $offer[static::KEY_PRICE_FORMATTED])
                : $offer[static::KEY_MERCHANT_NAME] . static::SELLER_PRICE_UNPUBLISHED;

            if (($offer[static::KEY_IS_AVAILABLE] ?? true) === false) {
                $sellerPrice .= static::SELLER_PRICE_OUT_OF_STOCK;
            }

            $sellerPrices[$sellerPrice] = $sellerPrice;
        }

        return implode(static::SELLER_PRICE_SEPARATOR, $sellerPrices);
    }

    /**
     * @param list<string> $productConcreteSkus
     *
     * @return list<array{0: int|null, 1: array<string, mixed>}>
     */
    protected function buildProductOffers(array $productConcreteSkus, bool $canSeePrice): array
    {
        $productOfferStorageCollectionTransfer = $this->productOfferStorageClient->getProductOfferStoragesBySkus(
            (new ProductOfferStorageCriteriaTransfer())->setProductConcreteSkus($productConcreteSkus),
        );
        $sortableOffers = [];

        foreach ($productOfferStorageCollectionTransfer->getProductOffers() as $productOfferStorageTransfer) {
            $sortableOffers[] = $this->buildProductOffer($productOfferStorageTransfer, $canSeePrice);
        }

        return $sortableOffers;
    }

    /**
     * @return array{0: int|null, 1: array<string, mixed>}
     */
    protected function buildProductOffer(ProductOfferStorageTransfer $productOfferStorageTransfer, bool $canSeePrice): array
    {
        $merchantReference = (string)$productOfferStorageTransfer->getMerchantReference();
        $offer = [
            static::KEY_MERCHANT_NAME => $productOfferStorageTransfer->getMerchantStorage()?->getName() ?? $merchantReference,
            static::KEY_MERCHANT_REFERENCE => $merchantReference,
            static::KEY_PRODUCT_CONCRETE_SKU => (string)$productOfferStorageTransfer->getProductConcreteSku(),
            static::KEY_PRODUCT_OFFER_REFERENCE => (string)$productOfferStorageTransfer->getProductOfferReference(),
        ];
        $price = $canSeePrice ? $productOfferStorageTransfer->getPrice()?->getPrice() : null;

        if ($price !== null) {
            $offer[static::KEY_PRICE_FORMATTED] = $this->storefrontAssistantPriceFormatter->formatAmount($price);
        }

        $isAvailable = $this->resolveProductOfferAvailability($productOfferStorageTransfer);

        if ($isAvailable !== null) {
            $offer[static::KEY_IS_AVAILABLE] = $isAvailable;
        }

        if ($productOfferStorageTransfer->getIsDefault() === true) {
            $offer[static::KEY_IS_DEFAULT] = true;
        }

        return [$price, $offer];
    }

    protected function resolveProductOfferAvailability(ProductOfferStorageTransfer $productOfferStorageTransfer): ?bool
    {
        $isNeverOutOfStock = $productOfferStorageTransfer->getIsNeverOutOfStock();
        $stockQuantity = $productOfferStorageTransfer->getStockQuantity();

        if ($isNeverOutOfStock === null && $stockQuantity === null) {
            return null;
        }

        return $isNeverOutOfStock === true || $stockQuantity > 0;
    }

    /**
     * @param array<string, mixed> $productDetails
     * @param array<string, int> $productConcreteIdsBySku
     *
     * @return list<array{0: int|null, 1: array<string, mixed>}>
     */
    protected function buildMerchantProductOffers(
        array $productDetails,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer,
        array $productConcreteIdsBySku,
        bool $canSeePrice
    ): array {
        $merchantReference = trim((string)($storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData()[static::STORAGE_KEY_MERCHANT_REFERENCE] ?? ''));

        if ($merchantReference === '') {
            return [];
        }

        $merchantStorageTransfer = $this->merchantStorageClient->findOne(
            (new MerchantStorageCriteriaTransfer())->addMerchantReference($merchantReference),
        );

        if ($merchantStorageTransfer === null) {
            return [];
        }

        $productConcreteSku = $this->resolveMerchantProductConcreteSku($storefrontAssistantProductDetailsRequestTransfer, $productConcreteIdsBySku);
        $offer = [
            static::KEY_MERCHANT_NAME => (string)$merchantStorageTransfer->getName(),
            static::KEY_MERCHANT_REFERENCE => $merchantReference,
            static::KEY_PRODUCT_CONCRETE_SKU => $productConcreteSku,
        ];
        $price = $canSeePrice
            ? $this->resolveMerchantProductPrice(
                $storefrontAssistantProductDetailsRequestTransfer->getIdProductAbstractOrFail(),
                $productConcreteIdsBySku[$productConcreteSku],
            )
            : null;

        if ($price !== null) {
            $offer[static::KEY_PRICE_FORMATTED] = $this->storefrontAssistantPriceFormatter->formatAmount($price);
        }

        $isAvailable = $this->resolveMerchantProductAvailability($productDetails, $productConcreteSku, count($productConcreteIdsBySku));

        if ($isAvailable !== null) {
            $offer[static::KEY_IS_AVAILABLE] = $isAvailable;
        }

        return [[$price, $offer]];
    }

    /**
     * @param array<string, int> $productConcreteIdsBySku
     */
    protected function resolveMerchantProductConcreteSku(
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer,
        array $productConcreteIdsBySku
    ): string {
        $requestedSku = trim((string)$storefrontAssistantProductDetailsRequestTransfer->getSku());

        if (isset($productConcreteIdsBySku[$requestedSku])) {
            return $requestedSku;
        }

        return (string)array_key_first($productConcreteIdsBySku);
    }

    protected function resolveMerchantProductPrice(int $idProductAbstract, int $idProductConcrete): ?int
    {
        return $this->priceProductStorageClient->getResolvedCurrentProductPriceTransfer(
            (new PriceProductFilterTransfer())
                ->setIdProductAbstract($idProductAbstract)
                ->setIdProduct($idProductConcrete)
                ->setQuantity(static::DEFAULT_QUANTITY),
        )->getPrice();
    }

    /**
     * @param array<string, mixed> $productDetails
     */
    protected function resolveMerchantProductAvailability(array $productDetails, string $productConcreteSku, int $productConcreteCount): ?bool
    {
        $concretes = $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_CONCRETES] ?? [];

        foreach (is_array($concretes) ? $concretes : [] as $concrete) {
            if (($concrete[ProductAttributeDetailsExpander::KEY_CONCRETE_SKU] ?? null) === $productConcreteSku && is_bool($concrete[ProductAvailabilityDetailsExpander::KEY_IS_AVAILABLE] ?? null)) {
                return $concrete[ProductAvailabilityDetailsExpander::KEY_IS_AVAILABLE];
            }
        }

        $availability = $productDetails[ProductAvailabilityDetailsExpander::KEY_AVAILABILITY] ?? null;

        if ($productConcreteCount !== 1 || !is_array($availability) || ($availability[ProductAvailabilityDetailsExpander::KEY_IS_KNOWN] ?? false) !== true) {
            return null;
        }

        return ($availability[ProductAvailabilityDetailsExpander::KEY_IS_AVAILABLE] ?? false) === true;
    }

    /**
     * @param list<array{0: int|null, 1: array<string, mixed>}> $sortableOffers
     *
     * @return list<array<string, mixed>>
     */
    protected function sortOffersByLowestPrice(array $sortableOffers): array
    {
        usort($sortableOffers, static function (array $firstSortableOffer, array $secondSortableOffer): int {
            [$firstPrice] = $firstSortableOffer;
            [$secondPrice] = $secondSortableOffer;

            if ($firstPrice === null || $secondPrice === null) {
                return ($firstPrice === null) <=> ($secondPrice === null);
            }

            return $firstPrice <=> $secondPrice;
        });

        return array_map(static fn (array $sortableOffer): array => $sortableOffer[1], $sortableOffers);
    }

    /**
     * @param list<array<string, mixed>> $offers
     */
    protected function findLowestOfferMerchantName(array $offers): ?string
    {
        foreach ($offers as $offer) {
            if (isset($offer[static::KEY_PRICE_FORMATTED]) && ($offer[static::KEY_IS_AVAILABLE] ?? true) === true) {
                return $offer[static::KEY_MERCHANT_NAME];
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    protected function extractProductConcreteIdsBySku(
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): array {
        $productAbstractStorageData = $storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData();
        $productConcreteIds = $productAbstractStorageData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($productConcreteIds)) {
            return [];
        }

        $productConcreteIdsBySku = [];

        foreach ($productConcreteIds as $productConcreteSku => $idProductConcrete) {
            $productConcreteSku = trim((string)$productConcreteSku);

            if ($productConcreteSku !== '' && (int)$idProductConcrete > 0) {
                $productConcreteIdsBySku[$productConcreteSku] = (int)$idProductConcrete;
            }
        }

        return array_slice($productConcreteIdsBySku, 0, ProductAttributeDetailsExpander::MAX_CONCRETES, true);
    }
}
