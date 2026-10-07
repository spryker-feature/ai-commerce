<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantMessageRole;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group AiCommerceClientStorefrontAssistantConversationTest
 * Add your own group annotations below this line
 */
class AiCommerceClientStorefrontAssistantConversationTest extends Unit
{
    protected const string ASSISTANT_MESSAGE = 'We sell cameras and lenses.';

    protected const int CONVERSATION_INDEX_LIMIT = 2;

    protected const int CONVERSATION_LIST_LIMIT = 1;

    protected AiCommerceClientTester $tester;

    protected string $customerReferenceOwner;

    protected string $customerReferenceForeign;

    protected function _before(): void
    {
        $this->customerReferenceOwner = $this->tester->generateUniqueCustomerReference();
        $this->customerReferenceForeign = $this->tester->generateUniqueCustomerReference();
    }

    public function testGivenAPersistedConversationWhenItsOwnerReadsTheCollectionThenItIsReturned(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $storefrontAssistantConversationCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionCriteria($this->customerReferenceOwner),
            );

        // Assert
        $this->assertContains(
            $conversationReference,
            $this->tester->getConversationReferences($storefrontAssistantConversationCollectionTransfer),
        );
    }

    public function testGivenAPersistedConversationWhenAnotherCustomerReadsTheCollectionThenItIsNotReturned(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $storefrontAssistantConversationCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionCriteria($this->customerReferenceForeign),
            );

        // Assert
        $this->assertNotContains(
            $conversationReference,
            $this->tester->getConversationReferences($storefrontAssistantConversationCollectionTransfer),
        );
    }

    public function testGivenMoreConversationsThanTheIndexLimitWhenTheOwnerReadsTheCollectionThenOnlyTheNewestAreReturned(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantConversationIndexLimit(static::CONVERSATION_INDEX_LIMIT);
        $this->tester->haveStorefrontAssistantAgentPlugin();
        $oldestConversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $olderConversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $newestConversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $conversationReferences = $this->tester->getConversationReferences(
            $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionCriteria($this->customerReferenceOwner),
            ),
        );

        // Assert
        $this->assertCount(static::CONVERSATION_INDEX_LIMIT, $conversationReferences);
        $this->assertContains($olderConversationReference, $conversationReferences);
        $this->assertContains($newestConversationReference, $conversationReferences);
        $this->assertNotContains($oldestConversationReference, $conversationReferences);
    }

    public function testGivenMoreConversationsThanTheLimitWhenTheOwnerRequestsAnOlderOneByReferenceThenItIsReturned(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();
        $oldestConversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $storefrontAssistantConversationCollectionCriteriaTransfer = $this->tester->createConversationCollectionCriteria($this->customerReferenceOwner);
        $storefrontAssistantConversationCollectionCriteriaTransfer->getStorefrontAssistantConversationConditionsOrFail()
            ->setConversationReferences([$oldestConversationReference])
            ->setLimit(static::CONVERSATION_LIST_LIMIT);

        // Act
        $conversationReferences = $this->tester->getConversationReferences(
            $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantConversationCollection(
                $storefrontAssistantConversationCollectionCriteriaTransfer,
            ),
        );

        // Assert
        $this->assertSame([$oldestConversationReference], $conversationReferences);
    }

    public function testGivenAPersistedConversationWhenAnotherCustomerRequestsItByReferenceThenItIsNotReturned(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $storefrontAssistantConversationCollectionCriteriaTransfer = $this->tester->createConversationCollectionCriteria($this->customerReferenceForeign);
        $storefrontAssistantConversationCollectionCriteriaTransfer->getStorefrontAssistantConversationConditionsOrFail()
            ->setConversationReferences([$conversationReference]);

        // Act
        $storefrontAssistantConversationCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationCollection($storefrontAssistantConversationCollectionCriteriaTransfer);

        // Assert
        $this->assertCount(0, $storefrontAssistantConversationCollectionTransfer->getStorefrontAssistantConversations());
    }

    public function testGivenABlankOwnerReferenceWhenTheCollectionIsReadThenItIsEmptyInsteadOfEveryConversation(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();
        $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $storefrontAssistantConversationCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionCriteria(''),
            );

        // Assert
        $this->assertCount(0, $storefrontAssistantConversationCollectionTransfer->getStorefrontAssistantConversations());
    }

    public function testGivenAPersistedConversationWhenItsOwnerDeletesItThenItIsRemovedAndReported(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $ownerDerivedConversationReference = $recordingTranscriptAiFoundationClient->getPromptedConversationReferences()[0];

        // Act
        $storefrontAssistantConversationCollectionResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->deleteStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionDeleteCriteria(
                    $this->customerReferenceOwner,
                    [$conversationReference],
                ),
            );

        // Assert
        $this->assertTrue($storefrontAssistantConversationCollectionResponseTransfer->getIsSuccessful());
        $this->assertSame(1, $storefrontAssistantConversationCollectionResponseTransfer->getDeletedCount());
        $this->assertNotContains(
            $conversationReference,
            $this->tester->getConversationReferences(
                $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantConversationCollection(
                    $this->tester->createConversationCollectionCriteria($this->customerReferenceOwner),
                ),
            ),
        );
        $this->assertNotSame($conversationReference, $ownerDerivedConversationReference);
        $this->assertSame(
            [$ownerDerivedConversationReference],
            $recordingTranscriptAiFoundationClient->getDeletedConversationReferences(),
        );
        $this->assertNotContains(
            $ownerDerivedConversationReference,
            $recordingTranscriptAiFoundationClient->getStoredConversationReferences(),
        );
    }

    public function testGivenAFailingHistoryDeleteWhenTheOwnerDeletesAConversationThenTheDeleteFailsAndTheConversationStaysListed(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $recordingTranscriptAiFoundationClient->failConversationHistoryDeletion();

        // Act
        $storefrontAssistantConversationCollectionResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->deleteStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionDeleteCriteria(
                    $this->customerReferenceOwner,
                    [$conversationReference],
                ),
            );

        // Assert
        $this->assertFalse($storefrontAssistantConversationCollectionResponseTransfer->getIsSuccessful());
        $this->assertSame(0, $storefrontAssistantConversationCollectionResponseTransfer->getDeletedCount());
        $this->assertNotCount(0, $storefrontAssistantConversationCollectionResponseTransfer->getErrors());
        $this->assertContains(
            $conversationReference,
            $this->tester->getConversationReferences(
                $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantConversationCollection(
                    $this->tester->createConversationCollectionCriteria($this->customerReferenceOwner),
                ),
            ),
        );
    }

    public function testGivenAForeignReferenceWhenItIsDeletedThenTheOwnersConversationStaysIntact(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $ownerDerivedConversationReference = $recordingTranscriptAiFoundationClient->getPromptedConversationReferences()[0];

        // Act
        $storefrontAssistantConversationCollectionResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->deleteStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionDeleteCriteria(
                    $this->customerReferenceForeign,
                    [$conversationReference],
                ),
            );

        // Assert
        $this->assertTrue($storefrontAssistantConversationCollectionResponseTransfer->getIsSuccessful());
        $this->assertSame(0, $storefrontAssistantConversationCollectionResponseTransfer->getDeletedCount());
        $this->assertContains(
            $conversationReference,
            $this->tester->getConversationReferences(
                $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantConversationCollection(
                    $this->tester->createConversationCollectionCriteria($this->customerReferenceOwner),
                ),
            ),
        );
        $deletedConversationReferences = $recordingTranscriptAiFoundationClient->getDeletedConversationReferences();
        $this->assertCount(1, $deletedConversationReferences);
        $this->assertNotContains($ownerDerivedConversationReference, $deletedConversationReferences);
        $this->assertContains(
            $ownerDerivedConversationReference,
            $recordingTranscriptAiFoundationClient->getStoredConversationReferences(),
        );
    }

    public function testGivenABlankOwnerReferenceWhenAConversationIsDeletedThenTheDeleteIsRejectedAndNoTranscriptIsDeleted(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $ownerDerivedConversationReference = $recordingTranscriptAiFoundationClient->getPromptedConversationReferences()[0];

        // Act
        $storefrontAssistantConversationCollectionResponseTransfer = $this->tester->getStorefrontAssistantClient()
            ->deleteStorefrontAssistantConversationCollection(
                $this->tester->createConversationCollectionDeleteCriteria('', [$conversationReference]),
            );

        // Assert
        $this->assertFalse($storefrontAssistantConversationCollectionResponseTransfer->getIsSuccessful());
        $this->assertSame(0, $storefrontAssistantConversationCollectionResponseTransfer->getDeletedCount());
        $this->assertNotCount(0, $storefrontAssistantConversationCollectionResponseTransfer->getErrors());
        $this->assertSame([], $recordingTranscriptAiFoundationClient->getDeletedConversationReferences());
        $this->assertContains(
            $ownerDerivedConversationReference,
            $recordingTranscriptAiFoundationClient->getStoredConversationReferences(),
        );
        $this->assertContains(
            $conversationReference,
            $this->tester->getConversationReferences(
                $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantConversationCollection(
                    $this->tester->createConversationCollectionCriteria($this->customerReferenceOwner),
                ),
            ),
        );
    }

    public function testGivenAPersistedConversationWhenItsOwnerReadsTheMessagesThenTheTranscriptIsReturned(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin(static::ASSISTANT_MESSAGE);
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $storefrontAssistantConversationMessageCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationMessageCollection(
                $this->tester->createConversationMessageCollectionCriteria(
                    $this->customerReferenceOwner,
                    $conversationReference,
                ),
            );

        // Assert
        $this->assertTrue($storefrontAssistantConversationMessageCollectionTransfer->getIsSuccessful());
        $this->assertSame($conversationReference, $storefrontAssistantConversationMessageCollectionTransfer->getConversationReference());
        $this->assertSame(
            [
                [StorefrontAssistantMessageRole::User->value, $this->tester->getStorefrontAssistantUserMessage()],
                [StorefrontAssistantMessageRole::Assistant->value, static::ASSISTANT_MESSAGE],
            ],
            $this->tester->getConversationMessageRolesAndContents($storefrontAssistantConversationMessageCollectionTransfer),
        );
    }

    public function testGivenAPersistedConversationWithAnEmptyTranscriptWhenItsOwnerReadsTheMessagesThenTheReadSucceedsWithNoMessages(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin(static::ASSISTANT_MESSAGE);
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);
        $recordingTranscriptAiFoundationClient->clearTranscripts();

        // Act
        $storefrontAssistantConversationMessageCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationMessageCollection(
                $this->tester->createConversationMessageCollectionCriteria(
                    $this->customerReferenceOwner,
                    $conversationReference,
                ),
            );

        // Assert
        $this->assertTrue($storefrontAssistantConversationMessageCollectionTransfer->getIsSuccessful());
        $this->assertCount(0, $storefrontAssistantConversationMessageCollectionTransfer->getMessages());
        $this->assertCount(0, $storefrontAssistantConversationMessageCollectionTransfer->getErrors());
        $this->assertCount(1, $recordingTranscriptAiFoundationClient->getReadConversationReferences());
    }

    public function testGivenABlankConversationReferenceWhenTheOwnerReadsTheMessagesThenTheReadIsRejectedWithoutReadingTheTranscript(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $recordingTranscriptAiFoundationClient = $this->tester->haveStorefrontAssistantAgentPlugin(static::ASSISTANT_MESSAGE);
        $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $storefrontAssistantConversationMessageCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationMessageCollection(
                $this->tester->createConversationMessageCollectionCriteria($this->customerReferenceOwner, ''),
            );

        // Assert
        $this->assertFalse($storefrontAssistantConversationMessageCollectionTransfer->getIsSuccessful());
        $this->assertCount(1, $storefrontAssistantConversationMessageCollectionTransfer->getErrors());
        $this->assertCount(0, $storefrontAssistantConversationMessageCollectionTransfer->getMessages());
        $this->assertSame([], $recordingTranscriptAiFoundationClient->getReadConversationReferences());
    }

    public function testGivenAConversationTheCustomerDoesNotHoldWhenItsMessagesAreReadThenTheReadIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin(static::ASSISTANT_MESSAGE);
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $storefrontAssistantConversationMessageCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationMessageCollection(
                $this->tester->createConversationMessageCollectionCriteria(
                    $this->customerReferenceForeign,
                    $conversationReference,
                ),
            );

        // Assert
        $this->assertFalse($storefrontAssistantConversationMessageCollectionTransfer->getIsSuccessful());
        $this->assertCount(0, $storefrontAssistantConversationMessageCollectionTransfer->getMessages());
    }

    public function testGivenABlankOwnerReferenceWhenMessagesAreReadThenTheReadIsRejected(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();
        $conversationReference = $this->tester->haveStorefrontAssistantConversation($this->customerReferenceOwner);

        // Act
        $storefrontAssistantConversationMessageCollectionTransfer = $this->tester->getStorefrontAssistantClient()
            ->getStorefrontAssistantConversationMessageCollection(
                $this->tester->createConversationMessageCollectionCriteria('', $conversationReference),
            );

        // Assert
        $this->assertFalse($storefrontAssistantConversationMessageCollectionTransfer->getIsSuccessful());
    }

    public function testGivenRegisteredAgentsWhenTheEnabledAgentsAreReadThenEachCarriesItsNameAndDescription(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveStorefrontAssistantAgentPlugin();

        // Act
        $enabledAgents = $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantEnabledAgents();

        // Assert
        $this->assertNotCount(0, $enabledAgents);
        $this->assertArrayHasKey('name', $enabledAgents[0]);
        $this->assertArrayHasKey('description', $enabledAgents[0]);
    }

    public function testGivenNoRegisteredAgentWhenTheEnabledAgentsAreReadThenTheListIsEmpty(): void
    {
        // Arrange
        $this->tester->enableStorefrontAssistant();
        $this->tester->haveNoStorefrontAssistantAgentPlugin();

        // Act
        $enabledAgents = $this->tester->getStorefrontAssistantClient()->getStorefrontAssistantEnabledAgents();

        // Assert
        $this->assertSame([], $enabledAgents);
    }
}
