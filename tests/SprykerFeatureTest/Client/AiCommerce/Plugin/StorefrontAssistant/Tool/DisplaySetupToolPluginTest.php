<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplaySetupToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group DisplaySetupToolPluginTest
 */
class DisplaySetupToolPluginTest extends Unit
{
    protected const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    protected const string PARAMETER_BUDGET = 'budget';

    protected const string PARAMETER_REASONS = 'reasons';

    protected const string KEY_REASON = 'reason';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_ITEMS = 'items';

    protected const string KEY_ERROR = 'error';

    protected const string KEY_TOTAL_FORMATTED = 'totalFormatted';

    protected const string KEY_BUDGET_FORMATTED = 'budgetFormatted';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string KEY_PRICE = 'price';

    protected const string KEY_PRICE_FORMATTED = 'priceFormatted';

    protected const string KEY_IMAGE_URL = 'imageUrl';

    protected const int PRICE_PRECISION = 100;

    protected const int MAX_DESCRIPTION_LENGTH = 600;

    protected AiCommerceClientTester $tester;

    public function testGivenProductsWithinBudgetWhenExecutedThenTotalIsAtMostBudgetAndEqualsTheSumOfTheCardPrices(): void
    {
        // Arrange
        $pricesByIdProductAbstract = $this->tester->getPricedProductAbstractsResolvableByExpanders(2);
        $total = array_sum($pricesByIdProductAbstract);
        $budget = (int)ceil($total / static::PRICE_PRECISION) + 1;

        // Act
        $result = $this->tester->executeDisplaySetupTool(array_keys($pricesByIdProductAbstract), $budget);
        $cards = $this->tester->enrichToolResultProducts($result);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_ERROR, $result);
        $this->assertSame(array_keys($pricesByIdProductAbstract), array_column($result[static::KEY_PRODUCTS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertLessThanOrEqual($budget * static::PRICE_PRECISION, $total);
        $this->assertSame($total, array_sum(array_column($cards, static::KEY_PRICE)));
        $this->assertSame($this->tester->formatAmountInCurrentCurrency($total), $result[static::KEY_TOTAL_FORMATTED]);
        $this->assertSame($this->tester->formatAmountInCurrentCurrency($budget * static::PRICE_PRECISION), $result[static::KEY_BUDGET_FORMATTED]);
        $this->assertNotSame('', $cards[0][static::KEY_PRICE_FORMATTED] ?? '');
    }

    public function testGivenProductsWithinBudgetWhenExecutedThenTheAgentDoesNotReceiveCardOnlyData(): void
    {
        // Arrange
        $pricesByIdProductAbstract = $this->tester->getPricedProductAbstractsResolvableByExpanders(2);
        $budget = (int)ceil(array_sum($pricesByIdProductAbstract) / static::PRICE_PRECISION) + 1;

        // Act
        $result = $this->tester->executeDisplaySetupTool(array_keys($pricesByIdProductAbstract), $budget);

        // Assert
        $this->assertArrayNotHasKey(static::KEY_IMAGE_URL, $result[static::KEY_PRODUCTS][0]);
        $this->assertArrayNotHasKey(static::KEY_PRICE, $result[static::KEY_PRODUCTS][0]);
        $this->assertNotSame('', $result[static::KEY_PRODUCTS][0][static::KEY_PRICE_FORMATTED]);
    }

    public function testGivenReasonsWhenExecutedThenEveryCardCarriesItsReason(): void
    {
        // Arrange
        $pricesByIdProductAbstract = $this->tester->getPricedProductAbstractsResolvableByExpanders(2);
        $budget = (int)ceil(array_sum($pricesByIdProductAbstract) / static::PRICE_PRECISION) + 1;
        $productAbstractIds = array_keys($pricesByIdProductAbstract);
        $reasons = [$productAbstractIds[0] => 'Quiet keyboard', $productAbstractIds[1] => '27" IPS screen'];

        // Act
        $result = $this->tester->executeDisplaySetupTool($productAbstractIds, $budget, $reasons);
        $cards = $this->tester->enrichToolResultProducts($result);

        // Assert
        $this->assertSame(array_values($reasons), array_column($result[static::KEY_PRODUCTS], static::KEY_REASON));
        $this->assertSame(array_values($reasons), array_column($cards, static::KEY_REASON));
    }

    public function testGivenProductsOverBudgetWhenExecutedThenOverBudgetIsReportedAndNoCardIsShown(): void
    {
        // Arrange
        $pricesByIdProductAbstract = $this->tester->getPricedProductAbstractsResolvableByExpanders(2);
        $budget = (int)floor(array_sum($pricesByIdProductAbstract) / static::PRICE_PRECISION) - 1;

        // Act
        $result = $this->tester->executeDisplaySetupTool(array_keys($pricesByIdProductAbstract), $budget);

        // Assert
        $this->assertArrayHasKey(static::KEY_ERROR, $result);
        $this->assertArrayNotHasKey(static::KEY_PRODUCTS, $result);
        $this->assertSame([], $this->tester->enrichToolResultProducts($result));
        $this->assertSame(array_keys($pricesByIdProductAbstract), array_column($result[static::KEY_ITEMS], static::KEY_ID_PRODUCT_ABSTRACT));
        $this->assertStringContainsString($this->tester->formatAmountInCurrentCurrency(array_sum($pricesByIdProductAbstract)), $result[static::KEY_ERROR]);
    }

    public function testGivenFewerThanTwoProductsWhenExecutedThenItIsRejected(): void
    {
        // Arrange
        $pricesByIdProductAbstract = $this->tester->getPricedProductAbstractsResolvableByExpanders(1);

        // Act
        $result = $this->tester->executeDisplaySetupTool(array_keys($pricesByIdProductAbstract), 100000);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($result));
    }

    public function testGivenMoreThanFourProductsWhenExecutedThenItIsRejected(): void
    {
        // Arrange
        $productAbstractIds = [1, 2, 3, 4, 5];

        // Act
        $result = $this->tester->executeDisplaySetupTool($productAbstractIds, 100000);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($result));
    }

    public function testGivenNoBudgetWhenExecutedThenItIsRejected(): void
    {
        // Arrange
        $displaySetupToolPlugin = new DisplaySetupToolPlugin();

        // Act
        $result = $displaySetupToolPlugin->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [1, 2]]);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($result));
    }

    public function testGivenUnknownProductWhenExecutedThenNothingIsShownAndItIsReportedBack(): void
    {
        // Arrange
        $pricesByIdProductAbstract = $this->tester->getPricedProductAbstractsResolvableByExpanders(1);
        $idUnknown = $this->tester->getUnknownIdProductAbstract();

        // Act
        $result = $this->tester->executeDisplaySetupTool([(int)array_key_first($pricesByIdProductAbstract), $idUnknown], 100000);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($result));
        $this->assertStringContainsString((string)$idUnknown, $result[static::KEY_ERROR]);
    }

    public function testGivenCheaperSwapWhenExecutedAgainThenTotalIsLowerAndStillWithinBudget(): void
    {
        // Arrange
        $pricesByIdProductAbstract = $this->tester->getPricedProductAbstractsResolvableByExpanders(3);
        asort($pricesByIdProductAbstract);
        [$idCheapest, $idMiddle, $idPriciest] = array_keys($pricesByIdProductAbstract);
        $budget = (int)ceil(($pricesByIdProductAbstract[$idMiddle] + $pricesByIdProductAbstract[$idPriciest]) / static::PRICE_PRECISION) + 1;
        $firstResult = $this->tester->executeDisplaySetupTool([$idMiddle, $idPriciest], $budget);

        // Act
        $swappedResult = $this->tester->executeDisplaySetupTool([$idMiddle, $idCheapest], $budget);

        // Assert
        $this->assertArrayHasKey(static::KEY_PRODUCTS, $firstResult);
        $this->assertArrayHasKey(static::KEY_PRODUCTS, $swappedResult);
        $this->assertLessThan(
            array_sum(array_column($this->tester->enrichToolResultProducts($firstResult), static::KEY_PRICE)),
            array_sum(array_column($this->tester->enrichToolResultProducts($swappedResult), static::KEY_PRICE)),
        );
        $this->assertSame(
            $this->tester->formatAmountInCurrentCurrency($pricesByIdProductAbstract[$idMiddle] + $pricesByIdProductAbstract[$idCheapest]),
            $swappedResult[static::KEY_TOTAL_FORMATTED],
        );
    }

    public function testGivenThePluginWhenItsContractIsReadThenItExposesTheToolContract(): void
    {
        // Arrange
        $displaySetupToolPlugin = new DisplaySetupToolPlugin();

        // Act
        $requiredParameterNames = array_map(
            fn (ToolParameterInterface $toolParameter): string => $toolParameter->getName(),
            array_filter($displaySetupToolPlugin->getParameters(), fn (ToolParameterInterface $toolParameter): bool => $toolParameter->isRequired()),
        );

        // Assert
        $this->assertSame(DisplaySetupToolPlugin::TOOL_NAME, $displaySetupToolPlugin->getName());
        $this->assertSame([static::PARAMETER_ID_PRODUCT_ABSTRACTS, static::PARAMETER_BUDGET, static::PARAMETER_REASONS], array_values($requiredParameterNames));
        $this->assertLessThanOrEqual(static::MAX_DESCRIPTION_LENGTH, mb_strlen($displaySetupToolPlugin->getDescription()));
    }
}
