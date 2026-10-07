<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Sanitizer;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\AiCommerceFactory;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Sanitizer\StorefrontAssistantPageContextSanitizer;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Sanitizer
 * @group StorefrontAssistantPageContextSanitizerTest
 */
class StorefrontAssistantPageContextSanitizerTest extends Unit
{
    protected const int MAX_VALUE_LENGTH = 120;

    protected const int MAX_FILTER_COUNT = 10;

    public function testGivenANonArrayPageContextWhenSanitizedThenNoContextIsReturned(): void
    {
        // Arrange
        $storefrontAssistantPageContextSanitizer = (new AiCommerceFactory())->createStorefrontAssistantPageContextSanitizer();

        // Act
        $sanitizedPageContext = $storefrontAssistantPageContextSanitizer->sanitizePageContext('product');

        // Assert
        $this->assertNull($sanitizedPageContext);
    }

    public function testGivenANonReportablePageTypeWhenSanitizedThenNoContextIsReturned(): void
    {
        // Arrange
        $storefrontAssistantPageContextSanitizer = (new AiCommerceFactory())->createStorefrontAssistantPageContextSanitizer();

        // Act
        $sanitizedPageContext = $storefrontAssistantPageContextSanitizer->sanitizePageContext([
            StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => StorefrontAssistantPageType::Default->value,
        ]);

        // Assert
        $this->assertNull($sanitizedPageContext);
    }

    public function testGivenOverlongValuesAndNonStringsWhenSanitizedThenValuesAreTrimmedCutAndNonStringsDropped(): void
    {
        // Arrange
        $storefrontAssistantPageContextSanitizer = (new AiCommerceFactory())->createStorefrontAssistantPageContextSanitizer();

        // Act
        $sanitizedPageContext = $storefrontAssistantPageContextSanitizer->sanitizePageContext([
            StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => StorefrontAssistantPageType::Product->value,
            StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_NAME => '  ' . str_repeat('a', 200),
            StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_SKU => ['sku'],
        ]);

        // Assert
        $this->assertSame(
            [
                StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => StorefrontAssistantPageType::Product->value,
                StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_NAME => str_repeat('a', static::MAX_VALUE_LENGTH),
                StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_SKU => '',
                StorefrontAssistantPageContextSanitizer::KEY_CATEGORY_NAME => '',
                StorefrontAssistantPageContextSanitizer::KEY_SEARCH_QUERY => '',
                StorefrontAssistantPageContextSanitizer::KEY_FILTERS => [],
            ],
            $sanitizedPageContext,
        );
    }

    public function testGivenDuplicateBlankAndTooManyFiltersWhenSanitizedThenFiltersAreDeduplicatedAndCapped(): void
    {
        // Arrange
        $storefrontAssistantPageContextSanitizer = (new AiCommerceFactory())->createStorefrontAssistantPageContextSanitizer();
        $filters = array_merge(['brand: Sony', 'brand: Sony', ' ', ['nested']], $this->createColorFilters(20));

        // Act
        $sanitizedPageContext = $storefrontAssistantPageContextSanitizer->sanitizePageContext([
            StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => StorefrontAssistantPageType::Search->value,
            StorefrontAssistantPageContextSanitizer::KEY_FILTERS => $filters,
        ]);

        // Assert
        $this->assertSame(
            [
                StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => StorefrontAssistantPageType::Search->value,
                StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_NAME => '',
                StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_SKU => '',
                StorefrontAssistantPageContextSanitizer::KEY_CATEGORY_NAME => '',
                StorefrontAssistantPageContextSanitizer::KEY_SEARCH_QUERY => '',
                StorefrontAssistantPageContextSanitizer::KEY_FILTERS => array_merge(['brand: Sony'], $this->createColorFilters(static::MAX_FILTER_COUNT - 1)),
            ],
            $sanitizedPageContext,
        );
    }

    /**
     * @return list<string>
     */
    protected function createColorFilters(int $count): array
    {
        return array_map(static fn (int $index): string => sprintf('color: %d', $index), range(1, $count));
    }
}
