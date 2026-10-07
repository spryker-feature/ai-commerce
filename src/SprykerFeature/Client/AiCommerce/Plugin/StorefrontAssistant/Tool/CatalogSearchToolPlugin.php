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
        Searches the shop catalog and returns real products for the current store, locale and currency, together with the refinements that actually narrow this result set.

        How to use it well:
        - `query` is matched as words against the catalogue, not read as a request. Send the product itself in two to four words — "camera", "laptop backpack" — and leave every qualifier out of it. A sentence like "a waterproof camera for hiking" matches more products rather than fewer, because each extra word adds matches of its own, and ranks incidental mentions above the product the customer asked for.
        - Express the qualifiers as arguments instead: a budget through `priceMax`, "cheap" or "best" through `sort`, and any attribute — brand, colour, capacity — through `filters` once a refinement has shown it exists.
        - Search broadly first with `query` alone. The reply carries `availableRefinements`, where every value has a `matchCount`: that is how many of the matching products carry it. Pick refinements from those lists rather than inventing values, and prefer a value whose `matchCount` is well below `totalResults` — one equal to `totalResults` narrows nothing, and a value not in the list returns nothing.
        - Then narrow in a second call by passing the chosen values back through the argument the refinement names in `filterArgument`: `category`, `label`, `productClass`, the price bounds, `ratingMin`, or a `filters` entry such as `filters.brand`. A refinement whose `valueType` is `categoryId` is filtered by the numeric `value`, not by the `label` shown beside it. Filters combine as AND; `label`, `productClass` and a multi-valued `filters` entry take several values, which combine as OR within that filter.
        - `priceRange` in the reply gives the real lowest and highest price among the matches, in the same units `priceMin` and `priceMax` expect, so bound a budget from it instead of guessing.
        - When `hasMoreResults` is true, either narrow with a refinement or ask for the next `page`. When `totalResults` is 0, relax the most restrictive filter rather than repeating the same search, and use `didYouMean` if it is present.
        - `appliedFilters` echoes what was really applied. `ignoredArguments` lists what was dropped, each entry naming the `argument` and the `reason` it could not be used — read the reason and correct that argument rather than repeating the call unchanged.
        - When `totalResults` is 0 and `didYouMean` is present, search again with that spelling before telling the customer nothing was found: it is the catalogue's own correction of the query. With no `didYouMean`, drop the most restrictive filter — or the longest word of the query — and try once more before giving up.
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
                'The product to search for, in two to four words naming the product itself — "camera", "laptop backpack". Not the customer\'s sentence: this text is matched as words against the catalogue, so intent words ("for hiking", "something to", "cheap") add matches of their own and push incidental mentions above the product that was asked for. Express every constraint through the other arguments instead.',
                true,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_CATEGORY,
                ToolParameterType::Integer,
                'Narrows to one category, given as its numeric category id and never as its name. Take the id from the `value` field of the `category` entry in a previous search\'s availableRefinements, where every entry also carries a readable `label` so the right one can be told apart. Omit it on a first search and choose from the refinements that come back.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PRICE_MIN,
                ToolParameterType::Integer,
                'Lowest acceptable price, in the same units as the `priceRange` and the product `price` in the reply, which is the smallest unit of the store currency (cents, for example 2500 for 25.00). Omit when the customer named no lower bound.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PRICE_MAX,
                ToolParameterType::Integer,
                'Highest acceptable price, in the same units as priceMin. For "under 50" in a currency with cents, pass 5000. Omit when the customer named no upper bound.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_RATING_MIN,
                ToolParameterType::Integer,
                sprintf(
                    'Lowest acceptable average customer rating on a %d to %d star scale, so 4 means "%d stars and up". A value outside that scale is ignored rather than applied.',
                    CatalogSearchArgumentResolver::RATING_MIN,
                    CatalogSearchArgumentResolver::RATING_MAX,
                    CatalogSearchArgumentResolver::RATING_MAX - 1,
                ),
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_LABEL,
                ToolParameterType::Array,
                'Product labels to filter by, such as those marking new or discounted products. Pass values from the `label` entry of availableRefinements; several are allowed and match products carrying any of them. A single label may be passed as a plain string.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PRODUCT_CLASS,
                ToolParameterType::Array,
                'Product classes to filter by. Pass values from the `product-class-names` entry of availableRefinements; several are allowed and match products in any of them. A single class may be passed as a plain string.',
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_SORT,
                ToolParameterType::String,
                sprintf(
                    'Result ordering, one of: %s. Omit to let the shop rank by relevance to the query, which is the better choice for an open-ended search.',
                    implode(', ', CatalogSearchArgumentResolver::SUPPORTED_SORT_PARAMS),
                ),
                false,
            ),
            new ToolParameter(
                CatalogSearchArgumentResolver::PARAMETER_PAGE,
                ToolParameterType::Integer,
                'The 1-based page to return, for when the customer asks to see more beyond what a previous call already showed. Narrowing with a refinement usually serves the customer better than paging.',
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
                'Attribute filters, as an object keyed by the `filterArgument` a refinement reports, for example {"brand": "Canon", "color": "Black"}. Which attributes exist is decided by the shop, so take both the key and the value from the `availableRefinements` of a previous search rather than assuming an attribute is filterable. A key the shop does not filter by is reported back in `ignoredArguments` instead of narrowing anything, and a filter whose refinement allows several values accepts an array.',
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
