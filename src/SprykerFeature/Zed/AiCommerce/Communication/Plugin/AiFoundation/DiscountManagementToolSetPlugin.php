<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class DiscountManagementToolSetPlugin extends AbstractPlugin implements ToolSetPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return AiCommerceConstants::TOOL_SET_DISCOUNT_MANAGEMENT;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface>
     */
    public function getTools(): array
    {
        return [
            $this->getFactory()->createListDiscountsToolPlugin(),
            $this->getFactory()->createGetDiscountDetailsToolPlugin(),
            $this->getFactory()->createCreateDiscountToolPlugin(),
            $this->getFactory()->createUpdateDiscountToolPlugin(),
        ];
    }
}
