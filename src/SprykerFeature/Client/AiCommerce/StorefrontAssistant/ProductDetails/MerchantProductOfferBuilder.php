<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\MerchantStorageCriteriaTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;

class MerchantProductOfferBuilder implements MerchantProductOfferBuilderInterface
{
    protected const string STORAGE_KEY_MERCHANT_REFERENCE = 'merchant_reference';

    protected const int DEFAULT_QUANTITY = 1;

    public function __construct(
        protected PriceProductStorageClientInterface $priceProductStorageClient,
        protected MerchantStorageClientInterface $merchantStorageClient,
        protected StorefrontAssistantPriceFormatterInterface $storefrontAssistantPriceFormatter
    ) {
    }

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
            ProductOfferDetailsExpander::KEY_MERCHANT_NAME => (string)$merchantStorageTransfer->getName(),
            ProductOfferDetailsExpander::KEY_MERCHANT_REFERENCE => $merchantReference,
            ProductOfferDetailsExpander::KEY_PRODUCT_CONCRETE_SKU => $productConcreteSku,
        ];
        $price = $canSeePrice
            ? $this->resolveMerchantProductPrice(
                $storefrontAssistantProductDetailsRequestTransfer->getIdProductAbstractOrFail(),
                $productConcreteIdsBySku[$productConcreteSku],
            )
            : null;

        if ($price !== null) {
            $offer[ProductOfferDetailsExpander::KEY_PRICE_FORMATTED] = $this->storefrontAssistantPriceFormatter->formatAmount($price);
        }

        $isAvailable = $this->resolveMerchantProductAvailability($productDetails, $productConcreteSku, count($productConcreteIdsBySku));

        if ($isAvailable !== null) {
            $offer[ProductOfferDetailsExpander::KEY_IS_AVAILABLE] = $isAvailable;
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
}
