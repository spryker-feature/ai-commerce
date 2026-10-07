<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class StorefrontAssistantRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    public const string ROUTE_NAME_STOREFRONT_ASSISTANT_PROMPT = 'shopping-assistant/prompt';

    public const string ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATIONS = 'shopping-assistant/conversations';

    public const string ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATION_MESSAGES = 'shopping-assistant/conversations/messages';

    public const string ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATION_DELETE = 'shopping-assistant/conversations/delete';

    protected const string PATTERN_STOREFRONT_ASSISTANT_PROMPT = '/shopping-assistant/prompt';

    protected const string PATTERN_STOREFRONT_ASSISTANT_CONVERSATIONS = '/shopping-assistant/conversations';

    protected const string PATTERN_STOREFRONT_ASSISTANT_CONVERSATION_MESSAGES = '/shopping-assistant/conversations/messages';

    protected const string PATTERN_STOREFRONT_ASSISTANT_CONVERSATION_DELETE = '/shopping-assistant/conversations';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addStorefrontAssistantPromptRoute($routeCollection);
        $routeCollection = $this->addStorefrontAssistantConversationsRoute($routeCollection);
        $routeCollection = $this->addStorefrontAssistantConversationMessagesRoute($routeCollection);
        $routeCollection = $this->addStorefrontAssistantConversationDeleteRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\AiCommerce\Controller\StorefrontAssistantController::promptAction()
     */
    protected function addStorefrontAssistantPromptRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(static::PATTERN_STOREFRONT_ASSISTANT_PROMPT, 'AiCommerce', 'StorefrontAssistant', 'prompt');

        $routeCollection->add(static::ROUTE_NAME_STOREFRONT_ASSISTANT_PROMPT, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\AiCommerce\Controller\StorefrontAssistantController::conversationsAction()
     */
    protected function addStorefrontAssistantConversationsRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute(static::PATTERN_STOREFRONT_ASSISTANT_CONVERSATIONS, 'AiCommerce', 'StorefrontAssistant', 'conversations');

        $routeCollection->add(static::ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATIONS, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\AiCommerce\Controller\StorefrontAssistantController::conversationMessagesAction()
     */
    protected function addStorefrontAssistantConversationMessagesRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute(static::PATTERN_STOREFRONT_ASSISTANT_CONVERSATION_MESSAGES, 'AiCommerce', 'StorefrontAssistant', 'conversationMessages');

        $routeCollection->add(static::ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATION_MESSAGES, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\AiCommerce\Controller\StorefrontAssistantController::deleteConversationAction()
     */
    protected function addStorefrontAssistantConversationDeleteRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(static::PATTERN_STOREFRONT_ASSISTANT_CONVERSATION_DELETE, 'AiCommerce', 'StorefrontAssistant', 'deleteConversation')
            ->setMethods(Request::METHOD_DELETE);

        $routeCollection->add(static::ROUTE_NAME_STOREFRONT_ASSISTANT_CONVERSATION_DELETE, $route);

        return $routeCollection;
    }
}
