<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Setup;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ShownProductRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReasonResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchArgumentResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;

class SetupProposalBuilder implements SetupProposalBuilderInterface
{
    public const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    public const string PARAMETER_BUDGET = 'budget';

    public const string PARAMETER_REASONS = 'reasons';

    public const int MIN_ITEMS = 2;

    public const int MAX_ITEMS = 4;

    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const string RESULT_KEY_ITEMS = 'items';

    protected const string RESULT_KEY_TOTAL_FORMATTED = 'totalFormatted';

    protected const string RESULT_KEY_BUDGET_FORMATTED = 'budgetFormatted';

    protected const string RESULT_KEY_ERROR = 'error';

    protected const string ERROR_ITEM_COUNT = 'Nothing was shown: pass %d to %d different idProductAbstract values.';

    protected const string ERROR_BUDGET = 'Nothing was shown: pass the customer\'s budget as a number greater than 0.';

    protected const string ERROR_UNKNOWN_PRODUCTS = 'Nothing was shown: unknown idProductAbstract %s; pass only ids a tool returned.';

    protected const string ERROR_UNPRICED_PRODUCTS = 'Nothing was shown: %s has no price; replace it.';

    protected const string ERROR_OVER_BUDGET = 'Nothing was shown: the total %s is over the %s budget by %s. Replace the priciest item with a cheaper one and call this tool again.';

    /**
     * @var list<string>
     */
    protected const array AGENT_RESULT_PRODUCT_KEYS = [
        StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT,
        StorefrontAssistantProductMapper::KEY_NAME,
        StorefrontAssistantProductMapper::KEY_PRICE_FORMATTED,
        StorefrontAssistantProductMapper::KEY_REASON,
    ];

    public function __construct(
        protected ToolResultProductExpanderInterface $toolResultProductExpander,
        protected ToolArgumentNormalizerInterface $toolArgumentNormalizer,
        protected ShownProductRegistryInterface $shownProductRegistry,
        protected StorefrontAssistantPriceFormatterInterface $storefrontAssistantPriceFormatter,
        protected DisplayProductReasonResolverInterface $displayProductReasonResolver
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function buildSetupProposal(array $arguments): array
    {
        $arguments = $this->toolArgumentNormalizer->normalizeArguments($arguments);
        $productAbstractIds = $this->resolveProductAbstractIds($arguments[static::PARAMETER_ID_PRODUCT_ABSTRACTS] ?? null);

        if (count($productAbstractIds) < static::MIN_ITEMS || count($productAbstractIds) > static::MAX_ITEMS) {
            return [static::RESULT_KEY_ERROR => sprintf(static::ERROR_ITEM_COUNT, static::MIN_ITEMS, static::MAX_ITEMS)];
        }

        $budget = $this->resolveBudget($arguments[static::PARAMETER_BUDGET] ?? null);

        if ($budget === null) {
            return [static::RESULT_KEY_ERROR => static::ERROR_BUDGET];
        }

        $productsByIdProductAbstract = $this->expandProducts($productAbstractIds);
        $unknownProductAbstractIds = array_diff($productAbstractIds, array_keys($productsByIdProductAbstract));

        if ($unknownProductAbstractIds !== []) {
            return [static::RESULT_KEY_ERROR => sprintf(static::ERROR_UNKNOWN_PRODUCTS, implode(', ', $unknownProductAbstractIds))];
        }

        $unpricedProductNames = $this->getUnpricedProductNames($productsByIdProductAbstract);

        if ($unpricedProductNames !== []) {
            return [static::RESULT_KEY_ERROR => sprintf(static::ERROR_UNPRICED_PRODUCTS, implode(', ', $unpricedProductNames))];
        }

        $reasons = $arguments[static::PARAMETER_REASONS] ?? null;

        return $this->buildResult(
            $this->displayProductReasonResolver->resolveReasons(array_values($productsByIdProductAbstract), is_array($reasons) ? $reasons : []),
            $budget,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    protected function buildResult(array $products, int $budget): array
    {
        $total = (int)array_sum(array_column($products, StorefrontAssistantProductMapper::KEY_PRICE));
        $agentProducts = array_map(
            static fn (array $product): array => array_intersect_key($product, array_flip(static::AGENT_RESULT_PRODUCT_KEYS)),
            $products,
        );
        $totalFormatted = $this->storefrontAssistantPriceFormatter->formatAmount($total);
        $budgetFormatted = $this->storefrontAssistantPriceFormatter->formatAmount($budget);

        if ($total > $budget) {
            return [
                static::RESULT_KEY_ERROR => sprintf(
                    static::ERROR_OVER_BUDGET,
                    $totalFormatted,
                    $budgetFormatted,
                    $this->storefrontAssistantPriceFormatter->formatAmount($total - $budget),
                ),
                static::RESULT_KEY_ITEMS => $agentProducts,
            ];
        }

        $this->shownProductRegistry->addShownProductAbstractIds(
            array_column($products, StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT),
        );

        return [
            static::RESULT_KEY_PRODUCTS => $agentProducts,
            static::RESULT_KEY_TOTAL_FORMATTED => $totalFormatted,
            static::RESULT_KEY_BUDGET_FORMATTED => $budgetFormatted,
        ];
    }

    /**
     * @param array<int, int> $productAbstractIds
     *
     * @return array<int, array<string, mixed>>
     */
    protected function expandProducts(array $productAbstractIds): array
    {
        $productsByIdProductAbstract = [];

        $products = $this->toolResultProductExpander->expandProducts(array_map(
            static fn (int $idProductAbstract): array => [StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
            $productAbstractIds,
        ));

        foreach ($products as $product) {
            $idProductAbstract = $product[StorefrontAssistantProductMapper::KEY_ID_PRODUCT_ABSTRACT] ?? null;

            if (is_int($idProductAbstract) && trim((string)($product[StorefrontAssistantProductMapper::KEY_NAME] ?? '')) !== '') {
                $productsByIdProductAbstract[$idProductAbstract] = $product;
            }
        }

        $orderedProductsByIdProductAbstract = [];

        foreach ($productAbstractIds as $idProductAbstract) {
            if (isset($productsByIdProductAbstract[$idProductAbstract])) {
                $orderedProductsByIdProductAbstract[$idProductAbstract] = $productsByIdProductAbstract[$idProductAbstract];
            }
        }

        return $orderedProductsByIdProductAbstract;
    }

    /**
     * @param array<int, array<string, mixed>> $productsByIdProductAbstract
     *
     * @return array<int, string>
     */
    protected function getUnpricedProductNames(array $productsByIdProductAbstract): array
    {
        $unpricedProductNames = [];

        foreach ($productsByIdProductAbstract as $product) {
            $price = $product[StorefrontAssistantProductMapper::KEY_PRICE] ?? null;

            if (!is_int($price) || $price <= 0) {
                $unpricedProductNames[] = (string)$product[StorefrontAssistantProductMapper::KEY_NAME];
            }
        }

        return $unpricedProductNames;
    }

    protected function resolveBudget(mixed $budget): ?int
    {
        if (is_bool($budget) || !is_numeric($budget)) {
            return null;
        }

        $budget = (int)round((float)$budget * CatalogSearchArgumentResolver::PRICE_PRECISION);

        return $budget > 0 ? $budget : null;
    }

    /**
     * @return array<int, int>
     */
    protected function resolveProductAbstractIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $productAbstractIds = [];

        foreach ($value as $idProductAbstract) {
            if (is_bool($idProductAbstract) || !is_numeric($idProductAbstract) || (int)$idProductAbstract <= 0) {
                continue;
            }

            $productAbstractIds[] = (int)$idProductAbstract;
        }

        return array_values(array_unique($productAbstractIds));
    }
}
