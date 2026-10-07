<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class AiCommerceClient extends AbstractClient implements AiCommerceClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSearchTermFromImage(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer
    {
        return $this->getFactory()->createAiSearchByImageTermResolver()->getSearchTermFromImage($searchByImageRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function executeStorefrontAssistantChat(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): void {
        $this->getFactory()
            ->createStorefrontAssistantChatStreamer()
            ->executeStorefrontAssistantChat($storefrontAssistantChatRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function validateStorefrontAssistantChatRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): StorefrontAssistantChatResponseTransfer {
        return $this->getFactory()
            ->createStorefrontAssistantChatStreamer()
            ->validateStorefrontAssistantChatRequest($storefrontAssistantChatRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStorefrontAssistantConversationCollection(
        StorefrontAssistantConversationCollectionCriteriaTransfer $storefrontAssistantConversationCollectionCriteriaTransfer
    ): StorefrontAssistantConversationCollectionTransfer {
        return $this->getFactory()
            ->createConversationCollectionReader()
            ->getConversationCollection($storefrontAssistantConversationCollectionCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function deleteStorefrontAssistantConversationCollection(
        StorefrontAssistantConversationCollectionDeleteCriteriaTransfer $storefrontAssistantConversationCollectionDeleteCriteriaTransfer
    ): StorefrontAssistantConversationCollectionResponseTransfer {
        return $this->getFactory()
            ->createConversationCollectionDeleter()
            ->deleteConversationCollection($storefrontAssistantConversationCollectionDeleteCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStorefrontAssistantConversationMessageCollection(
        StorefrontAssistantConversationMessageCollectionCriteriaTransfer $storefrontAssistantConversationMessageCollectionCriteriaTransfer
    ): StorefrontAssistantConversationMessageCollectionTransfer {
        return $this->getFactory()
            ->createConversationMessageCollectionReader()
            ->getConversationMessageCollection($storefrontAssistantConversationMessageCollectionCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<int, array<string, string>>
     */
    public function getStorefrontAssistantEnabledAgents(): array
    {
        return $this->getFactory()
            ->createAgentSelector()
            ->getEnabledAgents();
    }
}
