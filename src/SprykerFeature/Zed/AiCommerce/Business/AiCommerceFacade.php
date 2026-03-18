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
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getFactory()
 */
class AiCommerceFacade extends AbstractFacade implements AiCommerceFacadeInterface
{
    public function getBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCriteriaTransfer $criteriaTransfer,
    ): BackofficeAssistantConversationCollectionTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationReader()->getCollection($criteriaTransfer);
    }

    public function createBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationCreator()->createCollection($collectionRequestTransfer);
    }

    public function updateBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionRequestTransfer $collectionRequestTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationUpdater()->updateCollection($collectionRequestTransfer);
    }

    public function deleteBackofficeAssistantConversationCollection(
        BackofficeAssistantConversationCollectionDeleteCriteriaTransfer $deleteCriteriaTransfer,
    ): BackofficeAssistantConversationCollectionResponseTransfer {
        return $this->getFactory()->createBackofficeAssistantConversationDeleter()->deleteCollection($deleteCriteriaTransfer);
    }

    public function handleBackofficeAssistantPrompt(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
    ): BackofficeAssistantPromptResponseTransfer {
        return $this->getFactory()->createPromptHandler()->handle($promptRequestTransfer);
    }

    public function executeGeneralPurposeAgent(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
    ): BackofficeAssistantPromptResponseTransfer {
        return $this->getFactory()->createGeneralPurposeAgentExecutor()->execute($promptRequestTransfer);
    }
}
