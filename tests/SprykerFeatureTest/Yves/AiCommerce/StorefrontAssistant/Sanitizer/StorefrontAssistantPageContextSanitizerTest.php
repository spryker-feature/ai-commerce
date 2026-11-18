<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Sanitizer;

use Codeception\Stub;
use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\AiCommerceFactory;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Sanitizer\StorefrontAssistantPageContextSanitizer;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Sanitizer\StorefrontAssistantPageContextSanitizerInterface;

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

    protected const string CUSTOM_PAGE_TYPE = 'machine';

    protected const string CUSTOM_FIELD_REFERENCE = 'machineReference';

    protected const string CUSTOM_FIELD_NAME = 'machineName';

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
                StorefrontAssistantPageContextSanitizer::KEY_CUSTOM_FIELDS => [],
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
                StorefrontAssistantPageContextSanitizer::KEY_CUSTOM_FIELDS => [],
            ],
            $sanitizedPageContext,
        );
    }

    public function testGivenAConfiguredProjectPageTypeWhenSanitizedThenOnlyItsConfiguredFieldsAreKeptAsCustomFields(): void
    {
        // Arrange
        $storefrontAssistantPageContextSanitizer = $this->createSanitizerWithCustomPageContextFields([
            static::CUSTOM_PAGE_TYPE => [static::CUSTOM_FIELD_REFERENCE, static::CUSTOM_FIELD_NAME],
        ]);

        // Act
        $sanitizedPageContext = $storefrontAssistantPageContextSanitizer->sanitizePageContext([
            StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => static::CUSTOM_PAGE_TYPE,
            static::CUSTOM_FIELD_REFERENCE => ' REF--3 ',
            static::CUSTOM_FIELD_NAME => ['nested'],
            'unconfiguredField' => 'dropped',
        ]);

        // Assert
        $this->assertSame(static::CUSTOM_PAGE_TYPE, $sanitizedPageContext[StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE]);
        $this->assertSame(
            [static::CUSTOM_FIELD_REFERENCE => 'REF--3', static::CUSTOM_FIELD_NAME => ''],
            $sanitizedPageContext[StorefrontAssistantPageContextSanitizer::KEY_CUSTOM_FIELDS],
        );
    }

    public function testGivenAPageTypeThatNoConfigurationNamesWhenSanitizedThenNoContextIsReturned(): void
    {
        // Arrange
        $storefrontAssistantPageContextSanitizer = $this->createSanitizerWithCustomPageContextFields([]);

        // Act
        $sanitizedPageContext = $storefrontAssistantPageContextSanitizer->sanitizePageContext([
            StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => static::CUSTOM_PAGE_TYPE,
            static::CUSTOM_FIELD_REFERENCE => 'REF--3',
        ]);

        // Assert
        $this->assertNull($sanitizedPageContext);
    }

    public function testGivenACorePageTypeInTheProjectConfigurationWhenSanitizedThenItKeepsTheCoreBehavior(): void
    {
        // Arrange
        $storefrontAssistantPageContextSanitizer = $this->createSanitizerWithCustomPageContextFields([
            StorefrontAssistantPageType::Default->value => [static::CUSTOM_FIELD_REFERENCE],
        ]);

        // Act
        $sanitizedPageContext = $storefrontAssistantPageContextSanitizer->sanitizePageContext([
            StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE => StorefrontAssistantPageType::Default->value,
            static::CUSTOM_FIELD_REFERENCE => 'REF--3',
        ]);

        // Assert
        $this->assertNull($sanitizedPageContext);
    }

    /**
     * @param array<string, list<string>> $customPageContextFields
     */
    protected function createSanitizerWithCustomPageContextFields(array $customPageContextFields): StorefrontAssistantPageContextSanitizerInterface
    {
        /**
         * @var \SprykerFeature\Yves\AiCommerce\AiCommerceConfig $aiCommerceConfig
         */
        $aiCommerceConfig = Stub::make(AiCommerceConfig::class, [
            'getStorefrontAssistantCustomPageContextFields' => $customPageContextFields,
        ]);

        $aiCommerceFactory = new AiCommerceFactory();
        $aiCommerceFactory->setConfig($aiCommerceConfig);

        return $aiCommerceFactory->createStorefrontAssistantPageContextSanitizer();
    }

    /**
     * @return list<string>
     */
    protected function createColorFilters(int $count): array
    {
        return array_map(static fn (int $index): string => sprintf('color: %d', $index), range(1, $count));
    }
}
