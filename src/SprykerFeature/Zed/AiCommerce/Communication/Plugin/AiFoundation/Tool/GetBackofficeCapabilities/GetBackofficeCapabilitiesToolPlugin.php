<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetBackofficeCapabilities;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class GetBackofficeCapabilitiesToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    protected const string KNOWLEDGE_FILE_PATH = __DIR__ . '/backoffice_knowledge.md';

    public function getName(): string
    {
        return 'get_backoffice_capabilities';
    }

    public function getDescription(): string
    {
        return 'Returns a structured overview of all Backoffice capabilities and sections. Use this tool when the user asks what the Backoffice can do, which features are available, or what sections exist.';
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
        return [];
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
        $content = file_get_contents(static::KNOWLEDGE_FILE_PATH);

        if ($content === false) {
            return json_encode(['error' => 'Backoffice capabilities knowledge file could not be read.']);
        }

        return $content;
    }
}
