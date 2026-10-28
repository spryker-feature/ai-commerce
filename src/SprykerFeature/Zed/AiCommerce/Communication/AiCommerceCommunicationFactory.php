<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication;

use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use Spryker\Zed\User\Business\UserFacadeInterface;
use SprykerFeature\Shared\AiCommerce\Conversation\RegeneratedPromptMarker;
use SprykerFeature\Shared\AiCommerce\Stream\MessageStreamer;
use SprykerFeature\Shared\AiCommerce\Stream\MessageStreamerInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTracker;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTrackerInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitter;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitterInterface;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallEventStreamer;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallEventStreamerInterface;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallIdResolver;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallIdResolverInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Attachment\AttachmentBuilder;
use SprykerFeature\Zed\AiCommerce\Communication\Attachment\AttachmentBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Communication\Attachment\AttachmentValidator;
use SprykerFeature\Zed\AiCommerce\Communication\Attachment\AttachmentValidatorInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat\BackofficeAssistantAgentReader;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat\BackofficeAssistantAgentReaderInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat\BackofficeAssistantChatMapper;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat\BackofficeAssistantChatMapperInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat\BackofficeAssistantChatPayloadValidator;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat\BackofficeAssistantChatPayloadValidatorInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\BackofficeAssistantPromptRequestValidator;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\BackofficeAssistantPromptRequestValidatorInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\IntentRouter;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\IntentRouterInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptContextFormatter;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptContextFormatterInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptProcessor;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptProcessorInterface;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\CreateDiscountToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\FillFormToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetContentItemsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetDiscountDetailsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetNavigationToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderDetailsByIdToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderDetailsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderManualEventsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderOmsTransitionsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetOrderStateFlagsToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\SkillBackofficeCapabilities\SkillBackofficeCapabilitiesToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\SkillDiscountKnowledgeBase\SkillDiscountKnowledgeBaseToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\SkillOrderManagementKnowledgeBase\SkillOrderManagementKnowledgeBaseToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\UpdateDiscountToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper\SmartCmsContentRequestMapper;
use SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper\SmartCmsContentRequestMapperInterface;
use SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper\SmartCmsContentResponseMapper;
use SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper\SmartCmsContentResponseMapperInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class AiCommerceCommunicationFactory extends AbstractCommunicationFactory
{
    public function createBackofficeAssistantChatMapper(): BackofficeAssistantChatMapperInterface
    {
        return new BackofficeAssistantChatMapper($this->createPromptContextFormatter(), $this->createRegeneratedPromptMarker());
    }

    public function createBackofficeAssistantChatPayloadValidator(): BackofficeAssistantChatPayloadValidatorInterface
    {
        return new BackofficeAssistantChatPayloadValidator();
    }

    public function createBackofficeAssistantAgentReader(): BackofficeAssistantAgentReaderInterface
    {
        return new BackofficeAssistantAgentReader($this->getBackofficeAssistantAgentPlugins());
    }

    public function createPromptContextFormatter(): PromptContextFormatterInterface
    {
        return new PromptContextFormatter($this->createRegeneratedPromptMarker());
    }

    public function createPromptHandler(): PromptProcessorInterface
    {
        return new PromptProcessor(
            $this->getFacade(),
            $this->getAiFoundationFacade(),
            $this->getBackofficeAssistantAgentPlugins(),
            $this->createAttachmentBuilder(),
            $this->createMessageStreamer(),
            $this->createIntentRouter(),
            $this->createBackofficeAssistantPromptRequestValidator(),
            $this->getTranslatorFacade(),
            $this->createPromptContextFormatter(),
        );
    }

    public function createAttachmentBuilder(): AttachmentBuilderInterface
    {
        return new AttachmentBuilder();
    }

    public function createAttachmentValidator(): AttachmentValidatorInterface
    {
        return new AttachmentValidator($this->getConfig());
    }

    public function createStreamEventEmitter(): StreamEventEmitterInterface
    {
        return new StreamEventEmitter();
    }

    public function createStreamBlockTracker(): StreamBlockTrackerInterface
    {
        return new StreamBlockTracker($this->createStreamEventEmitter());
    }

    public function createMessageStreamer(): MessageStreamerInterface
    {
        return new MessageStreamer(
            $this->createStreamEventEmitter(),
            $this->createStreamBlockTracker(),
        );
    }

    public function createToolCallIdResolver(): ToolCallIdResolverInterface
    {
        return new ToolCallIdResolver();
    }

    public function createToolCallEventStreamer(): ToolCallEventStreamerInterface
    {
        return new ToolCallEventStreamer(
            $this->createStreamEventEmitter(),
            $this->createStreamBlockTracker(),
            $this->createToolCallIdResolver(),
            $this->getConfig()->getBackofficeAssistantSseAiConfigurationNames(),
        );
    }

    public function createIntentRouter(): IntentRouterInterface
    {
        return new IntentRouter(
            $this->getAiFoundationFacade(),
            $this->getBackofficeAssistantAgentPlugins(),
            $this->getConfig()->getIntentRouterAiConfigurationName(),
        );
    }

    public function createBackofficeAssistantPromptRequestValidator(): BackofficeAssistantPromptRequestValidatorInterface
    {
        return new BackofficeAssistantPromptRequestValidator($this->createAttachmentValidator());
    }

    public function createGetNavigationToolPlugin(): ToolPluginInterface
    {
        return new GetNavigationToolPlugin();
    }

    public function createSkillBackofficeCapabilitiesToolPlugin(): ToolPluginInterface
    {
        return new SkillBackofficeCapabilitiesToolPlugin();
    }

    public function createGetOrderOmsTransitionsToolPlugin(): ToolPluginInterface
    {
        return new GetOrderOmsTransitionsToolPlugin();
    }

    public function createGetOrderDetailsToolPlugin(): ToolPluginInterface
    {
        return new GetOrderDetailsToolPlugin();
    }

    public function createGetOrderDetailsByIdToolPlugin(): ToolPluginInterface
    {
        return new GetOrderDetailsByIdToolPlugin();
    }

    public function createGetOrderManualEventsToolPlugin(): ToolPluginInterface
    {
        return new GetOrderManualEventsToolPlugin();
    }

    public function createGetOrderStateFlagsToolPlugin(): ToolPluginInterface
    {
        return new GetOrderStateFlagsToolPlugin();
    }

    public function createGetDiscountDetailsToolPlugin(): ToolPluginInterface
    {
        return new GetDiscountDetailsToolPlugin();
    }

    public function createCreateDiscountToolPlugin(): ToolPluginInterface
    {
        return new CreateDiscountToolPlugin();
    }

    public function createUpdateDiscountToolPlugin(): ToolPluginInterface
    {
        return new UpdateDiscountToolPlugin();
    }

    public function createSkillDiscountKnowledgeBaseToolPlugin(): ToolPluginInterface
    {
        return new SkillDiscountKnowledgeBaseToolPlugin();
    }

    public function createSkillOrderManagementKnowledgeBaseToolPlugin(): ToolPluginInterface
    {
        return new SkillOrderManagementKnowledgeBaseToolPlugin();
    }

    public function createFillFormToolPlugin(): ToolPluginInterface
    {
        return new FillFormToolPlugin();
    }

    public function createGetContentItemsToolPlugin(): ToolPluginInterface
    {
        return new GetContentItemsToolPlugin();
    }

    public function getAiFoundationFacade(): AiFoundationFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_AI_FOUNDATION);
    }

    public function getUserFacade(): UserFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_USER);
    }

    /**
     * @return array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface>
     */
    public function getBackofficeAssistantAgentPlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_BACKOFFICE_ASSISTANT_AGENT);
    }

    public function getCsrfTokenManager(): CsrfTokenManagerInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::SERVICE_FORM_CSRF_PROVIDER);
    }

    public function createSmartCmsContentRequestMapper(): SmartCmsContentRequestMapperInterface
    {
        return new SmartCmsContentRequestMapper();
    }

    public function createSmartCmsContentResponseMapper(): SmartCmsContentResponseMapperInterface
    {
        return new SmartCmsContentResponseMapper();
    }

    public function getTranslatorFacade(): TranslatorFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_TRANSLATOR);
    }

    public function createRegeneratedPromptMarker(): RegeneratedPromptMarker
    {
        return new RegeneratedPromptMarker();
    }
}
