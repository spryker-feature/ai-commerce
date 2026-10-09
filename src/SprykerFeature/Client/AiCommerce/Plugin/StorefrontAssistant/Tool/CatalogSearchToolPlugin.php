<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Spryker\Client\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Shared\AiFoundation\Tools\ToolParameterType;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchArgumentResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class CatalogSearchToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'catalog_search';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Searches the shop catalog for the current store, locale and currency and returns product rows plus the refinements that narrow this result.
        - `query` is optional: omit it to browse by `category`, by `label` ("on sale" is SALE, "new arrivals" is NEW), by a price bound or by a filter; with no argument it browses the whole catalogue.
        - A seller ("sold by Video King") goes in `merchant`, never in `query`. When a `merchant` search returns nothing, tell the customer no seller of that name offers such products and name at most five sellers from `emptiedBy` instead of showing other sellers' products.
        - Prices are in the store currency's normal units, so 100 euros is 100, for the bounds, `price` and `priceRange` alike. "Under 100" is `priceMax` 100; "between 100 and 200" is `priceMin` 100 and `priceMax` 200; "around 150" is `priceMin` 120 and `priceMax` 180. Apply a budget in the first search, and leave out a row whose own `price` still falls outside it.
        - Every `availableRefinements` value carries a `matchCount`. Choose values from these lists, preferring one well below `totalResults`, and pass them back through the argument named in `filterArgument`; a `valueType` categoryId is filtered by its numeric `value`. Filters combine as AND, several values of one filter as OR.
        - `priceRange` (`min`, `max`, `minFormatted`, `maxFormatted`) spans every match and ignores the search's own price bounds: answer what prices exist or what budget is realistic from it instead of searching again. `ratingRange` spans the rated matches.
        - Rows carry `labels`, `rating` with `reviewCount` only when rated, up to ten `attributes` and `variants`. The `description` is cut short, so judge specifications by `attributes`. Never call a row without `rating` top-rated, and say so when fewer than three matches are rated. A missing attribute is unknown, not absent: compare products by the attributes both rows carry. For a numeric need no refinement covers, such as "at least 20 megapixels", keep only rows whose attribute meets it; for the cheapest, browse with `sort` price_asc and page on.
        - Rows that share a name are different listings: say for each what differs, such as its `variants`, `color` or price.
        - `hasMoreResults` true: narrow with a refinement, or search again with `excludeShown` true when the customer wants more.
        - `ignoredArguments` names each dropped `argument` and its `reason`: correct it instead of repeating the call or presenting unfiltered results as matching.
        - `totalResults` 0: follow `suggestedNextAction`. `emptiedBy` names each wish without a match with its `argument`, `requested` value and the `availableValues` or `availableRange` that exist: tell the customer and offer the closest ones, never silently dropping it. `didYouMean` appears only when the words themselves matched nothing.
        DESCRIPTION;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return static::TOOL_DESCRIPTION;
    }

    /**
     * {@inheritDoc}
     *
     * Every filter here is backed by a facet the project actually wires into catalog search; an
     * argument the shop cannot filter by would only invite the model to narrow into an empty result.
     *
     * @api
     *
     * @return array<\Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_QUERY,
                ToolParameterType::String,
                'Optional. The product itself in two to four words, such as "camera", or a specific model\'s brand and model, such as "Samsung Galaxy S5 mini". Never the customer\'s sentence or intent words. Omit to browse.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_CATEGORY,
                ToolParameterType::Integer,
                'A numeric category id, never a name: from the shop\'s category list, `category_tree` or the `value` of a `category` refinement.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PRICE_MIN,
                ToolParameterType::Number,
                'Lowest price in the store currency\'s normal units, so 24.99 euros is 24.99. Omit for "under X".',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PRICE_MAX,
                ToolParameterType::Number,
                'Highest price in the store currency\'s normal units, so "under 50 euros" is 50.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_RATING_MIN,
                ToolParameterType::Integer,
                sprintf(
                    'Lowest average rating on a %d to %d star scale; 4 means "%d stars and up".',
                    CatalogSearchArgumentResolver::RATING_MIN,
                    CatalogSearchArgumentResolver::RATING_MAX,
                    CatalogSearchArgumentResolver::RATING_MAX - 1,
                ),
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_LABEL,
                ToolParameterType::Array,
                'Label keys such as SALE or NEW, or `label` refinement values; several match any.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PRODUCT_CLASS,
                ToolParameterType::Array,
                'Values of the `product-class-names` refinement; several match any.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_MERCHANT,
                ToolParameterType::Array,
                'Seller names as the `merchant` refinement lists them, such as "Video King"; several match any.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_SORT,
                ToolParameterType::String,
                sprintf(
                    'One of: %s. Omit for relevance. Never sort a lookup of a specific model.',
                    implode(', ', CatalogSearchArgumentResolver::SUPPORTED_SORT_PARAMS),
                ),
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PAGE,
                ToolParameterType::Integer,
                'The 1-based page. For more of a search already shown, pass `excludeShown` instead.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_EXCLUDE_SHOWN,
                ToolParameterType::Boolean,
                'True leaves out the products displayed earlier in this conversation, for "show me more". Keep the other arguments of the search being continued.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_LIMIT,
                ToolParameterType::Integer,
                sprintf(
                    'How many products to return, between %d and %d. Omit for the default of %d; raise it only when the customer asked to compare several products in one go, because a longer list crowds out the rest of the conversation.',
                    CatalogSearchArgumentResolver::LIMIT_MIN,
                    CatalogSearchArgumentResolver::LIMIT_MAX,
                    CatalogSearchArgumentResolver::DEFAULT_LIMIT,
                ),
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_FILTERS,
                ToolParameterType::Object,
                'Attribute filters keyed by a refinement\'s `filterArgument`, such as {"brand": "Canon", "color": "Black"}; a multi-value filter accepts an array. Pass a value the customer named in the first call under its usual key.',
                false,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param mixed ...$arguments
     *
     * @return array<string, mixed>
     */
    public function execute(...$arguments): mixed
    {
        return $this->getFactory()->createCatalogSearchReader()->searchProducts($arguments);
    }
}
