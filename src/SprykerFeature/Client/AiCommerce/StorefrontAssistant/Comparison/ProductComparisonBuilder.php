<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison;

use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductAttributeDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductAvailabilityDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class ProductComparisonBuilder implements ProductComparisonBuilderInterface
{
    public const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    public const int MIN_PRODUCTS = 2;

    public const int MAX_PRODUCTS = 4;

    public const int MAX_ATTRIBUTE_ROWS = 12;

    public const string RESULT_KEY_ERROR = 'error';

    public const string RESULT_KEY_COMPARED_PRODUCTS = 'comparedProducts';

    public const string RESULT_KEY_ATTRIBUTE_ROWS = 'attributeRows';

    public const string RESULT_KEY_COMPARISON_URL = 'comparisonUrl';

    public const string RESULT_KEY_IGNORED_ID_PRODUCT_ABSTRACTS = 'ignoredIdProductAbstracts';

    public const string RESULT_KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS = 'unknownIdProductAbstracts';

    public const string RESULT_KEY_ALREADY_SHOWN = 'alreadyShown';

    public const string KEY_ATTRIBUTE = 'attribute';

    public const string KEY_VALUES = 'values';

    public const string KEY_IS_DIFFERENT = 'isDifferent';

    public const string KEY_IS_AVAILABLE = ProductAvailabilityDetailsExpander::KEY_IS_AVAILABLE;

    protected const string COMPARISON_URL = '/product-comparison?skus=%s';

    protected const string COMPARISON_URL_SKU_SEPARATOR = ',';

    protected const string ATTRIBUTE_GLOSSARY_KEY_PREFIX = 'product.attribute.';

    protected const string SUPER_ATTRIBUTE_VALUE_SEPARATOR = ' / ';

    protected const string ERROR_PRODUCT_COUNT = 'Pass 2 to 4 different idProductAbstract values to compare.';

    protected const string MESSAGE_ALREADY_SHOWN = 'This table is already shown in this reply: answer now without calling compare_products again.';

    protected const string ERROR_TOO_FEW_FOUND = 'Fewer than 2 of these products were found, so there is nothing to compare: check the unknownIdProductAbstracts.';

    /**
     * @var list<string>
     */
    protected const array COMPARED_PRODUCT_KEYS = [
        StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT,
        StorefrontAssistantProductMapper::KEY_NAME,
        StorefrontAssistantProductMapper::KEY_SKU,
        StorefrontAssistantProductMapper::KEY_PRICE_FORMATTED,
        StorefrontAssistantProductMapper::KEY_RATING,
        StorefrontAssistantProductMapper::KEY_REVIEW_COUNT,
    ];

    public function __construct(
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected ProductDetailsReaderInterface $productDetailsReader,
        protected GlossaryStorageClientInterface $glossaryStorageClient,
        protected LocaleClientInterface $localeClient,
        protected ComparisonRegistryInterface $comparisonRegistry
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function compareProducts(array $arguments): array
    {
        $productAbstractIds = $this->extractProductAbstractIds($this->toolArgumentNormalizer->normalizeArguments($arguments));

        if (count($productAbstractIds) < static::MIN_PRODUCTS) {
            return [static::RESULT_KEY_ERROR => static::ERROR_PRODUCT_COUNT];
        }

        if ($this->comparisonRegistry->hasComparison($productAbstractIds)) {
            return [static::RESULT_KEY_ALREADY_SHOWN => static::MESSAGE_ALREADY_SHOWN];
        }

        $productDetailsList = [];
        $unknownProductAbstractIds = [];

        foreach (array_slice($productAbstractIds, 0, static::MAX_PRODUCTS) as $idProductAbstract) {
            $productDetails = $this->productDetailsReader->getProductDetails([
                ProductDetailsReader::PARAMETER_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            ]);

            if (isset($productDetails[ProductDetailsReader::RESULT_KEY_ERROR])) {
                $unknownProductAbstractIds[] = $idProductAbstract;

                continue;
            }

            $productDetailsList[] = $productDetails;
        }

        if (count($productDetailsList) < static::MIN_PRODUCTS) {
            return [
                static::RESULT_KEY_ERROR => static::ERROR_TOO_FEW_FOUND,
                static::RESULT_KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS => $unknownProductAbstractIds,
            ];
        }

        $this->comparisonRegistry->registerComparison($productAbstractIds);

        return $this->buildComparison(
            $productDetailsList,
            array_slice($productAbstractIds, static::MAX_PRODUCTS),
            $unknownProductAbstractIds,
        );
    }

    /**
     * @param list<array<string, mixed>> $productDetailsList
     * @param list<int> $ignoredProductAbstractIds
     * @param list<int> $unknownProductAbstractIds
     *
     * @return array<string, mixed>
     */
    protected function buildComparison(array $productDetailsList, array $ignoredProductAbstractIds, array $unknownProductAbstractIds): array
    {
        $comparison = [
            static::RESULT_KEY_COMPARED_PRODUCTS => array_map($this->buildComparedProduct(...), $productDetailsList),
            static::RESULT_KEY_ATTRIBUTE_ROWS => $this->buildAttributeRows($productDetailsList),
        ];

        $comparisonUrl = $this->buildComparisonUrl($productDetailsList);

        if ($comparisonUrl !== null) {
            $comparison[static::RESULT_KEY_COMPARISON_URL] = $comparisonUrl;
        }

        if ($ignoredProductAbstractIds !== []) {
            $comparison[static::RESULT_KEY_IGNORED_ID_PRODUCT_ABSTRACTS] = $ignoredProductAbstractIds;
        }

        if ($unknownProductAbstractIds !== []) {
            $comparison[static::RESULT_KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS] = $unknownProductAbstractIds;
        }

        return $comparison;
    }

    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, mixed>
     */
    protected function buildComparedProduct(array $productDetails): array
    {
        $comparedProduct = array_intersect_key($productDetails, array_flip(static::COMPARED_PRODUCT_KEYS));
        $availability = $productDetails[ProductAvailabilityDetailsExpander::KEY_AVAILABILITY] ?? null;

        if (is_array($availability) && ($availability[ProductAvailabilityDetailsExpander::KEY_IS_KNOWN] ?? false) === true) {
            $comparedProduct[static::KEY_IS_AVAILABLE] = ($availability[ProductAvailabilityDetailsExpander::KEY_IS_AVAILABLE] ?? false) === true;
        }

        return $comparedProduct;
    }

    /**
     * @param list<array<string, mixed>> $productDetailsList
     *
     * @return list<array<string, mixed>>
     */
    protected function buildAttributeRows(array $productDetailsList): array
    {
        $attributesList = array_map($this->collectAttributes(...), $productDetailsList);
        $differentRows = [];
        $sameRows = [];

        foreach ($this->collectAttributeKeys($attributesList) as $attributeKey) {
            $values = array_map(
                static fn (array $attributes): ?string => $attributes[$attributeKey] ?? null,
                $attributesList,
            );
            $isDifferent = count(array_unique(array_map(static fn (?string $value): string => (string)$value, $values))) > 1;

            if ($isDifferent) {
                $differentRows[$attributeKey] = $values;

                continue;
            }

            $sameRows[$attributeKey] = $values;
        }

        $differentRows = array_slice($differentRows, 0, static::MAX_ATTRIBUTE_ROWS, true);
        $sameRows = array_slice($sameRows, 0, static::MAX_ATTRIBUTE_ROWS - count($differentRows), true);

        return $this->mapAttributeRows($differentRows, $sameRows);
    }

    /**
     * @param array<string, list<string|null>> $differentRows
     * @param array<string, list<string|null>> $sameRows
     *
     * @return list<array<string, mixed>>
     */
    protected function mapAttributeRows(array $differentRows, array $sameRows): array
    {
        $attributeNames = $this->translateAttributeNames(array_merge(array_keys($differentRows), array_keys($sameRows)));

        return array_merge(
            $this->mapAttributeRowsWithDifference($differentRows, true, $attributeNames),
            $this->mapAttributeRowsWithDifference($sameRows, false, $attributeNames),
        );
    }

    /**
     * @param array<string, list<string|null>> $rows
     * @param array<string, string> $attributeNames
     *
     * @return list<array<string, mixed>>
     */
    protected function mapAttributeRowsWithDifference(array $rows, bool $isDifferent, array $attributeNames): array
    {
        $attributeRows = [];

        foreach ($rows as $attributeKey => $values) {
            $attributeRows[] = [
                static::KEY_ATTRIBUTE => $attributeNames[$attributeKey],
                static::KEY_VALUES => $values,
                static::KEY_IS_DIFFERENT => $isDifferent,
            ];
        }

        return $attributeRows;
    }

    /**
     * @param list<string> $attributeKeys
     *
     * @return array<string, string>
     */
    protected function translateAttributeNames(array $attributeKeys): array
    {
        if ($attributeKeys === []) {
            return [];
        }

        $glossaryKeys = array_map(static fn (string $attributeKey): string => static::ATTRIBUTE_GLOSSARY_KEY_PREFIX . $attributeKey, $attributeKeys);
        $translations = $this->glossaryStorageClient->translateBulk($glossaryKeys, $this->localeClient->getCurrentLocale());
        $attributeNames = [];

        foreach ($attributeKeys as $index => $attributeKey) {
            $translation = trim((string)($translations[$glossaryKeys[$index]] ?? ''));

            $attributeNames[$attributeKey] = $translation !== '' && $translation !== $glossaryKeys[$index]
                ? $translation
                : ucfirst(str_replace('_', ' ', $attributeKey));
        }

        return $attributeNames;
    }

    /**
     * @param list<array<string, string>> $attributesList
     *
     * @return list<string>
     */
    protected function collectAttributeKeys(array $attributesList): array
    {
        $attributeKeys = [];

        foreach ($attributesList as $attributes) {
            foreach (array_keys($attributes) as $attributeKey) {
                $attributeKeys[$attributeKey] = $attributeKey;
            }
        }

        return array_values($attributeKeys);
    }

    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, string>
     */
    protected function collectAttributes(array $productDetails): array
    {
        $attributes = $productDetails[StorefrontAssistantProductMapper::KEY_ATTRIBUTES] ?? [];
        $attributes = is_array($attributes) ? array_map('strval', $attributes) : [];
        $superAttributes = $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_SUPER_ATTRIBUTES] ?? [];

        if (!is_array($superAttributes)) {
            return $attributes;
        }

        foreach ($superAttributes as $attributeKey => $attributeValues) {
            if (!isset($attributes[$attributeKey]) && is_array($attributeValues) && $attributeValues !== []) {
                $attributes[(string)$attributeKey] = implode(static::SUPER_ATTRIBUTE_VALUE_SEPARATOR, $attributeValues);
            }
        }

        return $attributes;
    }

    /**
     * @param list<array<string, mixed>> $productDetailsList
     */
    protected function buildComparisonUrl(array $productDetailsList): ?string
    {
        $concreteSkus = [];

        foreach ($productDetailsList as $productDetails) {
            $concreteSku = $this->findComparableConcreteSku($productDetails);

            if ($concreteSku !== null) {
                $concreteSkus[] = rawurlencode($concreteSku);
            }
        }

        if (count($concreteSkus) < static::MIN_PRODUCTS) {
            return null;
        }

        return sprintf(static::COMPARISON_URL, implode(static::COMPARISON_URL_SKU_SEPARATOR, $concreteSkus));
    }

    /**
     * @param array<string, mixed> $productDetails
     */
    protected function findComparableConcreteSku(array $productDetails): ?string
    {
        $concretes = $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_CONCRETES] ?? [];
        $concreteSkus = [];

        foreach (is_array($concretes) ? $concretes : [] as $concrete) {
            $concreteSku = is_array($concrete) ? ($concrete[ProductAttributeDetailsExpander::KEY_CONCRETE_SKU] ?? null) : null;

            if (!is_string($concreteSku) || $concreteSku === '') {
                continue;
            }

            if (($concrete[ProductAvailabilityDetailsExpander::KEY_IS_AVAILABLE] ?? null) !== false) {
                return $concreteSku;
            }

            $concreteSkus[] = $concreteSku;
        }

        return $concreteSkus[0] ?? null;
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return list<int>
     */
    protected function extractProductAbstractIds(array $arguments): array
    {
        $value = $arguments[static::PARAMETER_ID_PRODUCT_ABSTRACTS] ?? null;

        if (!is_array($value)) {
            return [];
        }

        $productAbstractIds = [];

        foreach ($value as $idProductAbstract) {
            if (is_bool($idProductAbstract) || !is_numeric($idProductAbstract) || (int)$idProductAbstract <= 0) {
                continue;
            }

            $productAbstractIds[(int)$idProductAbstract] = (int)$idProductAbstract;
        }

        return array_values($productAbstractIds);
    }
}
