<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\Plugin\StorefrontAssistant\Tool;

use Codeception\Test\Unit;
use Spryker\Client\AiFoundation\Dependency\Tools\ToolParameterInterface;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\OfferChoicesToolPlugin;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group Plugin
 * @group StorefrontAssistant
 * @group Tool
 * @group OfferChoicesToolPluginTest
 */
class OfferChoicesToolPluginTest extends Unit
{
    protected const string PARAMETER_QUESTION = 'question';

    protected const string PARAMETER_CHOICES = 'choices';

    protected const string KEY_ERROR = 'error';

    protected const string QUESTION = 'Who is the gift for?';

    protected const string QUESTION_INTERESTS = 'What are they into?';

    protected const int MAX_DESCRIPTION_LENGTH = 600;

    protected const string GUIDANCE_SKIP_ANSWERED = 'skipping any already answered';

    protected const string GUIDANCE_STATED_BUDGET = 'a stated one is `priceMin`/`priceMax` of every search';

    protected AiCommerceClientTester $tester;

    public function testGivenChoicesWhenExecutedThenAtMostSixSanitizedChoicesAreReturned(): void
    {
        // Arrange
        $choices = ['<b>Partner</b>', 'My [dad]', str_repeat('a', 50), 'Mum', 'Friend', 'Colleague', 'Child', 'Myself'];

        // Act
        $result = (new OfferChoicesToolPlugin())->execute([static::PARAMETER_QUESTION => '<i>' . static::QUESTION . '</i>', static::PARAMETER_CHOICES => $choices]);

        // Assert
        $this->assertSame(static::QUESTION, $result[static::PARAMETER_QUESTION]);
        $this->assertSame(['Partner', 'My dad', str_repeat('a', 39) . '…', 'Mum', 'Friend', 'Colleague'], $result[static::PARAMETER_CHOICES]);
    }

    public function testGivenDuplicateOrEmptyChoicesWhenExecutedThenTheyAreDropped(): void
    {
        // Arrange
        $choices = ['Running', ' running ', '', '<br>', 42, 'Cooking'];

        // Act
        $result = (new OfferChoicesToolPlugin())->execute([static::PARAMETER_QUESTION => static::QUESTION, static::PARAMETER_CHOICES => $choices]);

        // Assert
        $this->assertSame(['Running', '42', 'Cooking'], $result[static::PARAMETER_CHOICES]);
    }

    public function testGivenFewerThanTwoUsableChoicesWhenExecutedThenNothingIsShownAndAnErrorIsReturned(): void
    {
        // Arrange
        $choices = ['Running', 'RUNNING', ''];

        // Act
        $result = (new OfferChoicesToolPlugin())->execute([static::PARAMETER_QUESTION => static::QUESTION, static::PARAMETER_CHOICES => $choices]);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($result));
    }

    public function testGivenAQuestionAlreadyOfferedInTheTurnWhenExecutedAgainThenNothingIsShownAndAnErrorIsReturned(): void
    {
        // Arrange
        (new OfferChoicesToolPlugin())->execute([static::PARAMETER_QUESTION => static::QUESTION, static::PARAMETER_CHOICES => ['Partner', 'Friend']]);

        // Act
        $result = (new OfferChoicesToolPlugin())->execute([static::PARAMETER_QUESTION => static::QUESTION_INTERESTS, static::PARAMETER_CHOICES => ['Sports', 'Music']]);

        // Assert
        $this->assertSame([static::KEY_ERROR], array_keys($result));
    }

    public function testGivenThePluginWhenItsContractIsReadThenItExposesTheToolContract(): void
    {
        // Arrange
        $offerChoicesToolPlugin = new OfferChoicesToolPlugin();

        // Act
        $requiredParameterNames = array_map(
            fn (ToolParameterInterface $toolParameter): string => $toolParameter->getName(),
            array_filter($offerChoicesToolPlugin->getParameters(), fn (ToolParameterInterface $toolParameter): bool => $toolParameter->isRequired()),
        );

        // Assert
        $this->assertSame(OfferChoicesToolPlugin::TOOL_NAME, $offerChoicesToolPlugin->getName());
        $this->assertSame([static::PARAMETER_QUESTION, static::PARAMETER_CHOICES], array_values($requiredParameterNames));
        $this->assertLessThanOrEqual(static::MAX_DESCRIPTION_LENGTH, mb_strlen($offerChoicesToolPlugin->getDescription()));
    }

    public function testGivenTheGiftGuidanceWhenReadThenItSkipsAnsweredQuestionsAndKeepsAStatedBudgetOnEverySearch(): void
    {
        // Arrange
        $offerChoicesToolPlugin = new OfferChoicesToolPlugin();

        // Act
        $description = $offerChoicesToolPlugin->getDescription();

        // Assert
        $this->assertStringContainsString(static::GUIDANCE_SKIP_ANSWERED, $description);
        $this->assertStringContainsString(static::GUIDANCE_STATED_BUDGET, $description);
    }
}
