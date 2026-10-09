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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Setup\SetupProposalBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class DisplaySetupToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'display_setup';

    protected const string TOOL_DESCRIPTION = 'Shows 2 to 4 product cards with their total against one budget, using the prices the customer sees. For different products under one budget ("home office under €1,500") use it instead of `display_products`: search each item in its own category and pick good matches, not the cheapest. Over budget, nothing is shown: swap the priciest item for a cheaper one. For any swap, call it again with the full list.';

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
                SetupProposalBuilder::PARAMETER_ID_PRODUCT_ABSTRACTS,
                ToolParameterType::Array,
                sprintf('%d to %d idProductAbstract values, one per item.', SetupProposalBuilder::MIN_ITEMS, SetupProposalBuilder::MAX_ITEMS),
                true,
            ),
            new ToolParameter(
                SetupProposalBuilder::PARAMETER_BUDGET,
                ToolParameterType::Number,
                'The budget in the shop currency, such as 1500.',
                true,
            ),
            new ToolParameter(
                SetupProposalBuilder::PARAMETER_REASONS,
                ToolParameterType::Object,
                'A reason per card keyed by idProductAbstract, as for `display_products`.',
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
        return $this->getFactory()->createSetupProposalBuilder()->buildSetupProposal($arguments);
    }
}
