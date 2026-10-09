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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\ProductRelationReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class ProductRelationsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'product_relations';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Reads the products the shop recommends for one product, as rows with idProductAbstract, name and price; the customer sees them only through `display_products`. `type`: `similar` for something like it, `accessories` for what goes with it (its up-sells), `alternatives` for a replacement of a discontinued or unavailable product.
        - Then call `display_products` once with the rows worth showing, in the given order, and answer; do not search the catalog as well.
        - `source` `curated` means the shop chose them: show them as the shop's recommendations even when they look different from what you would pick, and never call them unsuitable or say there are none. `category_fallback` means the shop lists none, so the rows are other products of its `category`: say so. `none` means nothing to show: follow `suggestedNextAction`.
        - "Cheaper alternatives" is a price question: use `catalog_search` with its category and a `priceMax` below its price.
        - `error` means the request was invalid or the product was not found: correct it as the error says.
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
                ProductRelationReader::PARAMETER_ID_PRODUCT_ABSTRACT,
                ToolParameterType::Integer,
                'From the page context or an earlier tool result.',
                true,
            ),
            new ToolParameter(
                ProductRelationReader::PARAMETER_TYPE,
                ToolParameterType::String,
                sprintf('One of: %s.', implode(', ', ProductRelationReader::TYPES)),
                true,
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
        return $this->getFactory()->createProductRelationReader()->getProductRelations($arguments);
    }
}
