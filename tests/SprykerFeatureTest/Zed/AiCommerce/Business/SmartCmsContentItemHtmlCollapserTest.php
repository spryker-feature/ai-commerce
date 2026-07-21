<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SmartCmsContentPlaceholderTransfer;
use Generated\Shared\Transfer\SmartCmsContentRequestTransfer;
use Generated\Shared\Transfer\SmartCmsContentTranslationTransfer;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Collapser\SmartCmsContentItemHtmlCollapserInterface;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group SmartCmsContentItemHtmlCollapserTest
 */
class SmartCmsContentItemHtmlCollapserTest extends Unit
{
    protected const string ENTITY_TYPE_CMS_BLOCK = 'cms_block';

    protected const string ENTITY_TYPE_CMS_PAGE = 'cms_page';

    /**
     * The bare content item Twig call the editor widget carries in its `data-twig-expression` attribute.
     */
    protected const string TWIG_EXPRESSION = "{{ product(['001_25904006']) }}";

    /**
     * The rendered widget span's human-readable dump that leaked into stored content in the bug (CC-39662).
     */
    protected const string WIDGET_HUMAN_READABLE_MARKER = 'Content Item Type:';

    protected const string CONTENT_WIDGET_MARKER = 'data-twig-expression';

    /**
     * A second content item Twig call, to prove several widgets in one translation are all collapsed.
     */
    protected const string SECOND_TWIG_EXPRESSION = "{{ product(['002_25904060']) }}";

    protected const string ENTITY_TYPE_UNKNOWN = 'not_a_cms_entity';

    protected const string ENTITY_TYPE_EMPTY = '';

    protected AiCommerceBusinessTester $tester;

    public function testCollapsePlaceholderContentReducesRenderedProductWidgetToBareTwigTokenForCmsBlock(): void
    {
        // Arrange
        $smartCmsContentRequestTransfer = $this->createRequestWithWidgetContent(
            static::ENTITY_TYPE_CMS_BLOCK,
            $this->createRenderedProductWidgetHtml(),
        );

        // Act
        $smartCmsContentRequestTransfer = $this->getCollapser()->collapsePlaceholderContent($smartCmsContentRequestTransfer);

        // Assert
        $content = $this->extractFirstTranslationContent($smartCmsContentRequestTransfer);
        $this->assertStringContainsString('{{ product(', $content);
        $this->assertStringNotContainsString(static::CONTENT_WIDGET_MARKER, $content);
        $this->assertStringNotContainsString(static::WIDGET_HUMAN_READABLE_MARKER, $content);
    }

    public function testCollapsePlaceholderContentLeavesPlainHtmlWithoutWidgetUnchanged(): void
    {
        // Arrange
        $plainHtml = '<p>Just some editor copy without any content widget.</p>';
        $smartCmsContentRequestTransfer = $this->createRequestWithWidgetContent(static::ENTITY_TYPE_CMS_BLOCK, $plainHtml);

        // Act
        $smartCmsContentRequestTransfer = $this->getCollapser()->collapsePlaceholderContent($smartCmsContentRequestTransfer);

        // Assert
        $this->assertSame($plainHtml, $this->extractFirstTranslationContent($smartCmsContentRequestTransfer));
    }

    public function testCollapsePlaceholderContentReducesRenderedProductWidgetToBareTwigTokenForCmsPage(): void
    {
        // Arrange
        $smartCmsContentRequestTransfer = $this->createRequestWithWidgetContent(
            static::ENTITY_TYPE_CMS_PAGE,
            $this->createRenderedProductWidgetHtml(),
        );

        // Act
        $smartCmsContentRequestTransfer = $this->getCollapser()->collapsePlaceholderContent($smartCmsContentRequestTransfer);

        // Assert
        $content = $this->extractFirstTranslationContent($smartCmsContentRequestTransfer);
        $this->assertStringContainsString('{{ product(', $content);
        $this->assertStringNotContainsString(static::CONTENT_WIDGET_MARKER, $content);
        $this->assertStringNotContainsString(static::WIDGET_HUMAN_READABLE_MARKER, $content);
    }

    public function testCollapsePlaceholderContentReducesEveryWidgetWhenSeveralAreInOneTranslation(): void
    {
        // Arrange
        $content = $this->createRenderedProductWidgetHtml()
            . '<p>Divider copy between widgets.</p>'
            . $this->createRenderedProductWidgetHtml(static::SECOND_TWIG_EXPRESSION);
        $smartCmsContentRequestTransfer = $this->createRequestWithWidgetContent(static::ENTITY_TYPE_CMS_BLOCK, $content);

        // Act
        $smartCmsContentRequestTransfer = $this->getCollapser()->collapsePlaceholderContent($smartCmsContentRequestTransfer);

        // Assert
        $collapsedContent = $this->extractFirstTranslationContent($smartCmsContentRequestTransfer);
        $this->assertStringContainsString(static::TWIG_EXPRESSION, $collapsedContent);
        $this->assertStringContainsString(static::SECOND_TWIG_EXPRESSION, $collapsedContent);
        $this->assertStringNotContainsString(static::CONTENT_WIDGET_MARKER, $collapsedContent);
    }

    public function testCollapsePlaceholderContentLeavesContentUnchangedForUnknownEntityType(): void
    {
        // Unknown entity types intentionally fail open (leave content untouched) rather than misroute conversion.
        // Arrange
        $widgetHtml = $this->createRenderedProductWidgetHtml();
        $smartCmsContentRequestTransfer = $this->createRequestWithWidgetContent(static::ENTITY_TYPE_UNKNOWN, $widgetHtml);

        // Act
        $smartCmsContentRequestTransfer = $this->getCollapser()->collapsePlaceholderContent($smartCmsContentRequestTransfer);

        // Assert
        $this->assertSame($widgetHtml, $this->extractFirstTranslationContent($smartCmsContentRequestTransfer));
    }

    public function testCollapsePlaceholderContentLeavesContentUnchangedForEmptyEntityType(): void
    {
        // Arrange
        $widgetHtml = $this->createRenderedProductWidgetHtml();
        $smartCmsContentRequestTransfer = $this->createRequestWithWidgetContent(static::ENTITY_TYPE_EMPTY, $widgetHtml);

        // Act
        $smartCmsContentRequestTransfer = $this->getCollapser()->collapsePlaceholderContent($smartCmsContentRequestTransfer);

        // Assert
        $this->assertSame($widgetHtml, $this->extractFirstTranslationContent($smartCmsContentRequestTransfer));
    }

    protected function getCollapser(): SmartCmsContentItemHtmlCollapserInterface
    {
        /** @var \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory $factory */
        $factory = $this->tester->getFactory();

        return $factory->createSmartCmsContentItemHtmlCollapser();
    }

    protected function createRequestWithWidgetContent(string $entityType, string $content): SmartCmsContentRequestTransfer
    {
        return (new SmartCmsContentRequestTransfer())
            ->setEntityType($entityType)
            ->addPlaceholder(
                (new SmartCmsContentPlaceholderTransfer())
                    ->setPlaceholder('content')
                    ->addTranslation(
                        (new SmartCmsContentTranslationTransfer())
                            ->setLocaleName('en_US')
                            ->setContent($content),
                    ),
            );
    }

    /**
     * Faithfully mirrors the editor widget markup produced by
     * `Spryker\Zed\ContentGui\ContentGuiConfig::getEditorContentWidgetTemplate()` for an Abstract Product List:
     * a `contenteditable="false"` span carrying every `data-*` attribute the HTML->Twig round trip depends on,
     * plus the human-readable inner spans that leaked into stored content in the bug.
     */
    protected function createRenderedProductWidgetHtml(?string $twigExpression = null): string
    {
        return '<span class="content-item-editor js-content-item-editor" contenteditable="false" '
            . 'data-type="Abstract Product List" data-key="product-abstract-list" '
            . 'data-display-type="Abstract Product List" '
            . 'data-id="1" '
            . 'data-template="Product Abstract List Widget" '
            . 'data-twig-expression="' . ($twigExpression ?? static::TWIG_EXPRESSION) . '">'
                . '<span>Content Item Type: <b>Abstract Product List</b></span>'
                . '<span>Content Item Key#: <b>product-abstract-list</b></span>'
                . '<span>Name: <b>Featured products</b></span>'
                . '<span>Template: <b>Product Abstract List Widget</b></span>'
            . '</span>';
    }

    protected function extractFirstTranslationContent(SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer): string
    {
        $smartCmsContentPlaceholderTransfer = $smartCmsContentRequestTransfer->getPlaceholders()->offsetGet(0);

        return (string)$smartCmsContentPlaceholderTransfer->getTranslations()->offsetGet(0)->getContent();
    }
}
