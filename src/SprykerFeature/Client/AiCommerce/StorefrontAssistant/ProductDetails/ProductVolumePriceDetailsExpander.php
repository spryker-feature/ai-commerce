<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Action\ActionItemOfferResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;

class ProductVolumePriceDetailsExpander implements ProductDetailsExpanderInterface
{
    public const string KEY_VOLUME_PRICES = 'volumePrices';

    public const string KEY_HAS_TIERS = 'hasTiers';

    public const string KEY_TIERS = 'tiers';

    public const string KEY_REQUESTED = 'requested';

    public const string KEY_QUANTITY = 'quantity';

    public const string KEY_UNIT_PRICE_FORMATTED = 'unitPriceFormatted';

    public const string KEY_TOTAL_FORMATTED = 'totalFormatted';

    /**
     * @uses \Spryker\Shared\PriceProduct\PriceProductConfig::PRICE_TYPE_DEFAULT
     */
    protected const string PRICE_TYPE_DEFAULT = 'DEFAULT';

    /**
     * @uses \Spryker\Shared\PriceProductVolume\PriceProductVolumeConfig::VOLUME_PRICE_TYPE
     */
    protected const string PRICE_DATA_KEY_VOLUME_PRICES = 'volume_prices';

    /**
     * @uses \Spryker\Shared\PriceProductVolume\PriceProductVolumeConfig::VOLUME_PRICE_QUANTITY
     */
    protected const string PRICE_DATA_KEY_QUANTITY = 'quantity';

    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     * @uses \Spryker\Shared\PriceProductVolume\PriceProductVolumeConfig::VOLUME_PRICE_GROSS_PRICE
     * @uses \Spryker\Shared\PriceProductVolume\PriceProductVolumeConfig::VOLUME_PRICE_NET_PRICE
     *
     * @var array<string, string>
     */
    protected const array PRICE_DATA_KEY_BY_PRICE_MODE = [
        'GROSS_MODE' => 'gross_price',
        'NET_MODE' => 'net_price',
    ];

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const int DEFAULT_QUANTITY = 1;

    public function __construct(
        protected PriceProductStorageClientInterface $priceProductStorageClient,
        protected PermissionClientInterface $permissionClient,
        protected StorefrontAssistantPriceFormatterInterface $storefrontAssistantPriceFormatter,
        protected ActionItemOfferResolverInterface $actionItemOfferResolver
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
        $priceProductFilterTransfer = $this->findPriceProductFilter($storefrontAssistantProductDetailsRequestTransfer);

        if ($priceProductFilterTransfer === null || !$this->permissionClient->can(ProductOfferDetailsExpander::PERMISSION_SEE_PRICE)) {
            return $productDetails;
        }

        [$priceProductFilterTransfer, $currentProductPriceTransfer, $tiers] = $this->resolvePriceBasis($priceProductFilterTransfer, $storefrontAssistantProductDetailsRequestTransfer);
        $quantity = $storefrontAssistantProductDetailsRequestTransfer->getQuantity();
        $hasRequestedQuantity = $quantity !== null && $quantity > static::DEFAULT_QUANTITY;

        if ($tiers === [] && !$hasRequestedQuantity) {
            return $productDetails;
        }

        $volumePrices = [static::KEY_HAS_TIERS => $tiers !== []];

        if ($tiers !== []) {
            $volumePrices[static::KEY_TIERS] = $tiers;
        }

        $unitPrice = $hasRequestedQuantity ? $this->resolveUnitPrice($priceProductFilterTransfer, $currentProductPriceTransfer, (int)$quantity, $tiers !== []) : null;

        if ($unitPrice !== null) {
            $volumePrices[static::KEY_REQUESTED] = [
                static::KEY_QUANTITY => $quantity,
                static::KEY_UNIT_PRICE_FORMATTED => $this->storefrontAssistantPriceFormatter->formatAmount($unitPrice),
                static::KEY_TOTAL_FORMATTED => $this->storefrontAssistantPriceFormatter->formatAmount($unitPrice * (int)$quantity),
            ];
        }

        $productDetails[static::KEY_VOLUME_PRICES] = $volumePrices;

        return $productDetails;
    }

    /**
     * @return array{0: \Generated\Shared\Transfer\PriceProductFilterTransfer, 1: \Generated\Shared\Transfer\CurrentProductPriceTransfer, 2: list<array<string, mixed>>}
     */
    protected function resolvePriceBasis(
        PriceProductFilterTransfer $priceProductFilterTransfer,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): array {
        $currentProductPriceTransfer = $this->resolveCurrentProductPrice($priceProductFilterTransfer);
        $tiers = $this->extractTiers($currentProductPriceTransfer);
        $defaultOfferPriceProductFilterTransfer = $this->findDefaultOfferPriceProductFilter($priceProductFilterTransfer, $storefrontAssistantProductDetailsRequestTransfer);

        if ($defaultOfferPriceProductFilterTransfer === null) {
            return [$priceProductFilterTransfer, $currentProductPriceTransfer, $tiers];
        }

        $defaultOfferCurrentProductPriceTransfer = $this->resolveCurrentProductPrice($defaultOfferPriceProductFilterTransfer);
        $defaultOfferTiers = $this->extractTiers($defaultOfferCurrentProductPriceTransfer);

        if ($defaultOfferTiers === [] && $tiers !== []) {
            return [$priceProductFilterTransfer, $currentProductPriceTransfer, $tiers];
        }

        return [$defaultOfferPriceProductFilterTransfer, $defaultOfferCurrentProductPriceTransfer, $defaultOfferTiers];
    }

    protected function resolveCurrentProductPrice(PriceProductFilterTransfer $priceProductFilterTransfer): CurrentProductPriceTransfer
    {
        return $this->priceProductStorageClient->getResolvedCurrentProductPriceTransfer(
            (clone $priceProductFilterTransfer)->setQuantity(static::DEFAULT_QUANTITY),
        );
    }

    protected function resolveUnitPrice(
        PriceProductFilterTransfer $priceProductFilterTransfer,
        CurrentProductPriceTransfer $currentProductPriceTransfer,
        int $quantity,
        bool $hasTiers
    ): ?int {
        if (!$hasTiers) {
            return $currentProductPriceTransfer->getPrice();
        }

        return $this->priceProductStorageClient->getResolvedCurrentProductPriceTransfer(
            (clone $priceProductFilterTransfer)->setQuantity($quantity),
        )->getPrice();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function extractTiers(CurrentProductPriceTransfer $currentProductPriceTransfer): array
    {
        $priceData = json_decode((string)($currentProductPriceTransfer->getPriceDataByPriceType()[static::PRICE_TYPE_DEFAULT] ?? null), true);
        $volumePrices = is_array($priceData) ? ($priceData[static::PRICE_DATA_KEY_VOLUME_PRICES] ?? null) : null;
        $priceKey = static::PRICE_DATA_KEY_BY_PRICE_MODE[(string)$currentProductPriceTransfer->getPriceMode()] ?? null;

        if (!is_array($volumePrices) || $priceKey === null) {
            return [];
        }

        $tiers = [];

        foreach ($volumePrices as $volumePrice) {
            if (!is_array($volumePrice) || !is_numeric($volumePrice[static::PRICE_DATA_KEY_QUANTITY] ?? null) || !is_numeric($volumePrice[$priceKey] ?? null)) {
                continue;
            }

            $tiers[(int)$volumePrice[static::PRICE_DATA_KEY_QUANTITY]] = (int)$volumePrice[$priceKey];
        }

        ksort($tiers);

        $formattedTiers = [];

        foreach ($tiers as $tierQuantity => $tierPrice) {
            $formattedTiers[] = [
                static::KEY_QUANTITY => $tierQuantity,
                static::KEY_UNIT_PRICE_FORMATTED => $this->storefrontAssistantPriceFormatter->formatAmount($tierPrice),
            ];
        }

        return $formattedTiers;
    }

    protected function findPriceProductFilter(
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): ?PriceProductFilterTransfer {
        $productConcreteIds = $storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData()[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;
        $idProductConcrete = is_array($productConcreteIds) ? (int)(reset($productConcreteIds) ?: 0) : 0;

        if ($idProductConcrete <= 0) {
            return null;
        }

        return (new PriceProductFilterTransfer())
            ->setIdProductAbstract($storefrontAssistantProductDetailsRequestTransfer->getIdProductAbstractOrFail())
            ->setIdProduct($idProductConcrete);
    }

    protected function findDefaultOfferPriceProductFilter(
        PriceProductFilterTransfer $priceProductFilterTransfer,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): ?PriceProductFilterTransfer {
        $productConcreteIds = $storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData()[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? [];
        $productConcreteSku = (string)array_search($priceProductFilterTransfer->getIdProduct(), is_array($productConcreteIds) ? $productConcreteIds : [], false);
        $productOfferReference = $this->actionItemOfferResolver->findProductOffer('', $productConcreteSku)?->getProductOfferReference();

        if ($productOfferReference === null) {
            return null;
        }

        return (clone $priceProductFilterTransfer)->setProductOfferReference($productOfferReference);
    }
}
