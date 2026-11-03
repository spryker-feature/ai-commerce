<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Validator;

use Codeception\Test\Unit;
use SprykerFeature\Client\AiCommerce\AiCommerceClientInterface;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\AiCommerceFactory;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Validator\StorefrontAssistantRequestPayloadValidator;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Validator
 * @group StorefrontAssistantRequestPayloadValidatorTest
 */
class StorefrontAssistantRequestPayloadValidatorTest extends Unit
{
    protected const string AGENT_NAME = 'product_discovery';

    protected const string CONVERSATION_REFERENCE = 'conversation-reference-1';

    protected AiCommerceYvesTester $tester;

    /**
     * @dataProvider provideValidChatPayloads
     *
     * @param array<mixed> $payload
     */
    public function testGivenValidChatPayloadWhenValidatedThenItIsAccepted(array $payload): void
    {
        // Act
        $isValid = $this->createValidator()->isChatPayloadValid($payload);

        // Assert
        $this->assertTrue($isValid);
    }

    /**
     * @return array<string, array<int, array<mixed>>>
     */
    public function provideValidChatPayloads(): array
    {
        return [
            'auto agent' => [['selectedAgent' => null, 'attachments' => []]],
            'empty agent' => [['selectedAgent' => '']],
            'enabled agent' => [['selectedAgent' => static::AGENT_NAME, 'attachments' => []]],
            'no optional keys' => [[]],
        ];
    }

    /**
     * @dataProvider provideInvalidChatPayloads
     *
     * @param array<mixed> $payload
     */
    public function testGivenInvalidChatPayloadWhenValidatedThenItIsRejected(array $payload): void
    {
        // Act
        $isValid = $this->createValidator()->isChatPayloadValid($payload);

        // Assert
        $this->assertFalse($isValid);
    }

    /**
     * @return array<string, array<int, array<mixed>>>
     */
    public function provideInvalidChatPayloads(): array
    {
        return [
            'unknown agent' => [['selectedAgent' => 'unknown_agent']],
            'agent not a string' => [['selectedAgent' => 42]],
            'attachments not an array' => [['attachments' => 'file']],
            'attachments null' => [['attachments' => null]],
        ];
    }

    /**
     * @dataProvider provideValidConversationDeletePayloads
     *
     * @param array<mixed> $payload
     */
    public function testGivenValidConversationDeletePayloadWhenValidatedThenItIsAccepted(array $payload): void
    {
        // Act
        $isValid = $this->createValidator()->isConversationDeletePayloadValid($payload);

        // Assert
        $this->assertTrue($isValid);
    }

    /**
     * @return array<string, array<int, array<mixed>>>
     */
    public function provideValidConversationDeletePayloads(): array
    {
        return [
            'single reference' => [['conversationReference' => static::CONVERSATION_REFERENCE]],
            'reference list' => [['conversationReferences' => [static::CONVERSATION_REFERENCE]]],
            'reference list with non-string' => [['conversationReferences' => [static::CONVERSATION_REFERENCE, 7]]],
        ];
    }

    /**
     * @dataProvider provideInvalidConversationDeletePayloads
     *
     * @param array<mixed> $payload
     */
    public function testGivenInvalidConversationDeletePayloadWhenValidatedThenItIsRejected(array $payload): void
    {
        // Act
        $isValid = $this->createValidator()->isConversationDeletePayloadValid($payload);

        // Assert
        $this->assertFalse($isValid);
    }

    /**
     * @return array<string, array<int, array<mixed>>>
     */
    public function provideInvalidConversationDeletePayloads(): array
    {
        return [
            'empty object' => [[]],
            'empty reference' => [['conversationReference' => '']],
            'empty reference list' => [['conversationReferences' => []]],
            'reference list without valid reference' => [['conversationReferences' => ['', 7, ['x']]]],
        ];
    }

    protected function createValidator(): StorefrontAssistantRequestPayloadValidator
    {
        $aiCommerceClientMock = $this->createMock(AiCommerceClientInterface::class);
        $aiCommerceClientMock->method('getStorefrontAssistantEnabledAgents')
            ->willReturn([['name' => static::AGENT_NAME, 'label' => 'Product Discovery', 'description' => '']]);

        return new StorefrontAssistantRequestPayloadValidator(
            new AiCommerceConfig(),
            $aiCommerceClientMock,
            (new AiCommerceFactory())->createStorefrontAssistantRequestPayloadMapper(),
        );
    }
}
