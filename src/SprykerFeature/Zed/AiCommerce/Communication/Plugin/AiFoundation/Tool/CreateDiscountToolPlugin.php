<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Throwable;

class CreateDiscountToolPlugin extends AbstractDiscountToolPlugin
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
            new ToolParameter('discountType', 'string', sprintf('Discount type: %s', $this->formatOptionsDescription($this->getConfig()->getDiscountTypes())), true),
            new ToolParameter('validFrom', 'string', 'Start date in format YYYY-MM-DD HH:MM:SS', true),
            new ToolParameter('validTo', 'string', 'End date in format YYYY-MM-DD HH:MM:SS (must be after validFrom)', true),
            new ToolParameter('isExclusive', 'boolean', 'Whether this discount is exclusive (cannot combine with others)', true),
            new ToolParameter('calculatorPlugin', 'string', sprintf('Calculator: %s', $this->formatOptionsDescription($this->getConfig()->getCalculatorPluginNames())), true),
            new ToolParameter('amount', 'integer', 'Amount: for percentage 1000 = 10.00%, for fixed amount in cents e.g. 1000 = 10.00 EUR', true),
            new ToolParameter('minimumItemAmount', 'integer', 'Minimum number of items required in cart (default: 1)', true),
            new ToolParameter('description', 'string', 'Optional internal description', false),
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
        try {
            /** @var array<string, mixed> $arguments */
            return $this->getBusinessFactory()->createDiscountWriter()->createDiscount($arguments);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(sprintf('CreateDiscountToolPlugin::execute() failed: %s', $throwable->getMessage()), ['exception' => $throwable]);

            return json_encode(['error' => 'An error occurred while creating the discount.']);
        }
    }
}
