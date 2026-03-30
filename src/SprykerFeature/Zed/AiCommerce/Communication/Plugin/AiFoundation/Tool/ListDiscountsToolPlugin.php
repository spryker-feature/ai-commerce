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
 */
class ListDiscountsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    use LoggerTrait;

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
            new ToolParameter('isActive', 'boolean', 'Filter by status: true for active discounts, false for inactive.', false),
            new ToolParameter('discountType', 'string', 'Filter by discount type: "cart_rule" or "voucher".', false),
            new ToolParameter('validFrom', 'string', 'Show discounts still valid after this date (format: YYYY-MM-DD HH:MM:SS).', false),
            new ToolParameter('validTo', 'string', 'Show discounts that started before this date (format: YYYY-MM-DD HH:MM:SS).', false),
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
            return $this->getBusinessFactory()->createDiscountListReader()->getDiscountList($arguments);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(sprintf('ListDiscountsToolPlugin::execute() failed: %s', $throwable->getMessage()), ['exception' => $throwable]);

            return json_encode(['error' => 'An error occurred while retrieving discounts.']);
        }
    }
}
