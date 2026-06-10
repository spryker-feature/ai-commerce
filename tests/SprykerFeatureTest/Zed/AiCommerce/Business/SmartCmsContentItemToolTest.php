<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SmartCmsContentItemConditionsTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCriteriaTransfer;
use Spryker\Zed\ContentBannerGui\Communication\Plugin\ContentGui\ContentBannerContentGuiEditorPlugin;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetContentItemsToolPlugin;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group SmartCmsContentItemToolTest
 */
class SmartCmsContentItemToolTest extends Unit
{
    protected const string CONTENT_TYPE_BANNER = 'Banner';

    protected AiCommerceBusinessTester $tester;

    public function testGetSmartCmsContentItemsReturnsExistingItemFilteredByType(): void
    {
        // Arrange
        $contentTransfer = $this->tester->haveSmartCmsContentItem('AI Banner', static::CONTENT_TYPE_BANNER);

        // Act
        $smartCmsContentItemCollectionTransfer = $this->tester->getFacade()->getSmartCmsContentItemCollection($this->createCriteriaByType());

        // Assert
        $keys = [];
        foreach ($smartCmsContentItemCollectionTransfer->getContentItems() as $smartCmsContentItemTransfer) {
            $keys[] = $smartCmsContentItemTransfer->getKey();
            $this->assertSame(static::CONTENT_TYPE_BANNER, $smartCmsContentItemTransfer->getContentTypeKey());
        }
        $this->assertContains($contentTransfer->getKey(), $keys);
    }

    public function testGetSmartCmsContentItemsEnrichesBannerWithTemplatesAndTwigFunction(): void
    {
        // Arrange
        $this->tester->setDependency(AiCommerceDependencyProvider::PLUGINS_CONTENT_GUI_EDITOR, [new ContentBannerContentGuiEditorPlugin()]);
        $contentTransfer = $this->tester->haveSmartCmsContentItem('AI Banner Templates', static::CONTENT_TYPE_BANNER);

        // Act
        $smartCmsContentItemCollectionTransfer = $this->tester->getFacade()->getSmartCmsContentItemCollection($this->createCriteriaByType());

        // Assert
        $matchedItem = $this->tester->findSmartCmsContentItemByKey($smartCmsContentItemCollectionTransfer, $contentTransfer->getKey());
        $this->assertNotNull($matchedItem);
        $this->assertNotSame('', $matchedItem->getTwigFunctionTemplate());
        $this->assertNotEmpty($matchedItem->getAvailableTemplates());
    }

    public function testGetContentItemsToolPluginReturnsJsonWithExistingItem(): void
    {
        // Arrange
        $contentTransfer = $this->tester->haveSmartCmsContentItem('AI Banner Tool', static::CONTENT_TYPE_BANNER);

        // Act
        $result = (new GetContentItemsToolPlugin())->execute(contentType: static::CONTENT_TYPE_BANNER);

        // Assert
        $decoded = json_decode($result, true);
        $this->assertContains($contentTransfer->getKey(), array_column($decoded['contentItems'] ?? [], 'key'));
    }

    protected function createCriteriaByType(): SmartCmsContentItemCriteriaTransfer
    {
        return (new SmartCmsContentItemCriteriaTransfer())
            ->setSmartCmsContentItemConditions(
                (new SmartCmsContentItemConditionsTransfer())->addContentTypeKey(static::CONTENT_TYPE_BANNER),
            );
    }
}
