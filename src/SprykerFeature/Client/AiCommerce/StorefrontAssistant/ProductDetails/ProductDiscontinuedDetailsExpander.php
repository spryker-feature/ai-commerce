<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails;

use Generated\Shared\Transfer\ConcreteAlternativeProductConditionsTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantProductDetailsRequestTransfer;
use Spryker\Client\ProductAlternativeStorage\ProductAlternativeStorageClientInterface;
use Spryker\Client\ProductDiscontinuedStorage\ProductDiscontinuedStorageClientInterface;

class ProductDiscontinuedDetailsExpander implements ProductDetailsExpanderInterface
{
    public const string KEY_DISCONTINUED = 'discontinued';

    public const string KEY_IS_DISCONTINUED = 'isDiscontinued';

    public const string KEY_NOTE = 'note';

    public const string KEY_DISCONTINUED_VARIANT_SKUS = 'discontinuedVariantSkus';

    public const string KEY_HAS_ALTERNATIVES = 'hasAlternatives';

    public const string KEY_CONCRETE_DISCONTINUED_NOTE = 'discontinuedNote';

    public const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string SUGGESTED_NEXT_ACTION_OFFER_ALTERNATIVES = 'Say it is discontinued and offer its alternatives (product_relations type alternatives).';

    protected const string STORAGE_KEY_ATTRIBUTE_MAP = 'attribute_map';

    protected const string STORAGE_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    public function __construct(
        protected ProductDiscontinuedStorageClientInterface $productDiscontinuedStorageClient,
        protected ProductAlternativeStorageClientInterface $productAlternativeStorageClient
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
        $localeName = $storefrontAssistantProductDetailsRequestTransfer->getLocaleNameOrFail();
        $concreteSkus = $this->extractConcreteSkus($storefrontAssistantProductDetailsRequestTransfer->getProductAbstractStorageData());
        $discontinuedNotesBySku = $this->findDiscontinuedNotesBySku($concreteSkus, $localeName);

        if ($discontinuedNotesBySku === []) {
            return $productDetails;
        }

        $discontinued = $this->buildDiscontinued(
            $discontinuedNotesBySku,
            count($discontinuedNotesBySku) === count($concreteSkus),
            $localeName,
        );
        $productDetails[static::KEY_DISCONTINUED] = $discontinued;

        if ($discontinued[static::KEY_IS_DISCONTINUED] === true && $discontinued[static::KEY_HAS_ALTERNATIVES] === true) {
            $productDetails[static::KEY_SUGGESTED_NEXT_ACTION] = static::SUGGESTED_NEXT_ACTION_OFFER_ALTERNATIVES;
        }

        return $this->mergeConcreteDiscontinued($productDetails, $discontinuedNotesBySku);
    }

    /**
     * @param array<string, string> $discontinuedNotesBySku
     *
     * @return array<string, mixed>
     */
    protected function buildDiscontinued(array $discontinuedNotesBySku, bool $isDiscontinued, string $localeName): array
    {
        $discontinued = [static::KEY_IS_DISCONTINUED => $isDiscontinued];

        if (!$isDiscontinued) {
            $discontinued[static::KEY_DISCONTINUED_VARIANT_SKUS] = array_map('strval', array_keys($discontinuedNotesBySku));
        }

        $notes = array_values(array_unique(array_filter($discontinuedNotesBySku, static fn (string $note): bool => $note !== '')));

        if ($notes !== []) {
            $discontinued[static::KEY_NOTE] = implode(' ', $notes);
        }

        $discontinued[static::KEY_HAS_ALTERNATIVES] = $this->hasAlternatives(array_map('strval', array_keys($discontinuedNotesBySku)), $localeName);

        return $discontinued;
    }

    /**
     * @param array<string, mixed> $productDetails
     * @param array<string, string> $discontinuedNotesBySku
     *
     * @return array<string, mixed>
     */
    protected function mergeConcreteDiscontinued(array $productDetails, array $discontinuedNotesBySku): array
    {
        $concretes = $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_CONCRETES] ?? null;

        if (!is_array($concretes)) {
            return $productDetails;
        }

        foreach ($concretes as $index => $concrete) {
            $concreteSku = $concrete[ProductAttributeDetailsExpander::KEY_CONCRETE_SKU] ?? null;

            if (!is_string($concreteSku) || !array_key_exists($concreteSku, $discontinuedNotesBySku)) {
                continue;
            }

            $concretes[$index][static::KEY_IS_DISCONTINUED] = true;

            if ($discontinuedNotesBySku[$concreteSku] !== '') {
                $concretes[$index][static::KEY_CONCRETE_DISCONTINUED_NOTE] = $discontinuedNotesBySku[$concreteSku];
            }
        }

        $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_CONCRETES] = $concretes;

        return $productDetails;
    }

    /**
     * @param list<string> $concreteSkus
     *
     * @return array<string, string>
     */
    protected function findDiscontinuedNotesBySku(array $concreteSkus, string $localeName): array
    {
        $discontinuedNotesBySku = [];

        foreach ($concreteSkus as $concreteSku) {
            $productDiscontinuedStorageTransfer = $this->productDiscontinuedStorageClient->findProductDiscontinuedStorage($concreteSku, $localeName);

            if ($productDiscontinuedStorageTransfer !== null) {
                $discontinuedNotesBySku[$concreteSku] = trim((string)$productDiscontinuedStorageTransfer->getNote());
            }
        }

        return $discontinuedNotesBySku;
    }

    /**
     * @param list<string> $discontinuedConcreteSkus
     */
    protected function hasAlternatives(array $discontinuedConcreteSkus, string $localeName): bool
    {
        $concreteAlternativeProductCollectionTransfer = $this->productAlternativeStorageClient->getConcreteAlternativeProductCollection(
            (new ConcreteAlternativeProductCriteriaTransfer())->setConcreteAlternativeProductConditions(
                (new ConcreteAlternativeProductConditionsTransfer())
                    ->setSkus($discontinuedConcreteSkus)
                    ->setLocaleName($localeName),
            ),
        );

        foreach ($concreteAlternativeProductCollectionTransfer->getConcreteAlternativeProducts() as $concreteAlternativeProductTransfer) {
            if ($concreteAlternativeProductTransfer->getAlternativeProducts()->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $productAbstractStorageData
     *
     * @return list<string>
     */
    protected function extractConcreteSkus(array $productAbstractStorageData): array
    {
        $productConcreteIds = $productAbstractStorageData[static::STORAGE_KEY_ATTRIBUTE_MAP][static::STORAGE_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($productConcreteIds)) {
            return [];
        }

        $concreteSkus = [];

        foreach (array_keys($productConcreteIds) as $concreteSku) {
            $concreteSku = trim((string)$concreteSku);

            if ($concreteSku !== '') {
                $concreteSkus[] = $concreteSku;
            }
        }

        return array_values(array_unique($concreteSkus));
    }
}
