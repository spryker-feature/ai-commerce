<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\SkillDiscountKnowledgeBase;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * Provides a static knowledge base about how Spryker discounts work,
 * including field descriptions, allowed values, query string syntax,
 * discount types, calculator types, conditions, and voucher codes.
 * Call this tool when the user asks HOW to configure a discount,
 * what fields are available, or how query strings and conditions work.
 */
class SkillDiscountKnowledgeBaseToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    protected const string KNOWLEDGE_FILE_PATH = __DIR__ . '/discount_knowledge.md';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'skill_discount_knowledge_base';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Returns comprehensive knowledge about how Spryker discounts work: '
            . 'field descriptions, allowed values, discount types (Cart rule vs Voucher codes), '
            . 'calculator types (Percentage vs Fixed amount), query string syntax and available fields, '
            . 'condition fields, and voucher code generation. '
            . 'Call this tool when the user asks HOW to configure a discount, what values are valid, '
            . 'how to write a query string, or what conditions and fields are available.';
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
            return json_encode(['error' => 'Discount knowledge base file could not be read.']);
        }

        return $content;
    }
}
