<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemConditionsTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCriteriaTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Throwable;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class GetContentItemsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    use LoggerTrait;

    protected const string TOOL_NAME = 'get_content_items';

    protected const string PARAMETER_CONTENT_TYPE = 'contentType';

    protected const string KEY_ERROR = 'error';

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
        return 'List existing CMS content items the editor can embed, with their key, name, content type, available templates, and Twig function template. Optionally filter by content type (e.g. "Banner", "Product Set"). Use the returned key and a listed template to build a valid content item Twig call.';
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
            new ToolParameter(static::PARAMETER_CONTENT_TYPE, 'string', 'Optional content type key to filter by, e.g. "Banner". Omit to list all content items.', false),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function execute(...$arguments): mixed
    {
        try {
            $smartCmsContentItemCriteriaTransfer = $this->createCriteria($arguments[static::PARAMETER_CONTENT_TYPE] ?? null);
            $smartCmsContentItemCollectionTransfer = $this->getFacade()->getSmartCmsContentItemCollection($smartCmsContentItemCriteriaTransfer);

            return (string)json_encode($smartCmsContentItemCollectionTransfer->toArray(true, true));
        } catch (Throwable $throwable) {
            $this->getLogger()->error(sprintf('GetContentItemsToolPlugin::execute() failed: %s', $throwable->getMessage()), ['exception' => $throwable]);

            return (string)json_encode([static::KEY_ERROR => 'An error occurred while retrieving content items.']);
        }
    }

    protected function createCriteria(mixed $contentType): SmartCmsContentItemCriteriaTransfer
    {
        $smartCmsContentItemConditionsTransfer = new SmartCmsContentItemConditionsTransfer();

        if (is_string($contentType) && $contentType !== '') {
            $smartCmsContentItemConditionsTransfer->addContentTypeKey($contentType);
        }

        return (new SmartCmsContentItemCriteriaTransfer())
            ->setSmartCmsContentItemConditions($smartCmsContentItemConditionsTransfer)
            ->setPagination(
                (new PaginationTransfer())->setLimit($this->getConfig()->getSmartCmsContentItemLookupLimit()),
            );
    }
}
