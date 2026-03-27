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
class CreateDiscountToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'create_discount';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Create a new discount. Provide all required fields as individual parameters.';
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
            new ToolParameter('displayName', 'string', 'Unique display name for the discount', true),
            new ToolParameter('discountType', 'string', 'Discount type: "voucher" or "cart_rule"', true),
            new ToolParameter('validFrom', 'string', 'Start date in format YYYY-MM-DD HH:MM:SS', true),
            new ToolParameter('validTo', 'string', 'End date in format YYYY-MM-DD HH:MM:SS (must be after validFrom)', true),
            new ToolParameter('isExclusive', 'boolean', 'Whether this discount is exclusive (cannot combine with others)', true),
            new ToolParameter('calculatorPlugin', 'string', 'Calculator: "PLUGIN_CALCULATOR_PERCENTAGE" or "PLUGIN_CALCULATOR_FIXED"', true),
            new ToolParameter('amount', 'integer', 'Amount: for percentage 1000 = 10.00%, for fixed amount in cents e.g. 1000 = 10.00 EUR', true),
            new ToolParameter('collectorQueryString', 'string', 'Query string for which items the discount applies to (e.g. sku = "ABC123"), use empty string for all items', true),
            new ToolParameter('minimumItemAmount', 'integer', 'Minimum number of items required in cart (default: 1)', true),
            new ToolParameter('description', 'string', 'Optional internal description', false),
            new ToolParameter('decisionRuleQueryString', 'string', 'Optional additional condition query string (e.g. sub-total >= "10000")', false),
            new ToolParameter('priority', 'integer', 'Optional priority — lower number means higher priority (default: 9999)', false),
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
        /** @var array<string, mixed> $arguments */
        return $this->getBusinessFactory()->createDiscountWriter()->createDiscount($arguments);
    }
}
