<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ComparisonRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ShownProductRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultRegistry;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class DisplayProductReader implements DisplayProductReaderInterface
{
    public const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    public const string PARAMETER_REASONS = 'reasons';

    public const int MAX_PRODUCTS = 5;

    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const string RESULT_KEY_DISPLAYED_COUNT = 'displayedCount';

    protected const string RESULT_KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS = 'unknownIdProductAbstracts';

    protected const string RESULT_KEY_ALREADY_SHOWN = 'alreadyShown';

    protected const string MESSAGE_COMPARISON_SHOWN = 'A comparison table already shows the products in this reply: answer now without cards.';

    /**
     * @var list<string>
     */
    protected const array AGENT_RESULT_PRODUCT_KEYS = [
        StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT,
        StorefrontAssistantProductMapper::KEY_NAME,
        StorefrontAssistantProductMapper::KEY_REASON,
        StorefrontAssistantProductMapper::KEY_IS_PRICE_OUTLIER,
    ];

    public function __construct(
        protected ToolResultProductExpanderInterface $toolResultProductExpander,
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected ShownProductRegistryInterface $shownProductRegistry,
        protected DisplayProductReasonResolverInterface $displayProductReasonResolver,
        protected PriceOutlierMarkerInterface $priceOutlierMarker,
        protected CatalogSearchResultRegistryInterface $catalogSearchResultRegistry,
        protected ComparisonRegistryInterface $comparisonRegistry
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function getDisplayProducts(array $arguments): array
    {
        if ($this->comparisonRegistry->hasAnyComparison()) {
            return [
                static::RESULT_KEY_PRODUCTS => [],
                static::RESULT_KEY_DISPLAYED_COUNT => 0,
                static::RESULT_KEY_ALREADY_SHOWN => static::MESSAGE_COMPARISON_SHOWN,
            ];
        }

        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $productAbstractIds = $this->resolveProductAbstractIds($arguments);

        if ($productAbstractIds === []) {
            return [
                static::RESULT_KEY_PRODUCTS => [],
                static::RESULT_KEY_DISPLAYED_COUNT => 0,
            ];
        }

        $products = $this->markPriceOutliers($this->rejectUnresolvedProducts(
            $this->toolResultProductExpander->expandProducts(
                array_map(
                    static fn (int $idProductAbstract): array => [StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
                    $productAbstractIds,
                ),
            ),
        ));

        $reasons = $arguments[static::PARAMETER_REASONS] ?? null;

        return $this->buildResult(
            $this->displayProductReasonResolver->resolveReasons($products, is_array($reasons) ? $reasons : []),
            $productAbstractIds,
        );
    }

    /**
     * The identifiers the agent could not have shown are reported back, so a stale or invented
     * identifier is corrected on the next turn instead of silently shrinking the card list.
     *
     * @param array<int, array<string, mixed>> $products
     * @param array<int, int> $productAbstractIds
     *
     * @return array<string, mixed>
     */
    protected function buildResult(array $products, array $productAbstractIds): array
    {
        $result = [
            static::RESULT_KEY_PRODUCTS => array_map(
                static fn (array $product): array => array_intersect_key($product, array_flip(static::AGENT_RESULT_PRODUCT_KEYS)),
                $products,
            ),
            static::RESULT_KEY_DISPLAYED_COUNT => count($products),
        ];

        $displayedProductAbstractIds = array_column($products, StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT);
        $this->shownProductRegistry->addShownProductAbstractIds($displayedProductAbstractIds);
        $unknownProductAbstractIds = array_values(array_diff($productAbstractIds, $displayedProductAbstractIds));

        if ($unknownProductAbstractIds !== []) {
            $result[static::RESULT_KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS] = $unknownProductAbstractIds;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    protected function markPriceOutliers(array $products): array
    {
        $products = $this->priceOutlierMarker->markPriceOutliers($products);
        $searchedPriceOutlierProductAbstractIds = $this->collectSearchedPriceOutlierProductAbstractIds();

        foreach ($products as $index => $product) {
            if (in_array($product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? null, $searchedPriceOutlierProductAbstractIds, true)) {
                $products[$index][StorefrontAssistantProductMapper::KEY_IS_PRICE_OUTLIER] = true;
            }
        }

        return $products;
    }

    /**
     * @return list<int>
     */
    protected function collectSearchedPriceOutlierProductAbstractIds(): array
    {
        $productAbstractIds = [];

        foreach ($this->catalogSearchResultRegistry->getSearchResults() as $searchResult) {
            $searchedProducts = $searchResult[CatalogSearchResultRegistry::ENTRY_KEY_RESULT][static::RESULT_KEY_PRODUCTS] ?? [];
            $productAbstractIds = array_merge($productAbstractIds, $this->extractPriceOutlierProductAbstractIds($searchedProducts));
        }

        return $productAbstractIds;
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return list<int>
     */
    protected function extractPriceOutlierProductAbstractIds(array $products): array
    {
        return array_values(array_map(
            static fn (array $product): int => (int)$product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT],
            array_filter(
                $products,
                static fn (array $product): bool => ($product[StorefrontAssistantProductMapper::KEY_IS_PRICE_OUTLIER] ?? null) === true
                    && isset($product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT]),
            ),
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rejectUnresolvedProducts(array $products): array
    {
        return array_values(
            array_filter(
                $products,
                static fn (array $product): bool => trim((string)($product[StorefrontAssistantProductMapper::KEY_NAME] ?? '')) !== '',
            ),
        );
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<int, int>
     */
    protected function resolveProductAbstractIds(array $arguments): array
    {
        $value = $arguments[static::PARAMETER_ID_PRODUCT_ABSTRACTS] ?? null;

        if (!is_array($value)) {
            return [];
        }

        $productAbstractIds = [];

        foreach ($value as $idProductAbstract) {
            if (is_bool($idProductAbstract) || !is_numeric($idProductAbstract)) {
                continue;
            }

            $productAbstractIds[] = (int)$idProductAbstract;
        }

        $productAbstractIds = array_values(array_unique(array_filter(
            $productAbstractIds,
            static fn (int $idProductAbstract): bool => $idProductAbstract > 0,
        )));

        return array_slice($productAbstractIds, 0, static::MAX_PRODUCTS);
    }
}
