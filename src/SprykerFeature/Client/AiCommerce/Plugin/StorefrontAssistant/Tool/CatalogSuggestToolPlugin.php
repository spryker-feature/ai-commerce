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
        Looks a term up across what the shop publishes: `matchingTerms` (the catalogue's own vocabulary) plus `category`, `cms_page` and `product_set` results, each with its real `name` and `url`. Never state a page, category or product it did not return.
        - A word with no `matchingTerms` is not how the catalogue describes products: search for the product noun or narrow with a filter instead.
        - A `cms_page` (delivery, returns, warranty, imprint) is read with `read_shop_page` by its `id`: answer from that text, never from your own knowledge. A `category` or `product_set` result points the customer at a whole section or a curated set.
        - A `category` result's `id` is NOT what `catalog_search` filters by: take category ids from the shop's category list or `category_tree`.
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
                'The term, in the customer\'s words or as one product noun; a short term matches more than a sentence.',
                true,
            ),
            new ToolParameter(
                CatalogSuggestionReader::PARAMETER_LIMIT,
                ToolParameterType::Integer,
                sprintf(
                    'Results per entity type, %d to %d; default %d.',
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
