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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage\ShopPageReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class ReadShopPageToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'read_shop_page';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Reads the text of one shop content page, such as returns, delivery, payment or warranty, by the `id` of a catalog_suggest `cms_page` result. Answer policy questions only from this `text` and add the page `url`. Look a topic up once with catalog_suggest; if it returns no `cms_page` for it, `found` is false, or no text covers the question, say you found no shop policy on it and make no promise.
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
                ShopPageReader::PARAMETER_ID_CMS_PAGE,
                ToolParameterType::Integer,
                'The `id` of a catalog_suggest `cms_page` result.',
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
        return $this->getFactory()->createShopPageReader()->readShopPage($arguments);
    }
}
