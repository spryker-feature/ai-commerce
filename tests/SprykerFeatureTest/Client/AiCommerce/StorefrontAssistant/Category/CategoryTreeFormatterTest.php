<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Category;

use Codeception\Test\Unit;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Category
 * @group CategoryTreeFormatterTest
 */
class CategoryTreeFormatterTest extends Unit
{
    protected const int ID_CATEGORY_NODE_CAMERAS = 2;

    protected const int ID_CATEGORY_NODE_DIGITAL_CAMERAS = 4;

    protected const int ID_CATEGORY_NODE_COMPUTERS = 5;

    protected const string CATEGORY_NAME_CAMERAS = 'Cameras & Camcorders';

    protected const string CATEGORY_NAME_DIGITAL_CAMERAS = 'Digital Cameras';

    protected const string CATEGORY_NAME_COMPUTERS = 'Computer';

    protected const int CATEGORY_LINE_COUNT = 3;

    protected AiCommerceClientTester $tester;

    public function testGivenTreeWithinLimitWhenInlineTreeIsReadThenEveryLineIsReturned(): void
    {
        // Arrange
        $this->haveNestedCategoryTree();

        // Act
        $inlineCategoryTree = $this->tester->createCategoryTreeFormatter()->findInlineCategoryTree(static::CATEGORY_LINE_COUNT);

        // Assert
        $this->assertSame(
            implode("\n", [
                sprintf('%d %s', static::ID_CATEGORY_NODE_CAMERAS, static::CATEGORY_NAME_CAMERAS),
                sprintf('  %d %s', static::ID_CATEGORY_NODE_DIGITAL_CAMERAS, static::CATEGORY_NAME_DIGITAL_CAMERAS),
                sprintf('%d %s', static::ID_CATEGORY_NODE_COMPUTERS, static::CATEGORY_NAME_COMPUTERS),
            ]),
            $inlineCategoryTree,
        );
    }

    public function testGivenTreeAboveLimitWhenInlineTreeIsReadThenNullIsReturned(): void
    {
        // Arrange
        $this->haveNestedCategoryTree();

        // Act
        $inlineCategoryTree = $this->tester->createCategoryTreeFormatter()->findInlineCategoryTree(static::CATEGORY_LINE_COUNT - 1);

        // Assert
        $this->assertNull($inlineCategoryTree);
    }

    public function testGivenNoPublishedCategoryWhenInlineTreeIsReadThenNullIsReturned(): void
    {
        // Arrange
        $this->tester->haveCategoryStorageClientWithCategoryTree([]);

        // Act
        $inlineCategoryTree = $this->tester->createCategoryTreeFormatter()->findInlineCategoryTree(static::CATEGORY_LINE_COUNT);

        // Assert
        $this->assertNull($inlineCategoryTree);
    }

    protected function haveNestedCategoryTree(): void
    {
        $this->tester->haveCategoryStorageClientWithCategoryTree([
            $this->tester->createCategoryNodeStorage(static::ID_CATEGORY_NODE_CAMERAS, static::CATEGORY_NAME_CAMERAS, [
                $this->tester->createCategoryNodeStorage(static::ID_CATEGORY_NODE_DIGITAL_CAMERAS, static::CATEGORY_NAME_DIGITAL_CAMERAS),
            ]),
            $this->tester->createCategoryNodeStorage(static::ID_CATEGORY_NODE_COMPUTERS, static::CATEGORY_NAME_COMPUTERS),
        ]);
    }
}
