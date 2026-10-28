<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Widget;

use Spryker\Yves\Kernel\Widget\AbstractWidget;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSuggestToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CompareProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplaySetupToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\OfferChoicesToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductDetailsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductRelationsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductSetsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ReadShopPageToolPlugin;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Plugin\Router\StorefrontAssistantRouteProviderPlugin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceClientInterface getClient()
 */
class StorefrontAssistantWidget extends AbstractWidget
{
    protected const string PARAMETER_IS_ENABLED = 'isEnabled';

    protected const string PARAMETER_PROMPT_URL = 'promptUrl';

    protected const string PARAMETER_USER_NAME = 'userName';

    protected const string PARAMETER_SUPPORTED_ATTACHMENT_MIME_TYPES = 'supportedAttachmentMimeTypes';

    protected const string PARAMETER_MAX_ATTACHMENT_SIZE_BYTES = 'maxAttachmentSizeBytes';

    protected const string PARAMETER_MAX_MESSAGE_LENGTH_CHARACTERS = 'maxMessageLengthCharacters';

    protected const string PARAMETER_MAX_ATTACHMENT_COUNT = 'maxAttachmentCount';

    protected const string PARAMETER_CONVERSATIONS_URL = 'conversationsUrl';

    protected const string PARAMETER_DELETE_CONVERSATION_URL = 'deleteConversationUrl';

    protected const string PARAMETER_CONVERSATION_MESSAGES_URL = 'conversationMessagesUrl';

    protected const string PARAMETER_ENABLED_AGENTS = 'enabledAgents';

    protected const string PARAMETER_SUGGESTED_PROMPTS = 'suggestedPrompts';

    protected const string PARAMETER_SUGGESTED_PROMPT_SECTIONS = 'suggestedPromptSections';

    protected const string PARAMETER_TOOL_NAMES = 'toolNames';

    public function __construct()
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();
        $isEnabled = $this->getConfig()->isStorefrontAssistantEnabled()
            && $customerTransfer?->getCustomerReference() !== null;

        $this->addIsEnabledParameter($isEnabled);
        $this->addPromptUrlParameter($isEnabled);
        $this->addUserNameParameter($isEnabled ? (string)$customerTransfer->getFirstName() : '');
        $this->addSupportedAttachmentMimeTypesParameter();
        $this->addMaxAttachmentSizeBytesParameter();
        $this->addMaxMessageLengthCharactersParameter();
        $this->addMaxAttachmentCountParameter();
        $this->addConversationUrlParameters($isEnabled);
        $this->addEnabledAgentsParameter($isEnabled);
        $this->addSuggestedPromptsParameter($isEnabled);
        $this->addToolNamesParameter();
    }

    /**
     * The progress line names the tool the assistant is running, and a tool names itself with a backend
     * identifier. The names are published so the template can translate each one into a shop phrase.
     */
    protected function addToolNamesParameter(): void
    {
        $this->addParameter(static::PARAMETER_TOOL_NAMES, [
            CatalogSearchToolPlugin::TOOL_NAME,
            CatalogSuggestToolPlugin::TOOL_NAME,
            CategoryTreeToolPlugin::TOOL_NAME,
            DisplayProductsToolPlugin::TOOL_NAME,
            ProductDetailsToolPlugin::TOOL_NAME,
            ProductRelationsToolPlugin::TOOL_NAME,
            ReadShopPageToolPlugin::TOOL_NAME,
            CompareProductsToolPlugin::TOOL_NAME,
            ProductSetsToolPlugin::TOOL_NAME,
            OfferChoicesToolPlugin::TOOL_NAME,
            DisplaySetupToolPlugin::TOOL_NAME,
        ]);
    }

    protected function addEnabledAgentsParameter(bool $isEnabled): void
    {
        $enabledAgents = $isEnabled ? $this->getClient()->getStorefrontAssistantEnabledAgents() : [];

        $this->addParameter(static::PARAMETER_ENABLED_AGENTS, $enabledAgents);
    }

    protected function addSuggestedPromptsParameter(bool $isEnabled): void
    {
        $storefrontAssistantSuggestedPromptReader = $this->getFactory()->createStorefrontAssistantSuggestedPromptReader();
        $suggestedPrompts = $isEnabled ? $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts() : [];
        $suggestedPromptSections = $isEnabled ? $storefrontAssistantSuggestedPromptReader->getSuggestedPromptSections() : [];

        $this->addParameter(static::PARAMETER_SUGGESTED_PROMPTS, $suggestedPrompts);
        $this->addParameter(static::PARAMETER_SUGGESTED_PROMPT_SECTIONS, $suggestedPromptSections);
    }

    protected function addConversationUrlParameters(bool $isEnabled): void
    {
        $this->addParameter(static::PARAMETER_CONVERSATIONS_URL, $this->generateRouteUrl($isEnabled, StorefrontAssistantRouteProviderPlugin::ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATIONS));
        $this->addParameter(static::PARAMETER_DELETE_CONVERSATION_URL, $this->generateRouteUrl($isEnabled, StorefrontAssistantRouteProviderPlugin::ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATION_DELETE));
        $this->addParameter(static::PARAMETER_CONVERSATION_MESSAGES_URL, $this->generateRouteUrl($isEnabled, StorefrontAssistantRouteProviderPlugin::ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATION_MESSAGES));
    }

    protected function generateRouteUrl(bool $isEnabled, string $routeName): string
    {
        if (!$isEnabled) {
            return '';
        }

        return $this->getFactory()->getRouter()->generate($routeName, [], UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    protected function addSupportedAttachmentMimeTypesParameter(): void
    {
        $this->addParameter(
            static::PARAMETER_SUPPORTED_ATTACHMENT_MIME_TYPES,
            implode(',', $this->getConfig()->getStorefrontAssistantSupportedAttachmentMimeTypes()),
        );
    }

    protected function addMaxAttachmentSizeBytesParameter(): void
    {
        $this->addParameter(
            static::PARAMETER_MAX_ATTACHMENT_SIZE_BYTES,
            $this->getConfig()->getStorefrontAssistantMaxAttachmentSizeBytes(),
        );
    }

    protected function addMaxAttachmentCountParameter(): void
    {
        $this->addParameter(
            static::PARAMETER_MAX_ATTACHMENT_COUNT,
            $this->getConfig()->getStorefrontAssistantMaxAttachmentCount(),
        );
    }

    protected function addMaxMessageLengthCharactersParameter(): void
    {
        $this->addParameter(
            static::PARAMETER_MAX_MESSAGE_LENGTH_CHARACTERS,
            $this->getConfig()->getStorefrontAssistantMaxMessageLengthCharacters(),
        );
    }

    protected function addIsEnabledParameter(bool $isEnabled): void
    {
        $this->addParameter(static::PARAMETER_IS_ENABLED, $isEnabled);
    }

    protected function addPromptUrlParameter(bool $isEnabled): void
    {
        $promptUrl = $isEnabled
            ? $this->getFactory()->getRouter()->generate(
                StorefrontAssistantRouteProviderPlugin::ROUTE_NAME_STOREFRONT_ASSISTANT_PROMPT,
                [],
                UrlGeneratorInterface::ABSOLUTE_PATH,
            )
            : '';

        $this->addParameter(static::PARAMETER_PROMPT_URL, $promptUrl);
    }

    protected function addUserNameParameter(string $userName): void
    {
        $this->addParameter(static::PARAMETER_USER_NAME, $userName);
    }

    public static function getName(): string
    {
        return 'StorefrontAssistantWidget';
    }

    public static function getTemplate(): string
    {
        return '@AiCommerce/views/storefront-assistant/storefront-assistant.twig';
    }
}
