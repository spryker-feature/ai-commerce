<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Throwable;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class UpdateDiscountToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    use LoggerTrait;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'update_discount';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Update an existing discount by ID. Only provided optional fields will be updated.';
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
            new ToolParameter('idDiscount', 'integer', 'The discount ID to update', true),
            new ToolParameter('displayName', 'string', 'New display name', false),
            new ToolParameter('discountType', 'string', sprintf('Discount type: %s', $this->formatOptionsDescription($this->getConfig()->getDiscountTypes())), false),
            new ToolParameter('validFrom', 'string', 'Start date in format YYYY-MM-DD HH:MM:SS', false),
            new ToolParameter('validTo', 'string', 'End date in format YYYY-MM-DD HH:MM:SS', false),
            new ToolParameter('isExclusive', 'boolean', 'Whether this discount is exclusive', false),
            new ToolParameter('calculatorPlugin', 'string', sprintf('Calculator: %s', $this->formatOptionsDescription($this->getConfig()->getCalculatorPluginNames())), false),
            new ToolParameter('amount', 'integer', 'Amount: for percentage 1000 = 10.00%, for fixed amount in cents', false),
            new ToolParameter('minimumItemAmount', 'integer', 'Minimum number of items required in cart', false),
            new ToolParameter('description', 'string', 'Internal description', false),
            new ToolParameter('priority', 'integer', 'Priority — lower number means higher priority', false),
        ];
    }

    /**
     * @param array<string> $options
     */
    protected function formatOptionsDescription(array $options): string
    {
        return implode(' or ', array_map(
            static fn (string $option): string => sprintf('"%s"', $option),
            $options,
        ));
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
            return $this->getBusinessFactory()->createDiscountWriter()->updateDiscount((int)$arguments['idDiscount'], $arguments);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(sprintf('UpdateDiscountToolPlugin::execute() failed: %s', $throwable->getMessage()), ['exception' => $throwable]);

            return json_encode(['error' => 'An error occurred while updating the discount.']);
        }
    }
}
