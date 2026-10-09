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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class ProductDetailsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'product_details';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Reads one product's full data by idProductAbstract or `sku`: name, SKU, price, rating, every attribute, `variants` (`superAttributes` lists each variant option with its values, `concretes` each variant SKU with its option values), `availability`, `discontinued`, add-on `options`, seller `offers`, `volumePrices` and, for a bundle, `bundledProducts` with their `quantity`.
        - For a product shown earlier, pass its idProductAbstract; to compare 2 to 4 products use `compare_products` instead. A SKU the customer names goes in `sku` as given, without the word "SKU"; then show that product with `display_products`.
        - Stock: `availability.isAvailable` is live for this shop, so state it plainly and never say you cannot check stock. When some variants are unavailable, each `concretes` entry carries `isAvailable`: name an unavailable and an available variant by their option values. `availability.isKnown` false means the shop publishes no stock: say so, never call it available.
        - `discontinued` appears only when the product or some variants are no longer sold. `isDiscontinued` true means it cannot be bought: say so, quote its `note`, and follow `suggestedNextAction`. With `discontinuedVariantSkus` only those variants are discontinued.
        - Insurance, warranty or gift wrapping is answered by `options`: name each matching option by its `name` with its `priceFormatted`. An option without `priceFormatted` has no price this customer may see: never guess one. No `options` means it has none.
        - "Who sells it?" or "best price?": `sellerPrices` lists every seller with its price, cheapest first; name them all with their prices, never only the cheapest (`lowestOfferMerchantName`). Cards never show seller prices. `isAvailable` false means out of stock with that seller, never the cheapest pick; a missing `isAvailable` or `priceFormatted` is unpublished: say so, never guess. No `offers` means no seller is published.
        - For a quantity price pass `quantity` and quote the `volumePrices.requested` unit price and total; `hasTiers` false means the unit price is the same at every quantity: say so, never refer the customer to the shop.
        - List the facts asked for; an attribute the result lacks is not stated, never guessed. `error` means the product was not found: say so and offer to search.
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
                ProductDetailsReader::PARAMETER_ID_PRODUCT_ABSTRACT,
                ToolParameterType::Integer,
                'From the page context or an earlier tool result. Pass this or `sku`.',
                false,
            ),
            new ToolParameter(
                ProductDetailsReader::PARAMETER_SKU,
                ToolParameterType::String,
                'An abstract or variant SKU the customer named, such as 004 or 145_29885470.',
                false,
            ),
            new ToolParameter(
                ProductDetailsReader::PARAMETER_QUANTITY,
                ToolParameterType::Integer,
                'The quantity the customer asks a price for.',
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
        return $this->getFactory()->createProductDetailsReader()->getProductDetails($arguments);
    }
}
