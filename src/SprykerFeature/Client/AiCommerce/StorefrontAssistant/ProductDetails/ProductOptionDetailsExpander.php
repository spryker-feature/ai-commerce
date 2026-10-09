<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\ProductAbstractOptionStorageTransfer;
use Generated\Shared\Transfer\ProductOptionValueStorageTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\ProductOptionStorage\ProductOptionStorageClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;

class ProductOptionDetailsExpander implements ProductDetailsExpanderInterface
{
    public const string KEY_OPTIONS = 'options';

    public const string KEY_GROUP = 'group';

    public const string KEY_NAME = 'name';

    public const string KEY_SKU = 'sku';

    public const string KEY_PRICE_FORMATTED = 'priceFormatted';

    public function __construct(
        protected ProductOptionStorageClientInterface $productOptionStorageClient,
        protected GlossaryStorageClientInterface $glossaryStorageClient,
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
        $idProductAbstract = $storefrontAssistantProductDetailsRequestTransfer->getIdProductAbstractOrFail();
        $productAbstractOptionStorageTransfer = $this->productOptionStorageClient->getBulkProductOptions([$idProductAbstract])[$idProductAbstract] ?? null;

        if ($productAbstractOptionStorageTransfer === null) {
            return $productDetails;
        }

        $options = $this->buildOptions(
            $productAbstractOptionStorageTransfer,
            $storefrontAssistantProductDetailsRequestTransfer->getLocaleNameOrFail(),
        );

        if ($options !== []) {
            $productDetails[static::KEY_OPTIONS] = $options;
        }

        return $productDetails;
    }

    /**
     * @return list<array<string, string>>
     */
    protected function buildOptions(
        ProductAbstractOptionStorageTransfer $productAbstractOptionStorageTransfer,
        string $localeName
    ): array {
        $translations = $this->translateGlossaryKeys($productAbstractOptionStorageTransfer, $localeName);
        $options = [];

        foreach ($productAbstractOptionStorageTransfer->getProductOptionGroups() as $productOptionGroupStorageTransfer) {
            $groupName = $this->resolveText((string)$productOptionGroupStorageTransfer->getName(), $translations);

            foreach ($productOptionGroupStorageTransfer->getProductOptionValues() as $productOptionValueStorageTransfer) {
                $options[] = $this->buildOption($productOptionValueStorageTransfer, $groupName, $translations);
            }
        }

        return $options;
    }

    /**
     * @param array<string, string> $translations
     *
     * @return array<string, string>
     */
    protected function buildOption(
        ProductOptionValueStorageTransfer $productOptionValueStorageTransfer,
        string $groupName,
        array $translations
    ): array {
        $option = [
            static::KEY_GROUP => $groupName,
            static::KEY_NAME => $this->resolveText((string)$productOptionValueStorageTransfer->getValue(), $translations),
            static::KEY_SKU => (string)$productOptionValueStorageTransfer->getSku(),
        ];
        $price = $productOptionValueStorageTransfer->getPrice();

        if ($price !== null) {
            $option[static::KEY_PRICE_FORMATTED] = $this->storefrontAssistantPriceFormatter->formatAmount($price);
        }

        return $option;
    }

    /**
     * @return array<string, string>
     */
    protected function translateGlossaryKeys(
        ProductAbstractOptionStorageTransfer $productAbstractOptionStorageTransfer,
        string $localeName
    ): array {
        $glossaryKeys = [];

        foreach ($productAbstractOptionStorageTransfer->getProductOptionGroups() as $productOptionGroupStorageTransfer) {
            $glossaryKeys[] = trim((string)$productOptionGroupStorageTransfer->getName());

            foreach ($productOptionGroupStorageTransfer->getProductOptionValues() as $productOptionValueStorageTransfer) {
                $glossaryKeys[] = trim((string)$productOptionValueStorageTransfer->getValue());
            }
        }

        $glossaryKeys = array_values(array_unique(array_filter($glossaryKeys, static fn (string $glossaryKey): bool => $glossaryKey !== '')));

        if ($glossaryKeys === []) {
            return [];
        }

        return $this->glossaryStorageClient->translateBulk($glossaryKeys, $localeName);
    }

    /**
     * @param array<string, string> $translations
     */
    protected function resolveText(string $glossaryKey, array $translations): string
    {
        $glossaryKey = trim($glossaryKey);

        return $translations[$glossaryKey] ?? $glossaryKey;
    }
}
