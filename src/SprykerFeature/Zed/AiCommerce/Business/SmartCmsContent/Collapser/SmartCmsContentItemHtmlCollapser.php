<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Collapser;

use Generated\Shared\Transfer\CmsBlockGlossaryPlaceholderTransfer;
use Generated\Shared\Transfer\CmsBlockGlossaryPlaceholderTranslationTransfer;
use Generated\Shared\Transfer\CmsBlockGlossaryTransfer;
use Generated\Shared\Transfer\CmsGlossaryAttributesTransfer;
use Generated\Shared\Transfer\CmsGlossaryTransfer;
use Generated\Shared\Transfer\CmsPlaceholderTranslationTransfer;
use Generated\Shared\Transfer\SmartCmsContentPlaceholderTransfer;
use Generated\Shared\Transfer\SmartCmsContentRequestTransfer;
use Generated\Shared\Transfer\SmartCmsContentTranslationTransfer;
use Spryker\Zed\ContentGui\Business\ContentGuiFacadeInterface;

class SmartCmsContentItemHtmlCollapser implements SmartCmsContentItemHtmlCollapserInterface
{
    protected const string ENTITY_TYPE_CMS_PAGE = 'cms_page';

    protected const string ENTITY_TYPE_CMS_BLOCK = 'cms_block';

    protected const string CONTENT_WIDGET_MARKER = 'data-twig-expression';

    public function __construct(protected readonly ContentGuiFacadeInterface $contentGuiFacade)
    {
    }

    public function collapsePlaceholderContent(
        SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer,
    ): SmartCmsContentRequestTransfer {
        $entityType = (string)$smartCmsContentRequestTransfer->getEntityType();

        if ($entityType === '') {
            return $smartCmsContentRequestTransfer;
        }

        foreach ($smartCmsContentRequestTransfer->getPlaceholders() as $smartCmsContentPlaceholderTransfer) {
            $this->collapsePlaceholderTranslations($smartCmsContentPlaceholderTransfer, $entityType);
        }

        return $smartCmsContentRequestTransfer;
    }

    protected function collapsePlaceholderTranslations(
        SmartCmsContentPlaceholderTransfer $smartCmsContentPlaceholderTransfer,
        string $entityType,
    ): void {
        foreach ($smartCmsContentPlaceholderTransfer->getTranslations() as $smartCmsContentTranslationTransfer) {
            $this->collapseTranslation($smartCmsContentTranslationTransfer, $entityType);
        }
    }

    protected function collapseTranslation(
        SmartCmsContentTranslationTransfer $smartCmsContentTranslationTransfer,
        string $entityType,
    ): void {
        $content = (string)$smartCmsContentTranslationTransfer->getContent();

        if (!str_contains($content, static::CONTENT_WIDGET_MARKER)) {
            return;
        }

        $smartCmsContentTranslationTransfer->setContent($this->convertContent($content, $entityType));
    }

    protected function convertContent(string $content, string $entityType): string
    {
        if ($entityType === static::ENTITY_TYPE_CMS_BLOCK) {
            return $this->convertBlockContent($content);
        }

        if ($entityType === static::ENTITY_TYPE_CMS_PAGE) {
            return $this->convertPageContent($content);
        }

        return $content;
    }

    protected function convertPageContent(string $content): string
    {
        $cmsGlossaryTransfer = (new CmsGlossaryTransfer())
            ->addGlossaryAttribute(
                (new CmsGlossaryAttributesTransfer())->addTranslation(
                    (new CmsPlaceholderTranslationTransfer())->setTranslation($content),
                ),
            );

        $cmsGlossaryTransfer = $this->contentGuiFacade->convertCmsGlossaryHtmlToTwigExpressions($cmsGlossaryTransfer);

        return $this->extractFirstPageTranslation($cmsGlossaryTransfer) ?? $content;
    }

    protected function convertBlockContent(string $content): string
    {
        $cmsBlockGlossaryTransfer = (new CmsBlockGlossaryTransfer())
            ->addGlossaryPlaceholder(
                (new CmsBlockGlossaryPlaceholderTransfer())->addTranslation(
                    (new CmsBlockGlossaryPlaceholderTranslationTransfer())->setTranslation($content),
                ),
            );

        $cmsBlockGlossaryTransfer = $this->contentGuiFacade->convertCmsBlockGlossaryHtmlToTwigExpressions($cmsBlockGlossaryTransfer);

        return $this->extractFirstBlockTranslation($cmsBlockGlossaryTransfer) ?? $content;
    }

    protected function extractFirstPageTranslation(CmsGlossaryTransfer $cmsGlossaryTransfer): ?string
    {
        foreach ($cmsGlossaryTransfer->getGlossaryAttributes() as $cmsGlossaryAttributesTransfer) {
            foreach ($cmsGlossaryAttributesTransfer->getTranslations() as $cmsPlaceholderTranslationTransfer) {
                return $cmsPlaceholderTranslationTransfer->getTranslation();
            }
        }

        return null;
    }

    protected function extractFirstBlockTranslation(CmsBlockGlossaryTransfer $cmsBlockGlossaryTransfer): ?string
    {
        foreach ($cmsBlockGlossaryTransfer->getGlossaryPlaceholders() as $cmsBlockGlossaryPlaceholderTransfer) {
            foreach ($cmsBlockGlossaryPlaceholderTransfer->getTranslations() as $cmsBlockGlossaryPlaceholderTranslationTransfer) {
                return $cmsBlockGlossaryPlaceholderTranslationTransfer->getTranslation();
            }
        }

        return null;
    }
}
