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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class DisplayProductsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'display_products';

    protected const string TOOL_DESCRIPTION = 'Displays products to the customer as product cards, each showing the name, image, resolved price, SKU and a link. Call this once per reply with only the products worth showing, taken from an earlier catalog_search result. The customer sees nothing from catalog_search itself, so a product you do not pass here is never shown. Pass the idProductAbstract values exactly as the search returned them.';

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
                DisplayProductReader::PARAMETER_ID_PRODUCT_ABSTRACTS,
                ToolParameterType::Array,
                sprintf(
                    'The idProductAbstract values of the products to show the customer, in the order they should appear, taken from a previous catalog_search result. At most %d are displayed.',
                    DisplayProductReader::MAX_PRODUCTS,
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
        return $this->getFactory()->createDisplayProductReader()->getDisplayProducts($arguments);
    }
}
