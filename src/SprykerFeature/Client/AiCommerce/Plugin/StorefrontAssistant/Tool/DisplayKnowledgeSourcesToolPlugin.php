<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Spryker\Client\AiFoundationExtension\Dependency\Tools\ToolPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Shared\AiFoundationExtension\Tools\ToolParameterType;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Document\DocumentToolArgumentMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class DisplayKnowledgeSourcesToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'display_knowledge_sources';

    protected const string TOOL_DESCRIPTION = 'Shows the customer the shop documents that your answer used, as a list of sources with a link when the shop has one. Call it once after an answer from product_knowledge_search excerpts, with the sourceIdentifier values of the excerpts you used and the same products as the search. Only documents of these products are shown. Never write a link to a document in your reply.';

    protected const string PRODUCT_PARAMETER_DESCRIPTION = '%s At most %d products in total over abstract_skus, concrete_skus and product_abstract_ids. Pass the same products as the product_knowledge_search call.';

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
     * @return array<\Spryker\Client\AiFoundationExtension\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(DocumentToolArgumentMapper::PARAMETER_ATTACHMENT_IDS, ToolParameterType::Array, 'The sourceIdentifier values of the product_knowledge_search excerpts that your answer used, most relevant first.', true),
            new ToolParameter(DocumentToolArgumentMapper::PARAMETER_ABSTRACT_SKUS, ToolParameterType::Array, $this->createProductParameterDescription('Abstract SKUs of the products whose documents your answer used.'), false),
            new ToolParameter(DocumentToolArgumentMapper::PARAMETER_CONCRETE_SKUS, ToolParameterType::Array, $this->createProductParameterDescription('Concrete SKUs of product variants.'), false),
            new ToolParameter(DocumentToolArgumentMapper::PARAMETER_PRODUCT_ABSTRACT_IDS, ToolParameterType::Array, $this->createProductParameterDescription('idProductAbstract values.'), false),
        ];
    }

    /**
     * {@inheritDoc}
     * - Resolves the knowledge document scope of the given products and keeps only the documents whose source identifier is in `attachment_ids`.
     * - Returns `sources` with `documentName`, `sourceType`, `sourceIdentifier` and, when the shop data has a valid http or https URL, `url`.
     * - Reads each URL from the shop data only; deduplicates the sources and returns at most the configured number.
     * - Returns the reason in `messages` when an argument is invalid.
     *
     * @api
     *
     * @param mixed ...$arguments
     *
     * @return array<string, mixed>
     */
    public function execute(...$arguments): mixed
    {
        return $this->getFactory()->createKnowledgeSourceDisplayReader()->getKnowledgeSources($arguments);
    }

    protected function createProductParameterDescription(string $description): string
    {
        return sprintf(
            static::PRODUCT_PARAMETER_DESCRIPTION,
            $description,
            $this->getFactory()->getConfig()->getKnowledgeDocumentMaxIdentifiers(),
        );
    }
}
