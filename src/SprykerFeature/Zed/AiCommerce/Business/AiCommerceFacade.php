<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business;

use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getFactory()
 */
class AiCommerceFacade extends AbstractFacade implements AiCommerceFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCriteriaTransfer $criteriaTransfer,
    ): BackofficeAssistantConversationCollectionTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationReader()->getCollection($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationCreator()->createCollection($collectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationUpdater()->updateCollection($collectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function deleteBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationDeleter()->deleteCollection($deleteCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOrderOmsTransitions(string $orderReference): string
    {
        return $this->getFactory()->createOrderOmsTransitionsReader()->getOrderOmsTransitions($orderReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOrderDetails(string $orderReference): string
    {
        return $this->getFactory()->createOrderDetailsReader()->getOrderDetails($orderReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOrderManualEvents(string $orderReference): string
    {
        return $this->getFactory()->createOrderManualEventsReader()->getOrderManualEvents($orderReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOmsProcessDefinition(string $orderReference): string
    {
        return $this->getFactory()->createOmsProcessDefinitionReader()->getOmsProcessDefinition($orderReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOrderStateFlags(string $orderReference): string
    {
        return $this->getFactory()->createOrderStateFlagsReader()->getOrderStateFlags($orderReference);
    }
}
