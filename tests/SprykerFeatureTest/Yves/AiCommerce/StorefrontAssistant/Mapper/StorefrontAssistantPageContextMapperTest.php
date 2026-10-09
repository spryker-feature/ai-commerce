<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StorefrontAssistantPageContextTransfer;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\AiCommerceFactory;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Mapper
 * @group StorefrontAssistantPageContextMapperTest
 */
class StorefrontAssistantPageContextMapperTest extends Unit
{
    protected const string PRODUCT_NAME = 'Canon IXUS 175';

    protected const string PRODUCT_SKU = '005';

    protected const string FILTER_BRAND_SAMSUNG = 'brand: Samsung';

    protected const int MAX_VALUE_LENGTH = 120;

    public function testGivenPayloadWithServerResolvedFieldsWhenMappedThenTheyAreIgnored(): void
    {
        // Arrange
        $payload = [
            'pageType' => StorefrontAssistantPageType::Product->value,
            'productName' => static::PRODUCT_NAME,
            'productSku' => static::PRODUCT_SKU,
            'idProductAbstract' => 1,
            'productLabels' => ['New'],
        ];

        // Act
        $storefrontAssistantPageContextTransfer = $this->mapPayload($payload);

        // Assert
        $this->assertSame(static::PRODUCT_SKU, $storefrontAssistantPageContextTransfer->getProductSku());
        $this->assertNull($storefrontAssistantPageContextTransfer->getIdProductAbstract());
        $this->assertSame([], $storefrontAssistantPageContextTransfer->getProductLabels());
    }

    public function testGivenNameValueFilterWhenMappedThenItIsKeptVerbatimAndCapped(): void
    {
        // Arrange
        $overlongFilter = 'brand: ' . str_repeat('x', static::MAX_VALUE_LENGTH);
        $payload = [
            'pageType' => StorefrontAssistantPageType::Search->value,
            'searchQuery' => 'samsung',
            'filters' => [static::FILTER_BRAND_SAMSUNG, $overlongFilter],
        ];

        // Act
        $storefrontAssistantPageContextTransfer = $this->mapPayload($payload);

        // Assert
        $this->assertSame(
            [static::FILTER_BRAND_SAMSUNG, mb_substr($overlongFilter, 0, static::MAX_VALUE_LENGTH)],
            $storefrontAssistantPageContextTransfer->getFilters(),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function mapPayload(array $payload): StorefrontAssistantPageContextTransfer
    {
        $aiCommerceFactory = new AiCommerceFactory();
        $sanitizedPageContext = $aiCommerceFactory
            ->createStorefrontAssistantPageContextSanitizer()
            ->sanitizePageContext($payload);

        $this->assertNotNull($sanitizedPageContext);

        return $aiCommerceFactory
            ->createStorefrontAssistantPageContextMapper()
            ->mapPageContextToStorefrontAssistantPageContextTransfer($sanitizedPageContext);
    }
}
