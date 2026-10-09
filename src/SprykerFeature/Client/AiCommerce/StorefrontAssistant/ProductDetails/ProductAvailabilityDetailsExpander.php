<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\ProductAbstractAvailabilityTransfer;
use Generated\Shared\Transfer\ProductConcreteAvailabilityTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\AvailabilityStorage\AvailabilityStorageClientInterface;

class ProductAvailabilityDetailsExpander implements ProductDetailsExpanderInterface
{
    public const string KEY_AVAILABILITY = 'availability';

    public const string KEY_IS_KNOWN = 'isKnown';

    public const string KEY_IS_AVAILABLE = 'isAvailable';

    public const string KEY_AVAILABLE_VARIANT_COUNT = 'availableVariantCount';

    public const string KEY_UNAVAILABLE_VARIANT_COUNT = 'unavailableVariantCount';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    public function __construct(protected AvailabilityStorageClientInterface $availabilityStorageClient)
    {
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
        $productAbstractAvailabilityTransfer = $this->availabilityStorageClient->findProductAbstractAvailability(
            $storefrontAssistantProductDetailsRequestTransfer->getIdProductAbstractOrFail(),
        );
        $isAvailableBySku = $productAbstractAvailabilityTransfer !== null
            ? $this->mapIsAvailableBySku($productAbstractAvailabilityTransfer, $storefrontAssistantProductDetailsRequestTransfer, $productDetails)
            : [];

        if ($isAvailableBySku === []) {
            $productDetails[static::KEY_AVAILABILITY] = [static::KEY_IS_KNOWN => false];

            return $productDetails;
        }

        $productDetails[static::KEY_AVAILABILITY] = $this->buildAvailability($isAvailableBySku);

        return $this->mergeConcreteAvailability($productDetails, $isAvailableBySku);
    }

    /**
     * @param array<string, bool> $isAvailableBySku
     *
     * @return array<string, mixed>
     */
    protected function buildAvailability(array $isAvailableBySku): array
    {
        $availableVariantCount = count(array_filter($isAvailableBySku));
        $availability = [
            static::KEY_IS_KNOWN => true,
            static::KEY_IS_AVAILABLE => $availableVariantCount > 0,
        ];

        if (count($isAvailableBySku) > 1) {
            $availability[static::KEY_AVAILABLE_VARIANT_COUNT] = $availableVariantCount;
            $availability[static::KEY_UNAVAILABLE_VARIANT_COUNT] = count($isAvailableBySku) - $availableVariantCount;
        }

        return $availability;
    }

    /**
     * @param array<string, mixed> $productDetails
     * @param array<string, bool> $isAvailableBySku
     *
     * @return array<string, mixed>
     */
    protected function mergeConcreteAvailability(array $productDetails, array $isAvailableBySku): array
    {
        $concretes = $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_CONCRETES] ?? null;

        if (!is_array($concretes)) {
            return $productDetails;
        }

        foreach ($concretes as $index => $concrete) {
            $concreteSku = $concrete[ProductAttributeDetailsExpander::KEY_CONCRETE_SKU] ?? null;

            if (is_string($concreteSku) && array_key_exists($concreteSku, $isAvailableBySku)) {
                $concretes[$index][static::KEY_IS_AVAILABLE] = $isAvailableBySku[$concreteSku];
            }
        }

        $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_CONCRETES] = $concretes;

        return $productDetails;
    }

    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, bool>
     */
    protected function mapIsAvailableBySku(
        ProductAbstractAvailabilityTransfer $productAbstractAvailabilityTransfer,
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer,
        array $productDetails
    ): array {
        $sellableConcreteSkus = $this->extractSellableConcreteSkus($storefrontAssistantProductDetailsRequestTransfer);
        $isAvailableBySku = [];

        foreach ($productAbstractAvailabilityTransfer->getProductConcreteAvailabilities() as $productConcreteAvailabilityTransfer) {
            $concreteSku = trim((string)$productConcreteAvailabilityTransfer->getSku());

            if ($concreteSku === '' || ($sellableConcreteSkus !== null && !isset($sellableConcreteSkus[$concreteSku]))) {
                continue;
            }

            $isAvailableBySku[$concreteSku] = $this->isConcreteAvailable($productConcreteAvailabilityTransfer, $productDetails, $concreteSku);
        }

        return $isAvailableBySku;
    }

    /**
     * @param array<string, mixed> $productDetails
     */
    protected function isConcreteAvailable(
        ProductConcreteAvailabilityTransfer $productConcreteAvailabilityTransfer,
        array $productDetails,
        string $concreteSku
    ): bool {
        if ($this->isConcreteDiscontinued($productDetails, $concreteSku)) {
            return false;
        }

        if ($productConcreteAvailabilityTransfer->getIsNeverOutOfStock()) {
            return true;
        }

        $availability = $productConcreteAvailabilityTransfer->getAvailability();

        return $availability !== null && $availability->greaterThan(0);
    }

    /**
     * @param array<string, mixed> $productDetails
     */
    protected function isConcreteDiscontinued(array $productDetails, string $concreteSku): bool
    {
        $discontinued = $productDetails[ProductDiscontinuedDetailsExpander::KEY_DISCONTINUED] ?? null;

        if (!is_array($discontinued)) {
            return false;
        }

        if (($discontinued[ProductDiscontinuedDetailsExpander::KEY_IS_DISCONTINUED] ?? false) === true) {
            return true;
        }

        $discontinuedVariantSkus = $discontinued[ProductDiscontinuedDetailsExpander::KEY_DISCONTINUED_VARIANT_SKUS] ?? [];

        return is_array($discontinuedVariantSkus) && in_array($concreteSku, $discontinuedVariantSkus, true);
    }

    /**
     * @return array<string, true>|null
     */
    protected function extractSellableConcreteSkus(
        StorefrontAssistantProductDetailsRequestTransfer $storefrontAssistantProductDetailsRequestTransfer
    ): ?array {
        $productAbstractStorageData = $storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData();
        $productConcreteIds = $productAbstractStorageData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($productConcreteIds) || $productConcreteIds === []) {
            return null;
        }

        $sellableConcreteSkus = [];

        foreach (array_keys($productConcreteIds) as $concreteSku) {
            $sellableConcreteSkus[trim((string)$concreteSku)] = true;
        }

        return $sellableConcreteSkus;
    }
}
