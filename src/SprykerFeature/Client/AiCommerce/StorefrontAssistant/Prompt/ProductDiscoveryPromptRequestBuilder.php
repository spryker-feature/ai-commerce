<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Prompt;

use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\ProductDiscoveryChatRequestExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\ProductDiscoveryToolNameResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationReferenceDeriverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestMapperInterface;

class ProductDiscoveryPromptRequestBuilder implements ProductDiscoveryPromptRequestBuilderInterface
{
    public function __construct(
        protected ConversationReferenceDeriverInterface $conversationReferenceDeriver,
        protected StorefrontAssistantRequestMapperInterface $storefrontAssistantRequestMapper,
        protected PageContextSystemPromptComposerInterface $pageContextSystemPromptComposer,
        protected ProductDiscoveryChatRequestExpanderInterface $productDiscoveryChatRequestExpander,
        protected CategoryTreeFormatterInterface $categoryTreeFormatter,
        protected ProductDiscoveryToolNameResolverInterface $productDiscoveryToolNameResolver,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    public function buildPromptRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): PromptRequestTransfer {
        $storefrontAssistantChatRequestTransfer = $this->productDiscoveryChatRequestExpander->expand($storefrontAssistantChatRequestTransfer);

        $inlineCategoryTree = $this->categoryTreeFormatter->findInlineCategoryTree(
            $this->aiCommerceConfig->getStorefrontAssistantInlineCategoryTreeLimit(),
        );

        $scopedConversationReference = $this->conversationReferenceDeriver->deriveConversationReference(
            (string)$storefrontAssistantChatRequestTransfer->getCustomerReference(),
            (string)$storefrontAssistantChatRequestTransfer->getConversationReference(),
        );

        return $this->storefrontAssistantRequestMapper->mapStorefrontAssistantChatRequestToPromptRequest(
            $storefrontAssistantChatRequestTransfer,
            $this->aiCommerceConfig->getStorefrontAssistantAiConfigurationName(),
            $scopedConversationReference,
            $this->productDiscoveryToolNameResolver->resolveToolNames(
                $this->aiCommerceConfig->getProductDiscoveryAgentToolNames(),
                $inlineCategoryTree,
            ),
            $this->pageContextSystemPromptComposer->composeSystemPrompt(
                $this->aiCommerceConfig->getStorefrontAssistantSystemPrompt(
                    $this->aiCommerceConfig->getProductDiscoverySystemPromptKey(),
                ),
                $storefrontAssistantChatRequestTransfer,
                $inlineCategoryTree,
            ),
        );
    }
}
