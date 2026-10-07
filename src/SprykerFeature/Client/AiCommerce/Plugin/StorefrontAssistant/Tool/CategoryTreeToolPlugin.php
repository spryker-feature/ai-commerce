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
class CategoryTreeToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'category_tree';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Lists the categories this shop carries for the customer's store and locale, one per line as "id name", with nesting shown by indentation.

        The id is what the catalog_search `category` argument filters by; a category name passed there matches nothing. Use this when the customer asks what the shop sells, or when you need the id of a category the customer named.
        DESCRIPTION;

    protected const string RESULT_KEY_CATEGORIES = 'categories';

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
        return [
            static::RESULT_KEY_CATEGORIES => $this->getFactory()->createCategoryTreeFormatter()->formatCategoryTree(),
        ];
    }
}
