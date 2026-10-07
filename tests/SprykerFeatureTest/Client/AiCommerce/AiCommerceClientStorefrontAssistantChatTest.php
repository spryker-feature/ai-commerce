<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventType;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group AiCommerceClientStorefrontAssistantChatTest
 * Add your own group annotations below this line
 */
class AiCommerceClientStorefrontAssistantChatTest extends Unit
{
    protected const string CONVERSATION_REFERENCE = 'conversation-chat-1';

    protected const string USER_MESSAGE = 'What do you sell?';

    protected const string ASSISTANT_MESSAGE = 'We sell cameras and lenses.';

    protected const string MEDIA_TYPE_PNG = 'image/png';

    protected const string MEDIA_TYPE_SVG = 'image/svg+xml';

    protected const string DATA_PART_TYPE_AGENT = 'data-agent';

    protected const string PROVIDER_ERROR_MESSAGE = 'HTTP 400 error during POST chat/completions: reasoning_effort is not supported';

    protected const string BLANK_VALUE = '   ';

    protected const string ERROR_ATTACHMENT_TOO_LARGE = 'ai_commerce.storefront_assistant.attachment.too_large';

    protected const string ERROR_ATTACHMENT_TOO_MANY = 'ai_commerce.storefront_assistant.attachment.too_many';

    protected const string ERROR_ATTACHMENT_UNSUPPORTED_TYPE = 'ai_commerce.storefront_assistant.attachment.unsupported_type';

    protected const string ERROR_CUSTOMER_REFERENCE_REQUIRED = 'ai_commerce.storefront_assistant.validation.customer_reference_required';

    protected const string ERROR_CONVERSATION_REFERENCE_INVALID = 'ai_commerce.storefront_assistant.validation.conversation_reference_invalid';

    protected const string CONVERSATION_REFERENCE_WITH_DISALLOWED_CHARACTER = 'conversation:chat-1';

    protected const string ERROR_MESSAGE_REQUIRED = 'ai_commerce.storefront_assistant.validation.message_required';

    protected const string ERROR_MESSAGE_TOO_LONG = 'ai_commerce.storefront_assistant.validation.message_too_long';

    protected AiCommerceClientTester $tester;

    public function testGivenEnabledFeatureWhenChatIsExecutedThenTheMessageLifecycleAndAgentPartReachTheCustomerInOrder(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();

        // Act
        $eventTypes = $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertSame(
            [
                StreamEventType::Start->value,
                static::DATA_PART_TYPE_AGENT,
                StreamEventType::Finish->value,
            ],
            $eventTypes,
        );
    }

    public function testGivenDisabledFeatureWhenChatIsExecutedThenOnlyAnErrorPartIsEmitted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant(false);
        $aiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();

        // Act
        $eventTypes = $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertSame(
            [
                StreamEventType::Start->value,
                StreamEventType::Error->value,
                StreamEventType::Finish->value,
            ],
            $eventTypes,
        );
        $this->assertSame([], $aiFoundationClient->getPromptedConversationReferences());
    }

    public function testGivenAbsentCustomerReferenceWhenChatIsExecutedThenNoAiProviderIsReached(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $aiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();

        // Act
        $eventTypes = $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest('', static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertSame(
            [
                StreamEventType::Start->value,
                StreamEventType::Error->value,
                StreamEventType::Finish->value,
            ],
            $eventTypes,
        );
        $this->assertSame([], $aiFoundationClient->getPromptedConversationReferences());
    }

    public function testGivenNoRegisteredAgentWhenChatIsExecutedThenTheCustomerIsToldNoAgentIsAvailable(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveNoStorefrontAssistantAgentPlugin();

        // Act
        $eventTypes = $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertSame(
            [
                StreamEventType::Start->value,
                StreamEventType::Error->value,
                StreamEventType::Finish->value,
            ],
            $eventTypes,
        );
    }

    public function testGivenARegisteredAgentWithItsToggleDisabledWhenTheEnabledAgentsAreReadThenTheListIsEmpty(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant(true, false);
        $this->tester->haveStorefrontAssistantAgentPlugin();

        // Act
        $enabledAgents = $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantEnabledAgents();

        // Assert
        $this->assertSame([], $enabledAgents);
    }

    public function testGivenARegisteredAgentWithItsToggleDisabledWhenChatIsExecutedThenAnErrorPartIsEmittedAndNoAiProviderIsReached(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant(true, false);
        $aiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();

        // Act
        $eventTypes = $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertSame(
            [
                StreamEventType::Start->value,
                StreamEventType::Error->value,
                StreamEventType::Finish->value,
            ],
            $eventTypes,
        );
        $this->assertSame([], $aiFoundationClient->getPromptedConversationReferences());
    }

    public function testGivenAnAiProviderThatFailsWhenChatIsExecutedThenAnErrorPartIsEmittedAndNoFatalEscapes(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveThrowingStorefrontAssistantAgentPlugin();

        // Act
        $eventTypes = $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertContains(StreamEventType::Error->value, $eventTypes);
        $this->assertSame(StreamEventType::Finish->value, end($eventTypes));
    }

    public function testGivenAnAiProviderErrorWhenChatIsExecutedThenTheRawProviderMessageNeverReachesTheCustomer(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveFailingStorefrontAssistantAgentPlugin(static::PROVIDER_ERROR_MESSAGE);
        $customerReference = $this->tester->generateUniqueCustomerReference();
        $storefrontAssistantChatRequestTransfer = $this->tester->createStorefrontAssistantChatRequest($customerReference, static::CONVERSATION_REFERENCE);
        $this->tester->addStorefrontAssistantConversationCleanup($customerReference, static::CONVERSATION_REFERENCE);

        // Act
        $streamedOutput = $this->tester->captureStreamedOutput(
            fn () => $this->tester->getStorefrontAssistantClient()->executeStorefrontAssistantChat($storefrontAssistantChatRequestTransfer),
        );

        // Assert
        $this->assertContains(StreamEventType::Error->value, $this->tester->extractStreamedPartTypes($streamedOutput));
        $this->assertStringNotContainsString(static::PROVIDER_ERROR_MESSAGE, $streamedOutput);
    }

    public function testGivenACompletedTurnWhenChatIsExecutedThenTheConversationIsReadableBackByItsOwner(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin(static::ASSISTANT_MESSAGE);
        $customerReference = $this->tester->generateUniqueCustomerReference();
        $conversationReference = $this->tester->generateUniqueConversationReference();

        // Act
        $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($customerReference, $conversationReference),
        );

        // Assert
        $storefrontAssistantConversationCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionCriteria($customerReference),
            );

        $this->assertSame(
            [$conversationReference],
            $this->tester->getConversationReferences($storefrontAssistantConversationCollectionTransfer),
        );
    }

    public function testGivenACompletedTurnWhenTheAiProviderIsCalledThenItNeverReceivesTheCustomerVisibleReference(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $aiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->generateUniqueConversationReference();

        // Act
        $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), $conversationReference),
        );

        // Assert
        $this->assertNotContains(
            $conversationReference,
            $aiFoundationClient->getPromptedConversationReferences(),
        );
        $this->assertCount(1, $aiFoundationClient->getPromptedConversationReferences());
    }

    public function testGivenTwoCustomersUsingTheSameConversationReferenceWhenBothChatThenTheirTranscriptsStaySeparate(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $aiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->generateUniqueConversationReference();

        // Act
        $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), $conversationReference),
        );
        $this->tester->executeStorefrontAssistantChatAndGetEventTypes(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), $conversationReference),
        );

        // Assert
        $this->assertCount(2, array_unique($aiFoundationClient->getPromptedConversationReferences()));
    }

    public function testGivenAValidRequestWhenItIsValidatedThenTheResponseIsSuccessfulAndNoAiProviderIsReached(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $aiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()->validateStorefrontAssistantChatRequest(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertTrue($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame([], $aiFoundationClient->getPromptedConversationReferences());
    }

    public function testGivenAnUnsupportedAttachmentWhenItIsValidatedThenTheRequestIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setRawAttachments([$this->tester->createRawAttachment('evil.svg', static::MEDIA_TYPE_SVG, 'x')]);

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertNotEmpty($storefrontAssistantChatResponseTransfer->getErrors());
    }

    public function testGivenASupportedAttachmentWhenItIsValidatedThenTheRequestTransferIsLeftUnchanged(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setRawAttachments([
                $this->tester->createRawAttachment('photo.png', static::MEDIA_TYPE_PNG, "\x89PNG\r\n\x1A\n" . 'small'),
            ]);

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertTrue($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertCount(0, $storefrontAssistantChatRequestTransfer->getAttachments());
    }

    public function testGivenASupportedAttachmentWhenChatIsExecutedThenItReachesTheAgentPrompt(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setRawAttachments([
                $this->tester->createRawAttachment('photo.png', static::MEDIA_TYPE_PNG, "\x89PNG\r\n\x1A\n" . 'small'),
            ]);

        // Act
        $this->tester->executeStorefrontAssistantChatAndGetEventTypes($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertCount(1, $recordingTranscriptAiFoundationClient->getPromptedAttachments());
        $this->assertSame(static::MEDIA_TYPE_PNG, $recordingTranscriptAiFoundationClient->getPromptedAttachments()[0]->getMediaType());
    }

    public function testGivenAnOversizedMessageWhenItIsValidatedThenTheRequestIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setMessage($this->tester->createOversizedStorefrontAssistantMessage());

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_TOO_LONG, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
    }

    public function testGivenDisabledFeatureWhenTheRequestIsValidatedThenItIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant(false);

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()->validateStorefrontAssistantChatRequest(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
    }

    public function testGivenAnAttachmentLargerThanTheByteLimitWhenItIsValidatedThenTheRequestIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setRawAttachments([$this->tester->createOversizedPngRawAttachment()]);

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_ATTACHMENT_TOO_LARGE, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
        $this->assertCount(0, $storefrontAssistantChatRequestTransfer->getAttachments());
    }

    public function testGivenMoreAttachmentsThanTheCountLimitWhenItIsValidatedThenTheRequestIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setRawAttachments($this->tester->createTooManyPngRawAttachments());

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_ATTACHMENT_TOO_MANY, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
        $this->assertCount(0, $storefrontAssistantChatRequestTransfer->getAttachments());
    }

    public function testGivenAnAllowedMediaTypeWithTheBytesOfAnotherKindWhenItIsValidatedThenTheRequestIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setRawAttachments([$this->tester->createPngRawAttachmentWithGifContent()]);

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_ATTACHMENT_UNSUPPORTED_TYPE, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
        $this->assertCount(0, $storefrontAssistantChatRequestTransfer->getAttachments());
    }

    public function testGivenAnAttachmentFilenameLongerThanTheLimitWhenChatIsExecutedThenTheAgentReceivesItCutToTheLimit(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $storefrontAssistantChatRequestTransfer = $this->tester
            ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
            ->setRawAttachments([$this->tester->createPngRawAttachment($this->tester->createOverlongAttachmentFilename())]);

        // Act
        $this->tester->executeStorefrontAssistantChatAndGetEventTypes($storefrontAssistantChatRequestTransfer);

        // Assert
        $this->assertSame(
            $this->tester->getMaxAttachmentFilenameLength(),
            mb_strlen((string)$recordingTranscriptAiFoundationClient->getPromptedAttachments()[0]->getFilename()),
        );
    }

    public function testGivenABlankCustomerReferenceWhenTheRequestIsValidatedThenItIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()->validateStorefrontAssistantChatRequest(
            $this->tester->createStorefrontAssistantChatRequest(static::BLANK_VALUE, static::CONVERSATION_REFERENCE),
        );

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_CUSTOMER_REFERENCE_REQUIRED, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
    }

    public function testGivenABlankMessageWhenTheRequestIsValidatedThenItIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()->validateStorefrontAssistantChatRequest(
            $this->tester
                ->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE)
                ->setMessage(static::BLANK_VALUE),
        );

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_REQUIRED, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
    }

    public function testGivenABlankConversationReferenceWhenTheRequestIsValidatedThenItIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()->validateStorefrontAssistantChatRequest(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::BLANK_VALUE),
        );

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_CONVERSATION_REFERENCE_INVALID, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
    }

    public function testGivenAConversationReferenceWithADisallowedCharacterWhenTheRequestIsValidatedThenItIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()->validateStorefrontAssistantChatRequest(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), static::CONVERSATION_REFERENCE_WITH_DISALLOWED_CHARACTER),
        );

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_CONVERSATION_REFERENCE_INVALID, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
    }

    public function testGivenAnOverLongConversationReferenceWhenTheRequestIsValidatedThenItIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveGlossaryReturningKeys();
        $conversationReference = str_repeat('a', $this->tester->getMaxConversationReferenceLength() + 1);

        // Act
        $storefrontAssistantChatResponseTransfer = $this->tester->getStorefrontAssistantClient()->validateStorefrontAssistantChatRequest(
            $this->tester->createStorefrontAssistantChatRequest($this->tester->generateUniqueCustomerReference(), $conversationReference),
        );

        // Assert
        $this->assertFalse($storefrontAssistantChatResponseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_CONVERSATION_REFERENCE_INVALID, $storefrontAssistantChatResponseTransfer->getErrors()[0]->getMessage());
    }
}
