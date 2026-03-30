<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\SkillOrderManagementKnowledgeBase;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * Provides a static knowledge base about how Spryker order management works,
 * including order detail sections, OMS states, manual events, shipments,
 * returns, refunds, and available actions in the Backoffice.
 * Call this tool when the user asks HOW order management works,
 * what sections are on the order detail page, or how to interpret order states.
 */
class SkillOrderManagementKnowledgeBaseToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    protected const string KNOWLEDGE_FILE_PATH = __DIR__ . '/order_management_knowledge.md';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'skill_order_management_knowledge_base';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Returns comprehensive knowledge about how Spryker order management works: '
            . 'order detail page sections, OMS state machine concepts, manual trigger events, '
            . 'shipment management, returns, refunds, payments, discounts on orders, '
            . 'and all available Backoffice actions for orders. '
            . 'Call this tool when the user asks HOW order management works, '
            . 'what the order detail page shows, how OMS states work, '
            . 'or how to perform order-related actions.';
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
            return json_encode(['error' => 'Order management knowledge base file could not be read.']);
        }

        return $content;
    }
}
