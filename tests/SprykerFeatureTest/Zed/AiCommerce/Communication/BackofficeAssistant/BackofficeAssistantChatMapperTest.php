<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\BackofficeAssistant;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use SprykerFeature\Shared\AiCommerce\Conversation\RegeneratedPromptMarker;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat\BackofficeAssistantChatMapper;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptContextFormatter;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group BackofficeAssistant
 * @group BackofficeAssistantChatMapperTest
 */
class BackofficeAssistantChatMapperTest extends Unit
{
    protected const string MESSAGE = 'Summarize this order';

    protected const string CURRENT_PAGE = '/sales/detail?id-sales-order=1';

    protected const int ID_USER = 1;

    protected const string TYPE_USER = 'user';

    protected const string TYPE_ASSISTANT = 'assistant';

    protected const string ROLE_USER = 'user';

    protected const string ROLE_ASSISTANT = 'ai';

    public function testGivenAMessageWhenMappingThePayloadThenThePromptIsPrefixedWithTheCurrentDate(): void
    {
        // Act
        $backofficeAssistantPromptRequestTransfer = $this->createMapper()->mapPayloadToBackofficeAssistantPromptRequestTransfer(
            ['message' => static::MESSAGE],
            static::CURRENT_PAGE,
            static::ID_USER,
        );

        // Assert
        $this->assertMatchesRegularExpression(
            '/^\[Date: [A-Z][a-z]+, \d{4}-\d{2}-\d{2}\]\n\n' . preg_quote(static::MESSAGE, '/') . '$/',
            (string)$backofficeAssistantPromptRequestTransfer->getPrompt(),
        );
    }

    public function testGivenAStoredPromptWhenMappingMessagesThenThePromptIsShownWithoutTheDate(): void
    {
        // Arrange
        $mapper = $this->createMapper();
        $prompt = (string)$mapper->mapPayloadToBackofficeAssistantPromptRequestTransfer(['message' => static::MESSAGE], '', static::ID_USER)->getPrompt();

        // Act
        $messages = $mapper->mapBackofficeAssistantConversationToMessagesArray(
            $this->createConversation([[static::TYPE_USER, $prompt], [static::TYPE_ASSISTANT, 'Answer']]),
        );

        // Assert
        $this->assertSame(static::MESSAGE, $messages['messages'][0]['content']);
    }

    public function testGivenARegeneratedPromptWhenMappingMessagesThenOnlyTheNewAnswerIsShown(): void
    {
        // Arrange
        $mapper = $this->createMapper();
        $prompt = (string)$mapper->mapPayloadToBackofficeAssistantPromptRequestTransfer(['message' => static::MESSAGE], '', static::ID_USER)->getPrompt();
        $regeneratedPrompt = (string)$mapper->mapPayloadToBackofficeAssistantPromptRequestTransfer(
            ['message' => static::MESSAGE, RegeneratedPromptMarker::PAYLOAD_KEY_REGENERATE => true],
            '',
            static::ID_USER,
        )->getPrompt();

        // Act
        $messages = $mapper->mapBackofficeAssistantConversationToMessagesArray($this->createConversation([
            [static::TYPE_USER, $prompt],
            [static::TYPE_ASSISTANT, 'First answer'],
            [static::TYPE_USER, $regeneratedPrompt],
            [static::TYPE_ASSISTANT, 'Second answer'],
        ]));

        // Assert
        $this->assertSame(
            [[static::ROLE_USER, static::MESSAGE], [static::ROLE_ASSISTANT, 'Second answer']],
            array_map(static fn (array $message): array => [$message['role'], $message['content']], $messages['messages']),
        );
    }

    public function testGivenDuplicateConversationReferencesWhenMappingTheDeletePayloadThenEachReferenceIsKeptOnceForTheUser(): void
    {
        // Act
        $backofficeAssistantConversationCollectionDeleteCriteriaTransfer = $this->createMapper()
            ->mapPayloadToBackofficeAssistantConversationCollectionDeleteCriteriaTransfer(
                ['conversationReferences' => ['first', 'second', 'first']],
                static::ID_USER,
            );

        // Assert
        $this->assertSame(['first', 'second'], $backofficeAssistantConversationCollectionDeleteCriteriaTransfer->getConversationReferences());
        $this->assertSame(static::ID_USER, $backofficeAssistantConversationCollectionDeleteCriteriaTransfer->getIdUser());
    }

    protected function createMapper(): BackofficeAssistantChatMapper
    {
        $marker = new RegeneratedPromptMarker();

        return new BackofficeAssistantChatMapper(new PromptContextFormatter($marker), $marker);
    }

    /**
     * @param list<array{string, string}> $messages
     */
    protected function createConversation(array $messages): BackofficeAssistantConversationTransfer
    {
        $promptMessageTransfers = array_map(
            static fn (array $message): PromptMessageTransfer => (new PromptMessageTransfer())->setType($message[0])->setContent($message[1]),
            $messages,
        );

        return (new BackofficeAssistantConversationTransfer())->setMessages(new ArrayObject($promptMessageTransfers));
    }
}
