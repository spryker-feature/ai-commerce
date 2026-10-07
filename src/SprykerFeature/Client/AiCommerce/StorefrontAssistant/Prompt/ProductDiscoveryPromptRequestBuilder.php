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
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSuggestToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationReferenceDeriverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestMapperInterface;

class ProductDiscoveryPromptRequestBuilder implements ProductDiscoveryPromptRequestBuilderInterface
{
    /**
     * @var list<string>
     */
    protected const array TOOL_NAMES = [
        CatalogSearchToolPlugin::TOOL_NAME,
        CatalogSuggestToolPlugin::TOOL_NAME,
        CategoryTreeToolPlugin::TOOL_NAME,
        DisplayProductsToolPlugin::TOOL_NAME,
    ];

    public function __construct(
        protected ConversationReferenceDeriverInterface $conversationReferenceDeriver,
        protected StorefrontAssistantRequestMapperInterface $storefrontAssistantRequestMapper,
        protected PageContextSystemPromptComposerInterface $pageContextSystemPromptComposer,
        protected AiCommerceConfig $aiCommerceConfig
    ) {
    }

    public function buildPromptRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): PromptRequestTransfer {
        $scopedConversationReference = $this->conversationReferenceDeriver->deriveConversationReference(
            (string)$storefrontAssistantChatRequestTransfer->getCustomerReference(),
            (string)$storefrontAssistantChatRequestTransfer->getConversationReference(),
        );

        return $this->storefrontAssistantRequestMapper->mapStorefrontAssistantChatRequestToPromptRequest(
            $storefrontAssistantChatRequestTransfer,
            $this->aiCommerceConfig->getStorefrontAssistantAiConfigurationName(),
            $scopedConversationReference,
            static::TOOL_NAMES,
            $this->pageContextSystemPromptComposer->composeSystemPrompt(
                $this->aiCommerceConfig->getStorefrontAssistantSystemPrompt(
                    $this->aiCommerceConfig->getProductDiscoverySystemPromptKey(),
                ),
                $storefrontAssistantChatRequestTransfer,
            ),
        );
    }
}
