<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
abstract class AbstractDiscountToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    use LoggerTrait;

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
}
