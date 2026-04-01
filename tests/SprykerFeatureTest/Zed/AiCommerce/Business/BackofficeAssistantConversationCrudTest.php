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
        $idUser = $this->tester->haveUser()->getIdUserOrFail();
        $conversationName = uniqid('Conversation ', true);

        $conversationTransfer = (new BackofficeAssistantConversationTransfer())
            ->setIdUser($idUser)
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
        $this->assertSame($idUser, $createdConversation->getIdUser());
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
            BackofficeAssistantConversationTransfer::ID_USER => $this->tester->haveUser()->getIdUserOrFail(),
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
        $this->assertSame($conversationTransfer->getIdUser(), $foundConversation->getIdUser());
        $this->assertSame($conversationTransfer->getName(), $foundConversation->getName());
    }

    public function testGetBackofficeAssistantConversationCollectionFiltersByIdUser(): void
    {
        // Arrange
        $idUserFirst = $this->tester->haveUser()->getIdUserOrFail();
        $idUserSecond = $this->tester->haveUser()->getIdUserOrFail();

        $firstConversation = $this->tester->haveConversation([
            BackofficeAssistantConversationTransfer::ID_USER => $idUserFirst,
            BackofficeAssistantConversationTransfer::NAME => 'First User Conversation',
        ]);

        $this->tester->haveConversation([
            BackofficeAssistantConversationTransfer::ID_USER => $idUserSecond,
            BackofficeAssistantConversationTransfer::NAME => 'Second User Conversation',
        ]);

        $conditionsTransfer = (new BackofficeAssistantConversationConditionsTransfer())
            ->addIdUser($idUserFirst);

        $criteriaTransfer = (new BackofficeAssistantConversationCriteriaTransfer())
            ->setBackofficeAssistantConversationConditions($conditionsTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getBackofficeAssistantConversationCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getBackofficeAssistantConversations());

        $foundConversation = $collectionTransfer->getBackofficeAssistantConversations()->getIterator()->current();
        $this->assertSame($firstConversation->getConversationReference(), $foundConversation->getConversationReference());
        $this->assertSame($idUserFirst, $foundConversation->getIdUser());
    }

    public function testUpdateBackofficeAssistantConversationCollectionUpdatesAgentField(): void
    {
        // Arrange
        $conversationTransfer = $this->tester->haveConversation([
            BackofficeAssistantConversationTransfer::ID_USER => $this->tester->haveUser()->getIdUserOrFail(),
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
        $idUser = $this->tester->haveUser()->getIdUserOrFail();
        $conversationTransfer = (new BackofficeAssistantConversationTransfer())
            ->setIdUser($idUser)
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
