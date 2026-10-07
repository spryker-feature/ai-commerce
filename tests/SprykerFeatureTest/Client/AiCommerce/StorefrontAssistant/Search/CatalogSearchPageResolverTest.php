<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Search;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchPageResolver;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Search
 * @group CatalogSearchPageResolverTest
 */
class CatalogSearchPageResolverTest extends Unit
{
    protected const int LIMIT_DEFAULT = 5;

    protected const int ITEMS_PER_PAGE_DEFAULT = 12;

    protected const int ITEMS_PER_PAGE_LARGER = 24;

    public function testGivenTheFirstPageWhenResolvedThenTheFirstSearchPageIsFetchedFromItsStart(): void
    {
        // Arrange
        $catalogSearchPageResolver = new CatalogSearchPageResolver();

        // Act & Assert
        $this->assertSame(1, $catalogSearchPageResolver->resolveSearchPage(1, static::LIMIT_DEFAULT));
        $this->assertSame(static::ITEMS_PER_PAGE_DEFAULT, $catalogSearchPageResolver->resolveItemsPerPage(1, static::LIMIT_DEFAULT));
        $this->assertSame(0, $catalogSearchPageResolver->resolveOffsetWithinSearchPage(1, static::LIMIT_DEFAULT));
    }

    public function testGivenAPageInsideTheFirstSearchPageWhenResolvedThenItIsServedFromThatSearchPageAtItsOffset(): void
    {
        // Arrange
        $catalogSearchPageResolver = new CatalogSearchPageResolver();

        // Act & Assert
        $this->assertSame(1, $catalogSearchPageResolver->resolveSearchPage(2, static::LIMIT_DEFAULT));
        $this->assertSame(static::LIMIT_DEFAULT, $catalogSearchPageResolver->resolveOffsetWithinSearchPage(2, static::LIMIT_DEFAULT));
    }

    public function testGivenAPageStraddlingTwoSearchPagesWhenResolvedThenALargerSearchPageServesItWhole(): void
    {
        // Arrange
        $catalogSearchPageResolver = new CatalogSearchPageResolver();

        // Act
        $itemsPerPage = $catalogSearchPageResolver->resolveItemsPerPage(3, static::LIMIT_DEFAULT);

        // Assert
        $this->assertSame(static::ITEMS_PER_PAGE_LARGER, $itemsPerPage);
        $this->assertSame(1, $catalogSearchPageResolver->resolveSearchPage(3, static::LIMIT_DEFAULT));
        $this->assertSame(10, $catalogSearchPageResolver->resolveOffsetWithinSearchPage(3, static::LIMIT_DEFAULT));
    }
}
