<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationTransfer;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group Facade
 * @group BackofficeAssistantConversationCrudTest
 * Add your own group annotations below this line
 */
class BackofficeAssistantConversationCrudTest extends Unit
{
    protected AiCommerceBusinessTester $tester;

    public function testCreateBackofficeAssistantConversationCollectionCreatesConversation(): void
    {
        // Arrange
        $userUuid = uniqid('user-uuid-', true);
        $conversationName = uniqid('Conversation ', true);

        $conversationTransfer = (new BackofficeAssistantConversationTransfer())
            ->setUserUuid($userUuid)
            ->setName($conversationName);

        $collectionRequestTransfer = (new BackofficeAssistantConversationCollectionRequestTransfer())
            ->addBackofficeAssistantConversation($conversationTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->createBackofficeAssistantConversationCollection($collectionRequestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getBackofficeAssistantConversations());

        $createdConversation = $responseTransfer->getBackofficeAssistantConversations()->getIterator()->current();
        $this->assertNotNull($createdConversation->getIdBackofficeAssistantConversation());
        $this->assertNotEmpty($createdConversation->getConversationReference());
        $this->assertSame($userUuid, $createdConversation->getUserUuid());
        $this->assertSame($conversationName, $createdConversation->getName());

        // Cleanup
        $this->tester->getFacade()->deleteBackofficeAssistantConversationCollection(
            (new BackofficeAssistantConversationCollectionDeleteCriteriaTransfer())
                ->addConversationReference($createdConversation->getConversationReferenceOrFail()),
        );
    }

    public function testGetBackofficeAssistantConversationCollectionReturnsCreatedConversation(): void
    {
        // Arrange
        $conversationTransfer = $this->tester->haveConversation([
            BackofficeAssistantConversationTransfer::USER_UUID => uniqid('user-uuid-', true),
            BackofficeAssistantConversationTransfer::NAME => uniqid('Conversation ', true),
        ]);

        $conditionsTransfer = (new BackofficeAssistantConversationConditionsTransfer())
            ->addConversationReference($conversationTransfer->getConversationReferenceOrFail());

        $criteriaTransfer = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions($conditionsTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getBackofficeAssistantConversationCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getBackofficeAssistantConversations());

        $foundConversation = $collectionTransfer->getBackofficeAssistantConversations()->getIterator()->current();
        $this->assertSame($conversationTransfer->getConversationReference(), $foundConversation->getConversationReference());
        $this->assertSame($conversationTransfer->getUserUuid(), $foundConversation->getUserUuid());
        $this->assertSame($conversationTransfer->getName(), $foundConversation->getName());
    }

    public function testGetBackofficeAssistantConversationCollectionFiltersByUserUuid(): void
    {
        // Arrange
        $userUuidFirst = uniqid('user-uuid-first-', true);
        $userUuidSecond = uniqid('user-uuid-second-', true);

        $firstConversation = $this->tester->haveConversation([
            BackofficeAssistantConversationTransfer::USER_UUID => $userUuidFirst,
            BackofficeAssistantConversationTransfer::NAME => 'First User Conversation',
        ]);

        $this->tester->haveConversation([
            BackofficeAssistantConversationTransfer::USER_UUID => $userUuidSecond,
            BackofficeAssistantConversationTransfer::NAME => 'Second User Conversation',
        ]);

        $conditionsTransfer = (new BackofficeAssistantConversationConditionsTransfer())
            ->addUserUuid($userUuidFirst);

        $criteriaTransfer = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions($conditionsTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getBackofficeAssistantConversationCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getBackofficeAssistantConversations());

        $foundConversation = $collectionTransfer->getBackofficeAssistantConversations()->getIterator()->current();
        $this->assertSame($firstConversation->getConversationReference(), $foundConversation->getConversationReference());
        $this->assertSame($userUuidFirst, $foundConversation->getUserUuid());
    }

    public function testUpdateBackofficeAssistantConversationCollectionUpdatesAgentField(): void
    {
        // Arrange
        $conversationTransfer = $this->tester->haveConversation([
            BackofficeAssistantConversationTransfer::USER_UUID => uniqid('user-uuid-', true),
            BackofficeAssistantConversationTransfer::NAME => uniqid('Conversation ', true),
        ]);

        $updatedAgent = 'order-management';

        $updateTransfer = (new BackofficeAssistantConversationTransfer())
            ->setConversationReference($conversationTransfer->getConversationReferenceOrFail())
            ->setAgent($updatedAgent);

        $collectionRequestTransfer = (new BackofficeAssistantConversationCollectionRequestTransfer())
            ->addBackofficeAssistantConversation($updateTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateBackofficeAssistantConversationCollection($collectionRequestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());

        $conditionsTransfer = (new BackofficeAssistantConversationConditionsTransfer())
            ->addConversationReference($conversationTransfer->getConversationReferenceOrFail());

        $criteriaTransfer = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions($conditionsTransfer);

        $collectionTransfer = $this->tester->getFacade()->getBackofficeAssistantConversationCollection($criteriaTransfer);

        $this->assertCount(1, $collectionTransfer->getBackofficeAssistantConversations());

        $updatedConversation = $collectionTransfer->getBackofficeAssistantConversations()->getIterator()->current();
        $this->assertSame($updatedAgent, $updatedConversation->getAgent());
    }

    public function testDeleteBackofficeAssistantConversationCollectionDeletesConversation(): void
    {
        // Arrange
        $userUuid = uniqid('user-uuid-', true);
        $conversationTransfer = (new BackofficeAssistantConversationTransfer())
            ->setUserUuid($userUuid)
            ->setName(uniqid('Conversation ', true));

        $collectionRequestTransfer = (new BackofficeAssistantConversationCollectionRequestTransfer())
            ->addBackofficeAssistantConversation($conversationTransfer);

        $responseTransfer = $this->tester->getFacade()->createBackofficeAssistantConversationCollection($collectionRequestTransfer);
        $createdConversation = $responseTransfer->getBackofficeAssistantConversations()->getIterator()->current();
        $conversationReference = $createdConversation->getConversationReferenceOrFail();

        $deleteCriteriaTransfer = (new BackofficeAssistantConversationCollectionDeleteCriteriaTransfer())
            ->addConversationReference($conversationReference);

        // Act
        $this->tester->getFacade()->deleteBackofficeAssistantConversationCollection($deleteCriteriaTransfer);

        // Assert
        $conditionsTransfer = (new BackofficeAssistantConversationConditionsTransfer())
            ->addConversationReference($conversationReference);

        $criteriaTransfer = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions($conditionsTransfer);

        $collectionTransfer = $this->tester->getFacade()->getBackofficeAssistantConversationCollection($criteriaTransfer);

        $this->assertCount(0, $collectionTransfer->getBackofficeAssistantConversations());
    }
}
