<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation;

use Generated\Shared\Transfer\ProductCategoryStorageTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductCategoryStorage\ProductCategoryStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchArgumentResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class ProductRelationReader implements ProductRelationReaderInterface
{
    public const string PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    public const string PARAMETER_TYPE = 'type';

    public const string TYPE_SIMILAR = 'similar';

    public const string TYPE_ACCESSORIES = 'accessories';

    public const string TYPE_ALTERNATIVES = 'alternatives';

    /**
     * @var list<string>
     */
    public const array TYPES = [
        self::TYPE_SIMILAR,
        self::TYPE_ACCESSORIES,
        self::TYPE_ALTERNATIVES,
    ];

    public const string SOURCE_CURATED = 'curated';

    public const string SOURCE_CATEGORY_FALLBACK = 'category_fallback';

    public const string SOURCE_NONE = 'none';

    public const int MAX_PRODUCTS = 10;

    public const int CATEGORY_FALLBACK_LIMIT = 6;

    public const string RESULT_KEY_ERROR = 'error';

    public const string RESULT_KEY_RELATION_TYPE = 'relationType';

    public const string RESULT_KEY_SOURCE = 'source';

    public const string RESULT_KEY_PRODUCTS = 'products';

    public const string RESULT_KEY_TOTAL_COUNT = 'totalCount';

    public const string RESULT_KEY_CATEGORY = 'category';

    public const string RESULT_KEY_CATEGORY_NODE_ID = 'categoryNodeId';

    public const string RESULT_KEY_CATEGORY_NAME = 'name';

    public const string RESULT_KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string SEARCH_RESULT_KEY_PRODUCTS = 'products';

    /**
     * @var list<string>
     */
    protected const array EXCLUDED_PRODUCT_KEYS = [
        StorefrontAssistantProductMapper::KEY_SKU,
        StorefrontAssistantProductMapper::KEY_DESCRIPTION,
        StorefrontAssistantProductMapper::KEY_PRICE,
        StorefrontAssistantProductMapper::KEY_ORIGINAL_PRICE,
    ];

    protected const string ERROR_MISSING_ID_PRODUCT_ABSTRACT = 'Pass the idProductAbstract of the product.';

    protected const string ERROR_INVALID_TYPE = 'Unknown type "%s". Pass one of: %s.';

    protected const string ERROR_UNKNOWN_ID_PRODUCT_ABSTRACT = 'No product found for idProductAbstract %d.';

    protected const string NEXT_ACTION_NO_ACCESSORIES = 'The shop lists no accessories for this product. Tell the customer so, and offer to search the catalog for a kind of accessory they name, such as a bag or a memory card.';

    protected const string NEXT_ACTION_NO_RELATIONS = 'The shop lists no related products for this product and its category has no other products. Tell the customer so and offer to search the catalog instead.';

    protected const string TYPE_SEPARATOR = ', ';

    public function __construct(
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected ProductRelationIdReaderInterface $productRelationIdReader,
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductCategoryStorageClientInterface $productCategoryStorageClient,
        protected ToolResultProductExpanderInterface $toolResultProductExpander,
        protected CatalogSearchReaderInterface $catalogSearchReader,
        protected StoreClientInterface $storeClient,
        protected LocaleClientInterface $localeClient,
        protected RelatedProductCategoryFilterInterface $relatedProductCategoryFilter
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function getProductRelations(array $arguments): array
    {
        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $idProductAbstract = $this->extractIdProductAbstract($arguments);

        if ($idProductAbstract === null) {
            return [static::RESULT_KEY_ERROR => static::ERROR_MISSING_ID_PRODUCT_ABSTRACT];
        }

        $relationType = $this->extractRelationType($arguments);

        if (!in_array($relationType, static::TYPES, true)) {
            return [static::RESULT_KEY_ERROR => sprintf(static::ERROR_INVALID_TYPE, $relationType, implode(static::TYPE_SEPARATOR, static::TYPES))];
        }

        $storeName = (string)$this->storeClient->getCurrentStore()->getName();
        $localeName = $this->localeClient->getCurrentLocale();
        $productAbstractStorageData = $this->productStorageClient->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
            [$idProductAbstract],
            $localeName,
            $storeName,
        )[$idProductAbstract] ?? null;

        if (!is_array($productAbstractStorageData) || $productAbstractStorageData === []) {
            return [static::RESULT_KEY_ERROR => sprintf(static::ERROR_UNKNOWN_ID_PRODUCT_ABSTRACT, $idProductAbstract)];
        }

        $relatedProductAbstractIds = array_values(array_filter(
            $this->productRelationIdReader->getRelatedProductAbstractIds($relationType, $idProductAbstract, $productAbstractStorageData, $storeName, $localeName),
            static fn (int $relatedIdProductAbstract): bool => $relatedIdProductAbstract !== $idProductAbstract,
        ));

        if ($relationType === static::TYPE_SIMILAR) {
            $relatedProductAbstractIds = $this->rankSameCategoryFirst($idProductAbstract, $relatedProductAbstractIds, $storeName, $localeName);
        }

        $products = $this->getResolvedProducts(array_slice($relatedProductAbstractIds, 0, static::MAX_PRODUCTS));

        if ($products !== []) {
            return $this->buildResult($relationType, static::SOURCE_CURATED, $products, count($relatedProductAbstractIds));
        }

        if ($relationType === static::TYPE_ACCESSORIES) {
            return $this->buildResult($relationType, static::SOURCE_NONE, [], 0) + [static::RESULT_KEY_SUGGESTED_NEXT_ACTION => static::NEXT_ACTION_NO_ACCESSORIES];
        }

        return $this->searchProductCategory($relationType, $idProductAbstract, $storeName, $localeName);
    }

    /**
     * @param list<int> $relatedProductAbstractIds
     *
     * @return list<int>
     */
    protected function rankSameCategoryFirst(int $idProductAbstract, array $relatedProductAbstractIds, string $storeName, string $localeName): array
    {
        $sameCategoryProductAbstractIds = $this->relatedProductCategoryFilter->filterProductAbstractIdsSharingCategory(
            $idProductAbstract,
            $relatedProductAbstractIds,
            $storeName,
            $localeName,
        );

        if (count($sameCategoryProductAbstractIds) >= static::CATEGORY_FALLBACK_LIMIT) {
            return $sameCategoryProductAbstractIds;
        }

        return array_values(array_unique([...$sameCategoryProductAbstractIds, ...$relatedProductAbstractIds]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function searchProductCategory(string $relationType, int $idProductAbstract, string $storeName, string $localeName): array
    {
        $productCategoryStorageTransfer = $this->findProductCategory($idProductAbstract, $storeName, $localeName);

        if ($productCategoryStorageTransfer === null) {
            return $this->buildResult($relationType, static::SOURCE_NONE, [], 0) + [static::RESULT_KEY_SUGGESTED_NEXT_ACTION => static::NEXT_ACTION_NO_RELATIONS];
        }

        $searchResult = $this->catalogSearchReader->searchProducts([
            CatalogSearchArgumentResolver::PARAMETER_CATEGORY => (string)$productCategoryStorageTransfer->getCategoryNodeIdOrFail(),
            CatalogSearchArgumentResolver::PARAMETER_LIMIT => static::CATEGORY_FALLBACK_LIMIT + 1,
        ]);
        $products = $this->rejectProduct($searchResult[static::SEARCH_RESULT_KEY_PRODUCTS] ?? [], $idProductAbstract);
        $products = array_slice($products, 0, static::CATEGORY_FALLBACK_LIMIT);

        if ($products === []) {
            return $this->buildResult($relationType, static::SOURCE_NONE, [], 0) + [static::RESULT_KEY_SUGGESTED_NEXT_ACTION => static::NEXT_ACTION_NO_RELATIONS];
        }

        return $this->buildResult($relationType, static::SOURCE_CATEGORY_FALLBACK, $products, count($products)) + [
            static::RESULT_KEY_CATEGORY => [
                static::RESULT_KEY_CATEGORY_NODE_ID => $productCategoryStorageTransfer->getCategoryNodeIdOrFail(),
                static::RESULT_KEY_CATEGORY_NAME => (string)$productCategoryStorageTransfer->getName(),
            ],
        ];
    }

    protected function findProductCategory(int $idProductAbstract, string $storeName, string $localeName): ?ProductCategoryStorageTransfer
    {
        $productAbstractCategoryStorageTransfers = $this->productCategoryStorageClient->findBulkProductAbstractCategory([$idProductAbstract], $localeName, $storeName);

        foreach ($productAbstractCategoryStorageTransfers as $productAbstractCategoryStorageTransfer) {
            if ($productAbstractCategoryStorageTransfer->getIdProductAbstract() !== $idProductAbstract) {
                continue;
            }

            foreach ($productAbstractCategoryStorageTransfer->getCategories() as $productCategoryStorageTransfer) {
                if ($productCategoryStorageTransfer->getCategoryNodeId() !== null) {
                    return $productCategoryStorageTransfer;
                }
            }
        }

        return null;
    }

    /**
     * @param list<int> $productAbstractIds
     *
     * @return list<array<string, mixed>>
     */
    protected function getResolvedProducts(array $productAbstractIds): array
    {
        if ($productAbstractIds === []) {
            return [];
        }

        $products = $this->toolResultProductExpander->expandProductsForAgent(array_map(
            static fn (int $idProductAbstract): array => [StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
            $productAbstractIds,
        ));

        return array_values(array_filter(
            $products,
            static fn (array $product): bool => trim((string)($product[StorefrontAssistantProductMapper::KEY_NAME] ?? '')) !== '',
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function rejectProduct(mixed $products, int $idProductAbstract): array
    {
        if (!is_array($products)) {
            return [];
        }

        return array_values(array_filter(
            $products,
            static fn (mixed $product): bool => is_array($product)
                && (int)($product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? 0) !== $idProductAbstract,
        ));
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    protected function buildResult(string $relationType, string $source, array $products, int $totalCount): array
    {
        return [
            static::RESULT_KEY_RELATION_TYPE => $relationType,
            static::RESULT_KEY_SOURCE => $source,
            static::RESULT_KEY_PRODUCTS => array_map(
                static fn (array $product): array => array_diff_key($product, array_flip(static::EXCLUDED_PRODUCT_KEYS)),
                $products,
            ),
            static::RESULT_KEY_TOTAL_COUNT => $totalCount,
        ];
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
    protected function extractRelationType(array $arguments): string
    {
        $relationType = $arguments[static::PARAMETER_TYPE] ?? null;

        if (!is_scalar($relationType) || is_bool($relationType)) {
            return '';
        }

        return strtolower(trim((string)$relationType));
    }
}
