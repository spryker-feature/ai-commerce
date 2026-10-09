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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReasonResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class DisplayProductsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'display_products';

    protected const string TOOL_DESCRIPTION = 'Shows the customer product cards (name, reason, image, price, SKU and link) for the idProductAbstract values you pass, exactly as a tool returned them. Call it once per reply with only the products worth showing; a product you do not pass is never shown.';

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
                    'The idProductAbstract values to show, in display order. At most %d are displayed.',
                    DisplayProductReader::MAX_PRODUCTS,
                ),
                true,
            ),
            new ToolParameter(
                DisplayProductReader::PARAMETER_REASONS,
                ToolParameterType::Object,
                sprintf(
                    'A reason for every card keyed by idProductAbstract, max %d chars: the fact that fits the request, such as {"12": "20 MP · 8× zoom"}; same-name cards differ by variant, seller or price.',
                    DisplayProductReasonResolver::MAX_REASON_LENGTH,
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
