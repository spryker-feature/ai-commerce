<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class FormFillToolSetPlugin extends AbstractPlugin implements ToolSetPluginInterface
{
    protected const string TOOL_SET_FORM_FILL = 'form_fill_tools';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::TOOL_SET_FORM_FILL;
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
            $this->getFactory()->createFillFormToolPlugin(),
        ];
    }
}
