<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group ProductSetsToolPluginTest
 */
class ProductSetsToolPluginTest extends Unit
{
    protected const string KEY_SETS = 'sets';

    protected const string KEY_ID_PRODUCT_SET = 'idProductSet';

    protected const string KEY_NAME = 'name';

    protected const string KEY_URL = 'url';

    protected const string KEY_ITEMS = 'items';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    /**
     * @var array<int, array{0: string, 1: string, 2: list<int>}>
     */
    protected const array PRODUCT_SETS = [
        1 => ['HP Product Set', '/en/hp-product-set', [167, 170]],
        2 => ['Sony Product Set', '/en/sony-product-set', [190, 191]],
        3 => ['TomTom Runner Product Set', '/en/tomtom-runner-product-set', [180, 181]],
    ];

    protected AiCommerceClientTester $tester;

    public function testGivenThreePublishedSetsWhenListedThenEverySetIsReturnedWithItsItemsAndUrl(): void
    {
        // Arrange
        $this->haveProductSets(static::PRODUCT_SETS);
        $this->haveProducts([167, 170, 190, 191, 180, 181]);

        // Act
        $result = $this->tester->executeProductSetsTool();

        // Assert
        $this->assertSame($this->buildExpectedSets(static::PRODUCT_SETS), $result[static::KEY_SETS]);
    }

    public function testGivenSetWithUnresolvableItemWhenListedThenOnlyResolvedItemsAreReturned(): void
    {
        // Arrange
        $this->haveProductSets([1 => ['HP Product Set', '/en/hp-product-set', [167, 170, 122]]]);
        $this->haveProducts([167, 170]);

        // Act
        $result = $this->tester->executeProductSetsTool();

        // Assert
        $this->assertSame([167, 170], array_column($result[static::KEY_SETS][0][static::KEY_ITEMS], static::KEY_ID_PRODUCT_ABSTRACT));
    }

    public function testGivenNoSetsWhenListedThenAnEmptyListIsReturned(): void
    {
        // Arrange
        $this->haveProductSets([]);
        $this->haveProducts([]);

        // Act
        $result = $this->tester->executeProductSetsTool();

        // Assert
        $this->assertSame([static::KEY_SETS => []], $result);
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: list<int>}> $productSets
     */
    protected function haveProductSets(array $productSets): void
    {
        $productSetDataStorageTransfers = [];

        foreach ($productSets as $idProductSet => [$name, $url, $productAbstractIds]) {
            $productSetDataStorageTransfers[] = $this->tester->createProductSetDataStorage($idProductSet, $name, $url, $productAbstractIds);
        }

        $this->tester->haveProductSetPageSearchClientReturning($productSetDataStorageTransfers);
    }

    /**
     * @param list<int> $productAbstractIds
     */
    protected function haveProducts(array $productAbstractIds): void
    {
        $productAbstractStorageDataById = [];

        foreach ($productAbstractIds as $idProductAbstract) {
            $productAbstractStorageDataById[$idProductAbstract] = $this->tester->createProductAbstractStorageData(
                $idProductAbstract,
                (string)$idProductAbstract,
                $this->getProductName($idProductAbstract),
            ) + [static::KEY_URL => $this->getProductUrl($idProductAbstract)];
        }

        $this->tester->haveProductStorageClientReturning($productAbstractStorageDataById);
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: list<int>}> $productSets
     *
     * @return list<array<string, mixed>>
     */
    protected function buildExpectedSets(array $productSets): array
    {
        $expectedSets = [];

        foreach ($productSets as $idProductSet => [$name, $url, $productAbstractIds]) {
            $expectedSets[] = [
                static::KEY_ID_PRODUCT_SET => $idProductSet,
                static::KEY_NAME => $name,
                static::KEY_URL => $url,
                static::KEY_ITEMS => array_map(fn (int $idProductAbstract): array => [
                    static::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract,
                    static::KEY_NAME => $this->getProductName($idProductAbstract),
                    static::KEY_URL => $this->getProductUrl($idProductAbstract),
                ], $productAbstractIds),
            ];
        }

        return $expectedSets;
    }

    protected function getProductName(int $idProductAbstract): string
    {
        return sprintf('Product %d', $idProductAbstract);
    }

    protected function getProductUrl(int $idProductAbstract): string
    {
        return sprintf('/en/product-%d', $idProductAbstract);
    }
}
