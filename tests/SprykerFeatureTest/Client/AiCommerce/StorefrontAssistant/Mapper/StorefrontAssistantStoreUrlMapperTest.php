<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Mapper;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantStoreUrlMapper;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Mapper
 * @group StorefrontAssistantStoreUrlMapperTest
 */
class StorefrontAssistantStoreUrlMapperTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    /**
     * @dataProvider provideUrlsWithStoreRouting
     */
    public function testGivenStoreRoutingWhenUrlIsMappedThenOnlyRootRelativePathsWithoutTheStorePrefixGainIt(string $url, string $expectedUrl): void
    {
        // Arrange
        $storefrontAssistantStoreUrlMapper = new StorefrontAssistantStoreUrlMapper(
            Stub::makeEmpty(StoreClientInterface::class, [
                'getCurrentStore' => (new StoreTransfer())->setName(static::STORE_NAME),
            ]),
            true,
        );

        // Act
        $storeUrl = $storefrontAssistantStoreUrlMapper->mapUrlToStoreUrl($url);

        // Assert
        $this->assertSame($expectedUrl, $storeUrl);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function provideUrlsWithStoreRouting(): array
    {
        return [
            'storage url' => ['/en/highefficiencyheatpump45kw', '/DE/en/highefficiencyheatpump45kw'],
            'url with query' => ['/product-comparison?skus=a,b', '/DE/product-comparison?skus=a,b'],
            'url with the store prefix' => ['/DE/en/gtc', '/DE/en/gtc'],
            'store prefix only' => ['/DE', '/DE'],
            'url starting like the store name' => ['/DEALS/en/x', '/DE/DEALS/en/x'],
            'absolute url' => ['https://example.test/en/x', 'https://example.test/en/x'],
            'protocol relative url' => ['//example.test/en/x', '//example.test/en/x'],
            'empty url' => ['', ''],
        ];
    }

    public function testGivenNoStoreRoutingWhenUrlIsMappedThenItIsReturnedUnchanged(): void
    {
        // Arrange
        $storefrontAssistantStoreUrlMapper = new StorefrontAssistantStoreUrlMapper(
            Stub::makeEmpty(StoreClientInterface::class, [
                'getCurrentStore' => Expected::never(),
            ]),
            false,
        );

        // Act
        $storeUrl = $storefrontAssistantStoreUrlMapper->mapUrlToStoreUrl('/en/highefficiencyheatpump45kw');

        // Assert
        $this->assertSame('/en/highefficiencyheatpump45kw', $storeUrl);
    }
}
