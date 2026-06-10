<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Reader;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCollectionTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCriteriaTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemTransfer;
use Spryker\Zed\ContentGuiExtension\Dependency\Plugin\ContentGuiEditorPluginInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class SmartCmsContentItemReader implements SmartCmsContentItemReaderInterface
{
    /**
     * @param array<\Spryker\Zed\ContentGuiExtension\Dependency\Plugin\ContentGuiEditorPluginInterface> $contentGuiEditorPlugins
     */
    public function __construct(
        protected readonly AiCommerceRepositoryInterface $aiCommerceRepository,
        protected readonly AiCommerceConfig $aiCommerceConfig,
        protected readonly array $contentGuiEditorPlugins,
    ) {
    }

    public function getContentItems(
        SmartCmsContentItemCriteriaTransfer $smartCmsContentItemCriteriaTransfer,
    ): SmartCmsContentItemCollectionTransfer {
        $smartCmsContentItemCollectionTransfer = $this->aiCommerceRepository->getSmartCmsContentItemCollection(
            $this->ensureLookupLimit($smartCmsContentItemCriteriaTransfer),
        );

        foreach ($smartCmsContentItemCollectionTransfer->getContentItems() as $smartCmsContentItemTransfer) {
            $this->enrichWithEditorPluginData($smartCmsContentItemTransfer);
        }

        return $smartCmsContentItemCollectionTransfer;
    }

    /**
     * Guarantees the configured lookup limit is applied so the AI never receives an unbounded content-item list, keeping the limit a single source of truth in the Config.
     */
    protected function ensureLookupLimit(
        SmartCmsContentItemCriteriaTransfer $smartCmsContentItemCriteriaTransfer,
    ): SmartCmsContentItemCriteriaTransfer {
        if ($smartCmsContentItemCriteriaTransfer->getPagination()?->getLimit() !== null) {
            return $smartCmsContentItemCriteriaTransfer;
        }

        return $smartCmsContentItemCriteriaTransfer->setPagination(
            (new PaginationTransfer())->setLimit($this->aiCommerceConfig->getSmartCmsContentItemLookupLimit()),
        );
    }

    protected function enrichWithEditorPluginData(SmartCmsContentItemTransfer $smartCmsContentItemTransfer): void
    {
        $contentTypeKey = $smartCmsContentItemTransfer->getContentTypeKey();

        if ($contentTypeKey === null) {
            return;
        }

        $contentGuiEditorPlugin = $this->findEditorPluginByType($contentTypeKey);

        if ($contentGuiEditorPlugin === null) {
            return;
        }

        $smartCmsContentItemTransfer->setTwigFunctionTemplate($contentGuiEditorPlugin->getTwigFunctionTemplate());

        foreach ($contentGuiEditorPlugin->getTemplates() as $contentWidgetTemplateTransfer) {
            $identifier = $contentWidgetTemplateTransfer->getIdentifier();

            if ($identifier !== null) {
                $smartCmsContentItemTransfer->addAvailableTemplate($identifier);
            }
        }
    }

    protected function findEditorPluginByType(string $contentTypeKey): ?ContentGuiEditorPluginInterface
    {
        foreach ($this->contentGuiEditorPlugins as $contentGuiEditorPlugin) {
            if ($contentGuiEditorPlugin->getType() === $contentTypeKey) {
                return $contentGuiEditorPlugin;
            }
        }

        return null;
    }
}
