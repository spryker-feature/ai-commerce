<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Conversation;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationMessageExtractor;
use SprykerFeature\Shared\AiCommerce\Conversation\RegeneratedPromptMarker;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantMessageRole;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Conversation
 * @group ConversationMessageExtractorTest
 */
class ConversationMessageExtractorTest extends Unit
{
    protected const string TYPE_USER = 'user';

    protected const string TYPE_ASSISTANT = 'assistant';

    protected const string QUESTION = 'Which camera is the cheapest?';

    protected const string OTHER_QUESTION = 'And the most expensive?';

    public function testGivenTheLastQuestionWasRegeneratedWhenExtractingThenOnlyTheNewAnswerIsShown(): void
    {
        // Arrange
        $marker = new RegeneratedPromptMarker();
        $conversationHistoryTransfer = $this->createConversationHistory([
            [static::TYPE_USER, static::QUESTION],
            [static::TYPE_ASSISTANT, 'First answer'],
            [static::TYPE_USER, $marker->addMarker(static::QUESTION)],
            [static::TYPE_ASSISTANT, 'Second answer'],
        ]);

        // Act
        $messages = $this->createExtractor()->extractConversationMessagesFromConversationHistory($conversationHistoryTransfer);

        // Assert
        $this->assertSame(
            [[StorefrontAssistantMessageRole::User->value, static::QUESTION], [StorefrontAssistantMessageRole::Assistant->value, 'Second answer']],
            $this->readRolesAndContents($messages),
        );
    }

    public function testGivenAnEarlierQuestionWasRegeneratedWhenExtractingThenTheLaterExchangesAreDropped(): void
    {
        // Arrange
        $marker = new RegeneratedPromptMarker();
        $conversationHistoryTransfer = $this->createConversationHistory([
            [static::TYPE_USER, static::QUESTION],
            [static::TYPE_ASSISTANT, 'First answer'],
            [static::TYPE_USER, static::OTHER_QUESTION],
            [static::TYPE_ASSISTANT, 'Answer to the other question'],
            [static::TYPE_USER, $marker->addMarker(static::QUESTION)],
            [static::TYPE_ASSISTANT, 'Second answer'],
        ]);

        // Act
        $messages = $this->createExtractor()->extractConversationMessagesFromConversationHistory($conversationHistoryTransfer);

        // Assert
        $this->assertSame(
            [[StorefrontAssistantMessageRole::User->value, static::QUESTION], [StorefrontAssistantMessageRole::Assistant->value, 'Second answer']],
            $this->readRolesAndContents($messages),
        );
    }

    public function testGivenTheCustomerAskedAQuestionTwiceWithoutRegeneratingWhenExtractingThenBothExchangesAreKept(): void
    {
        // Arrange
        $conversationHistoryTransfer = $this->createConversationHistory([
            [static::TYPE_USER, static::QUESTION],
            [static::TYPE_ASSISTANT, 'First answer'],
            [static::TYPE_USER, static::QUESTION],
            [static::TYPE_ASSISTANT, 'Second answer'],
        ]);

        // Act
        $messages = $this->createExtractor()->extractConversationMessagesFromConversationHistory($conversationHistoryTransfer);

        // Assert
        $this->assertCount(4, $messages);
    }

    protected function createExtractor(): ConversationMessageExtractor
    {
        return new ConversationMessageExtractor(new RegeneratedPromptMarker());
    }

    /**
     * @param list<array{string, string}> $messages
     */
    protected function createConversationHistory(array $messages): ConversationHistoryTransfer
    {
        $promptMessageTransfers = array_map(
            static fn (array $message): PromptMessageTransfer => (new PromptMessageTransfer())->setType($message[0])->setContent($message[1]),
            $messages,
        );

        return (new ConversationHistoryTransfer())->setMessages(new ArrayObject($promptMessageTransfers));
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\StorefrontAssistantConversationMessageTransfer> $storefrontAssistantConversationMessageTransfers
     *
     * @return list<array{string, string}>
     */
    protected function readRolesAndContents(array $storefrontAssistantConversationMessageTransfers): array
    {
        return array_values(array_map(
            static fn ($storefrontAssistantConversationMessageTransfer): array => [
                (string)$storefrontAssistantConversationMessageTransfer->getRole(),
                (string)$storefrontAssistantConversationMessageTransfer->getContent(),
            ],
            $storefrontAssistantConversationMessageTransfers,
        ));
    }
}
