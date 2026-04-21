<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business;

use Generated\Shared\Transfer\AiTranslationCollectionRequestTransfer;
use Generated\Shared\Transfer\AiTranslationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationCriteriaTransfer;
use Generated\Shared\Transfer\CategorySuggestionRequestTransfer;
use Generated\Shared\Transfer\CategorySuggestionResponseTransfer;
use Generated\Shared\Transfer\ContentImprovementRequestTransfer;
use Generated\Shared\Transfer\ContentImprovementResponseTransfer;
use Generated\Shared\Transfer\ImageAltTextRequestTransfer;
use Generated\Shared\Transfer\ImageAltTextResponseTransfer;
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
    public function proposeCategorySuggestions(
        CategorySuggestionRequestTransfer $categorySuggestionRequestTransfer,
    ): CategorySuggestionResponseTransfer {
        return $this->getFactory()
            ->createSmartProductManagementCategoryProposer()
            ->proposeCategorySuggestions($categorySuggestionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function generateImageAltText(
        ImageAltTextRequestTransfer $imageAltTextRequestTransfer,
    ): ImageAltTextResponseTransfer {
        return $this->getFactory()
            ->createSmartProductManagementImageAltTextGenerator()
            ->generateImageAltText($imageAltTextRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function translateCollection(AiTranslationCollectionRequestTransfer $aiTranslationCollectionRequestTransfer): AiTranslationCollectionResponseTransfer
    {
        return $this->getFactory()
            ->createSmartProductManagementCollectionTranslator()
            ->translateCollection($aiTranslationCollectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function improveContent(ContentImprovementRequestTransfer $contentImprovementRequestTransfer): ContentImprovementResponseTransfer
    {
        return $this->getFactory()
            ->createSmartProductManagementContentImprover()
            ->improveContent($contentImprovementRequestTransfer);
    }
}
