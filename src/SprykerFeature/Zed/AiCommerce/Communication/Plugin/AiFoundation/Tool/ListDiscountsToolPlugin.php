<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 */
class ListDiscountsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'list_discounts';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'List discounts, optionally filtered by display name. Returns up to 50 discounts with basic info including ID, name, type, status, validity period, and calculator settings.';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter('searchTerm', 'string', 'Optional display name filter (partial match). Omit or pass empty string to list all discounts.', false),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param mixed ...$arguments
     */
    public function execute(...$arguments): mixed
    {
        return $this->getBusinessFactory()->createDiscountListReader()->getDiscountList($arguments['searchTerm'] ?? null);
    }
}
