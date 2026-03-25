<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class GetOmsProcessDefinitionToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public function getName(): string
    {
        return 'get_oms_process_definition';
    }

    public function getDescription(): string
    {
        return 'Get the full OMS process definition for an order including all states, transitions, events, and subprocesses.';
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                'orderReference',
                'string',
                'The order reference, for example DE--123',
                true,
            ),
        ];
    }

    public function execute(...$arguments): mixed
    {
        return $this->getFacade()->getOmsProcessDefinition((string)($arguments[0] ?? ''));
    }
}
