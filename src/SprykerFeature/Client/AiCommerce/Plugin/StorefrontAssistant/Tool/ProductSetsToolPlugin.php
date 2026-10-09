<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Spryker\Client\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class ProductSetsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'product_sets';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Lists the shop's product sets (products sold together) with their `url` and `items`. No card shows them, so your reply must list every set as a link followed by the names of at least two of its items, even if that takes more than two sentences. An empty `sets` means the shop has none. Bundles are single products, not sets: when asked for bundles, also call `catalog_search` with query "bundle".
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
        return [];
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
        return $this->getFactory()->createProductSetReader()->getProductSets();
    }
}
