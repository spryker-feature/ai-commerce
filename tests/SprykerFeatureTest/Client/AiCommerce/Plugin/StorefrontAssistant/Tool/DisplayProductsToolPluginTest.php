<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use Spryker\Client\AiFoundation\Dependency\Tools\ToolPluginInterface;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group DisplayProductsToolPluginTest
 */
class DisplayProductsToolPluginTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_DISPLAYED_COUNT = 'displayedCount';

    protected const string KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS = 'unknownIdProductAbstracts';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string KEY_NAME = 'name';

    protected const string KEY_URL = 'url';

    protected const string KEY_SKU = 'sku';

    protected const string KEY_REASON = 'reason';

    protected const string KEY_PRICE_FORMATTED = 'priceFormatted';

    protected const string PARAMETER_REASONS = 'reasons';

    protected const int MAX_REASON_LENGTH = 60;

    protected const string REASON = '20 MP · 8× zoom';

    protected const string KEY_IS_PRICE_OUTLIER = 'isPriceOutlier';

    protected const string PARAMETER_QUERY = 'query';

    protected const string SAME_NAME = 'Sony Cyber-shot DSC-W800';

    protected const int ID_PRODUCT_ABSTRACT_REGULAR_PRICE = 900016;

    protected const int ID_PRODUCT_ABSTRACT_OUTLIER_PRICE = 900017;

    protected const int REGULAR_PRICE = 8500;

    protected const int OUTLIER_PRICE = 311130;

    protected AiCommerceClientTester $tester;

    public function testGivenPublishedProductWhenExecutedThenItIsResolvedFromRealProductStorage(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idProductAbstract]]);

        // Assert
        $this->assertSame(1, $result[static::KEY_DISPLAYED_COUNT]);
        $this->assertSame($idProductAbstract, $result[static::KEY_PRODUCTS][0][static::KEY_ID_PRODUCT_ABSTRACT]);
        $this->assertNotSame('', $result[static::KEY_PRODUCTS][0]['name']);
    }

    public function testGivenPublishedProductWhenExecutedThenTheAgentReceivesOnlyItsIdentifierNameAndReason(): void
    {
        // Arrange
        $this->tester->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idProductAbstract]]);

        // Assert
        $this->assertSame([static::KEY_ID_PRODUCT_ABSTRACT, static::KEY_NAME, static::KEY_REASON], array_keys($result[static::KEY_PRODUCTS][0]));
    }

    public function testGivenReasonsWhenExecutedThenEachCardCarriesReasonOfAtMostSixtyCharacters(): void
    {
        // Arrange
        $this->tester->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();

        // Act
        $cards = $this->tester->getDisplayedProductCards([$idProductAbstract], [$idProductAbstract => static::REASON]);

        // Assert
        $this->assertSame(static::REASON, $cards[0][static::KEY_REASON]);
        $this->assertLessThanOrEqual(static::MAX_REASON_LENGTH, mb_strlen($cards[0][static::KEY_REASON]));
    }

    public function testGivenReasonLongerThanSixtyCharactersWhenExecutedThenItIsTruncated(): void
    {
        // Arrange
        $this->tester->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([
            static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idProductAbstract],
            static::PARAMETER_REASONS => [(string)$idProductAbstract => str_repeat('Long lasting battery ', 10)],
        ]);

        // Assert
        $reason = $result[static::KEY_PRODUCTS][0][static::KEY_REASON];
        $this->assertSame(static::MAX_REASON_LENGTH, mb_strlen($reason));
        $this->assertStringEndsWith('…', $reason);
    }

    public function testGivenMarkupInReasonWhenExecutedThenTagsAreStripped(): void
    {
        // Arrange
        $this->tester->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([
            static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idProductAbstract],
            static::PARAMETER_REASONS => [$idProductAbstract => "<b>Black</b> \n · <i>sold by</i> Video King"],
        ]);

        // Assert
        $this->assertSame('Black · sold by Video King', $result[static::KEY_PRODUCTS][0][static::KEY_REASON]);
    }

    public function testGivenNoReasonWhenExecutedThenFallbackReasonIsPriceFormatted(): void
    {
        // Arrange
        $this->tester->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();

        // Act
        $cards = $this->tester->getDisplayedProductCards([$idProductAbstract]);

        // Assert
        $this->assertNotSame('', $cards[0][static::KEY_REASON] ?? '');
        $this->assertSame($cards[0][static::KEY_PRICE_FORMATTED] ?? $cards[0][static::KEY_SKU], $cards[0][static::KEY_REASON]);
    }

    public function testGivenDisplayedProductWhenItsResultIsEnrichedForTheCustomerThenTheCardIsComplete(): void
    {
        // Arrange
        $this->tester->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->tester->getIdProductAbstractResolvableByExpanders();

        // Act
        $cards = $this->tester->getDisplayedProductCards([$idProductAbstract]);

        // Assert
        $this->assertCount(1, $cards);
        $this->assertSame($idProductAbstract, $cards[0][static::KEY_ID_PRODUCT_ABSTRACT]);
        $this->assertNotSame('', $cards[0][static::KEY_URL] ?? '');
        $this->assertNotSame('', $cards[0][static::KEY_SKU] ?? '');
    }

    public function testGivenPriceOutlierAmongDisplayedProductsWhenExecutedThenItIsFlagged(): void
    {
        // Arrange
        $this->tester->haveSameNameProductsPricedAt(static::SAME_NAME, [
            static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE => static::REGULAR_PRICE,
            static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE => static::OUTLIER_PRICE,
        ]);

        // Act
        $result = (new DisplayProductsToolPlugin())->execute([
            static::PARAMETER_ID_PRODUCT_ABSTRACTS => [static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE, static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE],
        ]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_IS_PRICE_OUTLIER, $result[static::KEY_PRODUCTS][0]);
        $this->assertTrue($result[static::KEY_PRODUCTS][1][static::KEY_IS_PRICE_OUTLIER]);
    }

    public function testGivenPriceOutlierAmongDisplayedProductsWhenItsResultIsEnrichedForTheCustomerThenOnlyItsCardIsFlagged(): void
    {
        // Arrange
        $this->tester->haveSameNameProductsPricedAt(static::SAME_NAME, [
            static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE => static::REGULAR_PRICE,
            static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE => static::OUTLIER_PRICE,
        ]);

        // Act
        $cards = $this->tester->getDisplayedProductCards([static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE, static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE]);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_IS_PRICE_OUTLIER, $cards[0]);
        $this->assertTrue($cards[1][static::KEY_IS_PRICE_OUTLIER]);
    }

    public function testGivenPriceOutlierFlaggedBySearchThisTurnWhenOnlyItIsDisplayedThenItIsStillFlagged(): void
    {
        // Arrange
        $this->tester->haveSameNameProductsPricedAt(static::SAME_NAME, [
            static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE => static::REGULAR_PRICE,
            static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE => static::OUTLIER_PRICE,
        ]);
        $this->tester->setUpCatalogSearchResult($this->tester->createCatalogSearchResultWithRows([
            static::ID_PRODUCT_ABSTRACT_REGULAR_PRICE,
            static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE,
        ]));
        $this->tester->executeCatalogSearchToolInNewTurn([static::PARAMETER_QUERY => static::SAME_NAME]);

        // Act
        $result = (new DisplayProductsToolPlugin())->execute([
            static::PARAMETER_ID_PRODUCT_ABSTRACTS => [static::ID_PRODUCT_ABSTRACT_OUTLIER_PRICE],
        ]);

        // Assert
        $this->assertTrue($result[static::KEY_PRODUCTS][0][static::KEY_IS_PRICE_OUTLIER]);
    }

    public function testGivenUnknownProductWhenExecutedThenItIsReportedBackInsteadOfDisplayed(): void
    {
        // Arrange
        $this->tester->setUpCurrentStore();
        $unknownIdProductAbstract = $this->tester->getUnknownIdProductAbstract();
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$unknownIdProductAbstract]]);

        // Assert
        $this->assertSame(0, $result[static::KEY_DISPLAYED_COUNT]);
        $this->assertSame([$unknownIdProductAbstract], $result[static::KEY_UNKNOWN_ID_PRODUCT_ABSTRACTS]);
    }

    public function testGivenNoProductsWhenExecutedThenNothingIsDisplayed(): void
    {
        // Arrange
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $result = $displayProductsToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => []]);

        // Assert
        $this->assertSame([], $result[static::KEY_PRODUCTS]);
        $this->assertSame(0, $result[static::KEY_DISPLAYED_COUNT]);
    }

    public function testGivenThePluginWhenItsContractIsReadThenItExposesTheToolContractAiFoundationRegistersItBy(): void
    {
        // Arrange
        $displayProductsToolPlugin = new DisplayProductsToolPlugin();

        // Act
        $parameters = $displayProductsToolPlugin->getParameters();

        // Assert
        $this->assertInstanceOf(ToolPluginInterface::class, $displayProductsToolPlugin);
        $this->assertSame(DisplayProductsToolPlugin::TOOL_NAME, $displayProductsToolPlugin->getName());
        $this->assertNotEmpty($parameters);
    }
}
