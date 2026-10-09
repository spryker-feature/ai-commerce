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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ProductComparisonBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class CompareProductsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'compare_products';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Shows the customer 2 to 4 products side by side as a table (price, rating, stock, attributes; differing ones flagged `isDifferent`) with a link to the shop's full comparison page, to compare products or pick the best of a few.
        - Pass one idProductAbstract per product the customer asks about, one listing per named model, from the page context or earlier tool results. Call it once; the table replaces product cards, so never call `display_products` in the same reply.
        - Never repeat the table: in at most two sentences, name the decisive differences and your pick.
        - Best value is what a product offers for its price, never the lowest price alone: name at least one attribute the pick gives per euro compared with the others.
        - In `values`, null means that product does not state it.
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
                ProductComparisonBuilder::PARAMETER_ID_PRODUCT_ABSTRACTS,
                ToolParameterType::Array,
                sprintf(
                    'The %d to %d idProductAbstract values to compare, in display order.',
                    ProductComparisonBuilder::MIN_PRODUCTS,
                    ProductComparisonBuilder::MAX_PRODUCTS,
                ),
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
        return $this->getFactory()->createProductComparisonBuilder()->compareProducts($arguments);
    }
}
