<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Widget;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductDetailsToolPlugin;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Widget\StorefrontAssistantWidget;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Widget
 * @group StorefrontAssistantWidgetTest
 */
class StorefrontAssistantWidgetTest extends Unit
{
    protected const string CUSTOMER_REFERENCE = 'DE--TEST-1';

    protected const string CUSTOMER_FIRST_NAME = 'Ada';

    protected const string PROMPT_URL = '/shopping-assistant/prompt';

    protected const string PARAMETER_IS_ENABLED = 'isEnabled';

    protected const string PARAMETER_USER_NAME = 'userName';

    protected const string PARAMETER_PROMPT_URL = 'promptUrl';

    protected const string PARAMETER_ENABLED_AGENTS = 'enabledAgents';

    protected const string PARAMETER_TOOL_NAMES = 'toolNames';

    protected const string PARAMETER_SUGGESTED_PROMPTS = 'suggestedPrompts';

    protected const string PARAMETER_CHAT_OWNER_TOKEN = 'chatOwnerToken';

    /**
     * @var array<int, array<string, string>>
     */
    protected const array ENABLED_AGENTS = [
        ['name' => 'product_discovery', 'description' => 'Finds and compares products.'],
    ];

    protected AiCommerceYvesTester $tester;

    public function testGivenEnabledAndAuthenticatedCustomerWhenBuiltThenUserNameIsFirstName(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setFirstName(static::CUSTOMER_FIRST_NAME);

        // Act
        $widget = $this->createWidget(true, $customerTransfer);

        // Assert
        $this->assertTrue($widget->getParameters()[static::PARAMETER_IS_ENABLED]);
        $this->assertSame(static::CUSTOMER_FIRST_NAME, $widget->getParameters()[static::PARAMETER_USER_NAME]);
        $this->assertSame(static::PROMPT_URL, $widget->getParameters()[static::PARAMETER_PROMPT_URL]);
    }

    public function testGivenEnabledWhenBuiltThenEnabledAgentsParameterIsTheStructuredAgentList(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setFirstName(static::CUSTOMER_FIRST_NAME);

        // Act
        $widget = $this->createWidget(true, $customerTransfer);

        // Assert
        $this->assertSame(
            static::ENABLED_AGENTS,
            $widget->getParameters()[static::PARAMETER_ENABLED_AGENTS],
        );
    }

    public function testGivenDisabledWhenBuiltThenEnabledAgentsParameterIsAnEmptyList(): void
    {
        // Act
        $widget = $this->createWidget(false, new CustomerTransfer());

        // Assert
        $this->assertSame([], $widget->getParameters()[static::PARAMETER_ENABLED_AGENTS]);
    }

    public function testGivenDisabledWhenBuiltThenSuggestedPromptsParameterIsAnEmptyMap(): void
    {
        // Act
        $widget = $this->createWidget(false, new CustomerTransfer());

        // Assert
        $this->assertSame([], $widget->getParameters()[static::PARAMETER_SUGGESTED_PROMPTS]);
    }

    public function testGivenEnabledWhenBuiltThenSuggestedPromptsParameterHoldsEveryPromptGroup(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE);

        // Act
        $parameters = $this->createWidget(true, $customerTransfer)->getParameters();

        // Assert
        $this->assertArrayHasKey(StorefrontAssistantPageType::Default->value, $parameters[static::PARAMETER_SUGGESTED_PROMPTS]);
    }

    public function testGivenDisabledWhenBuiltThenUserNameIsEmptyAndPromptUrlIsEmpty(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setFirstName(static::CUSTOMER_FIRST_NAME);

        // Act
        $widget = $this->createWidget(false, $customerTransfer);

        // Assert
        $this->assertFalse($widget->getParameters()[static::PARAMETER_IS_ENABLED]);
        $this->assertSame('', $widget->getParameters()[static::PARAMETER_USER_NAME]);
        $this->assertSame('', $widget->getParameters()[static::PARAMETER_PROMPT_URL]);
    }

    public function testGivenAuthenticatedCustomerWithoutReferenceWhenBuiltThenWidgetIsDisabledAndUserNameEmpty(): void
    {
        // Act
        $widget = $this->createWidget(true, new CustomerTransfer());

        // Assert
        $this->assertFalse($widget->getParameters()[static::PARAMETER_IS_ENABLED]);
        $this->assertSame('', $widget->getParameters()[static::PARAMETER_USER_NAME]);
    }

    public function testGivenNoAuthenticatedCustomerWhenBuiltThenWidgetIsDisabledAndUserNameEmpty(): void
    {
        // Act
        $widget = $this->createWidget(true, null);

        // Assert
        $this->assertFalse($widget->getParameters()[static::PARAMETER_IS_ENABLED]);
        $this->assertSame('', $widget->getParameters()[static::PARAMETER_USER_NAME]);
    }

    public function testGivenWidgetWhenBuiltThenDiscoveryToolNamesArePublishedForTheirLabels(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE);

        // Act
        $parameters = $this->createWidget(true, $customerTransfer)->getParameters();

        // Assert
        $this->assertContains(ProductDetailsToolPlugin::TOOL_NAME, $parameters[static::PARAMETER_TOOL_NAMES]);
    }

    public function testGivenNoEnabledAgentWhenBuiltThenWidgetIsDisabled(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setFirstName(static::CUSTOMER_FIRST_NAME);

        // Act
        $parameters = $this->createWidget(true, $customerTransfer, [])->getParameters();

        // Assert
        $this->assertFalse($parameters[static::PARAMETER_IS_ENABLED]);
        $this->assertSame('', $parameters[static::PARAMETER_PROMPT_URL]);
        $this->assertSame([], $parameters[static::PARAMETER_ENABLED_AGENTS]);
    }

    public function testGivenAuthenticatedCustomerWhenBuiltThenChatOwnerTokenIsTheHashedCustomerReference(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE);

        // Act
        $parameters = $this->createWidget(true, $customerTransfer)->getParameters();

        // Assert
        $this->assertSame(hash('sha256', static::CUSTOMER_REFERENCE), $parameters[static::PARAMETER_CHAT_OWNER_TOKEN]);
        $this->assertStringNotContainsString(static::CUSTOMER_REFERENCE, $parameters[static::PARAMETER_CHAT_OWNER_TOKEN]);
    }

    public function testGivenGuestWhenBuiltThenChatOwnerTokenIsEmpty(): void
    {
        // Act
        $parameters = $this->createWidget(true, null)->getParameters();

        // Assert
        $this->assertFalse($parameters[static::PARAMETER_IS_ENABLED]);
        $this->assertSame('', $parameters[static::PARAMETER_CHAT_OWNER_TOKEN]);
    }

    /**
     * @param array<int, array<string, string>> $enabledAgents
     */
    protected function createWidget(
        bool $isEnabled,
        ?CustomerTransfer $customerTransfer,
        array $enabledAgents = self::ENABLED_AGENTS
    ): StorefrontAssistantWidget {
        return $this->tester->createStorefrontAssistantWidget(
            $isEnabled,
            $customerTransfer,
            static::PROMPT_URL,
            $enabledAgents,
        );
    }
}
