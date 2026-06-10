<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Expander;

use Generated\Shared\Transfer\CmsBlockGlossaryPlaceholderTransfer;
use Generated\Shared\Transfer\CmsBlockGlossaryPlaceholderTranslationTransfer;
use Generated\Shared\Transfer\CmsBlockGlossaryTransfer;
use Generated\Shared\Transfer\CmsGlossaryAttributesTransfer;
use Generated\Shared\Transfer\CmsGlossaryTransfer;
use Generated\Shared\Transfer\CmsPlaceholderTranslationTransfer;
use Generated\Shared\Transfer\SmartCmsContentPlaceholderTransfer;
use Generated\Shared\Transfer\SmartCmsContentResponseTransfer;
use Generated\Shared\Transfer\SmartCmsContentTranslationTransfer;
use Spryker\Zed\ContentGui\Business\ContentGuiFacadeInterface;

class SmartCmsContentItemHtmlExpander implements SmartCmsContentItemHtmlExpanderInterface
{
    protected const string ENTITY_TYPE_CMS_PAGE = 'cms_page';

    protected const string ENTITY_TYPE_CMS_BLOCK = 'cms_block';

    /**
     * Only content item Twig calls need to be rendered as editor widgets; skip the round trip otherwise.
     */
    protected const string CONTENT_ITEM_MARKER = '{{ content_';

    public function __construct(protected readonly ContentGuiFacadeInterface $contentGuiFacade)
    {
    }

    public function expandPlaceholderContent(
        SmartCmsContentResponseTransfer $smartCmsContentResponseTransfer,
        string $entityType,
    ): SmartCmsContentResponseTransfer {
        foreach ($smartCmsContentResponseTransfer->getPlaceholders() as $smartCmsContentPlaceholderTransfer) {
            $this->expandPlaceholderTranslations($smartCmsContentPlaceholderTransfer, $entityType);
        }

        return $smartCmsContentResponseTransfer;
    }

    protected function expandPlaceholderTranslations(
        SmartCmsContentPlaceholderTransfer $smartCmsContentPlaceholderTransfer,
        string $entityType,
    ): void {
        foreach ($smartCmsContentPlaceholderTransfer->getTranslations() as $smartCmsContentTranslationTransfer) {
            $this->expandTranslation($smartCmsContentTranslationTransfer, $entityType);
        }
    }

    protected function expandTranslation(
        SmartCmsContentTranslationTransfer $smartCmsContentTranslationTransfer,
        string $entityType,
    ): void {
        $content = (string)$smartCmsContentTranslationTransfer->getContent();

        if (!str_contains($content, static::CONTENT_ITEM_MARKER)) {
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

        // Unknown or empty entity type: leave the untrusted content unchanged rather than misrouting it to page conversion.
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

        $cmsGlossaryTransfer = $this->contentGuiFacade->convertCmsGlossaryTwigExpressionsToHtml($cmsGlossaryTransfer);

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

        $cmsBlockGlossaryTransfer = $this->contentGuiFacade->convertCmsBlockGlossaryTwigExpressionsToHtml($cmsBlockGlossaryTransfer);

        return $this->extractFirstBlockTranslation($cmsBlockGlossaryTransfer) ?? $content;
    }

    /**
     * The conversion above wraps the single content string in exactly one attribute/translation, so the converted HTML is the first translation of the first attribute.
     */
    protected function extractFirstPageTranslation(CmsGlossaryTransfer $cmsGlossaryTransfer): ?string
    {
        foreach ($cmsGlossaryTransfer->getGlossaryAttributes() as $cmsGlossaryAttributesTransfer) {
            foreach ($cmsGlossaryAttributesTransfer->getTranslations() as $cmsPlaceholderTranslationTransfer) {
                return $cmsPlaceholderTranslationTransfer->getTranslation();
            }
        }

        return null;
    }

    /**
     * The conversion above wraps the single content string in exactly one placeholder/translation, so the converted HTML is the first translation of the first placeholder.
     */
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
