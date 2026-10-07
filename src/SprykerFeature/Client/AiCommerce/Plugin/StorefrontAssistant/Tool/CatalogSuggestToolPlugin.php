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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSuggestionReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class CatalogSuggestToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'catalog_suggest';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Looks a term up across everything the shop publishes — products, categories, content pages and product sets — and returns the catalogue's own vocabulary for it.

        Two things it is for:
        - Grounding a term before searching. `matchingTerms` holds the completions the shop's own catalogue contains. If a word the customer used returns no terms, the catalogue does not describe products that way, and searching for it will return nothing useful — search for the product noun instead, or narrow with a filter.
        - Answering what `catalog_search` cannot. `cms_page` results are the shop's own content pages — delivery, returns, warranty, imprint — so a question about shipping or returns is answered from there, with the page `url`, instead of from your own knowledge. `category` and `product_set` results point the customer at a whole section or a curated set rather than at five loose products.

        Use it when a search returned nothing or something irrelevant, when the customer asks about anything other than a specific product, or before searching with a word you are not sure this shop uses. Every result carries the real `name` and `url` — never state a page, category or product this tool did not return.

        A `category` result's `id` is the category entity id, which is NOT what `catalog_search` filters by; take a filterable category id from `category_tree`.
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
     * @api
     *
     * @return array<\Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                CatalogSuggestionReader::PARAMETER_QUERY,
                ToolParameterType::String,
                'The term to look up, in the customer\'s own words or as a single product noun. A short term matches more of the catalogue\'s vocabulary than a sentence does.',
                true,
            ),
            new ToolParameter(
                CatalogSuggestionReader::PARAMETER_LIMIT,
                ToolParameterType::Integer,
                sprintf(
                    'How many results to return per entity type, between %d and %d. Omit for %d.',
                    CatalogSuggestionReader::LIMIT_MIN,
                    CatalogSuggestionReader::LIMIT_MAX,
                    CatalogSuggestionReader::DEFAULT_LIMIT,
                ),
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
        return $this->getFactory()->createCatalogSuggestionReader()->getSuggestions($arguments);
    }
}
