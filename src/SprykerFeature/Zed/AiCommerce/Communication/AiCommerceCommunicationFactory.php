<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication;

use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\User\Business\UserFacadeInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Attachment\AttachmentBuilder;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Attachment\AttachmentBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Emitter\SseEventEmitter;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Emitter\SseEventEmitterInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\BackofficeAssistantPromptRequestValidator;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\BackofficeAssistantPromptRequestValidatorInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\IntentRouter;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\IntentRouterInterface;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptProcessor;
use SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt\PromptProcessorInterface;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\CreateDiscountToolPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\FillFormToolPlugin;
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
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class AiCommerceCommunicationFactory extends AbstractCommunicationFactory
{
    public function createPromptHandler(): PromptProcessorInterface
    {
        return new PromptProcessor(
            $this->getFacade(),
            $this->getAiFoundationFacade(),
            $this->getBackofficeAssistantAgentPlugins(),
            $this->createAttachmentBuilder(),
            $this->createSseEventEmitter(),
            $this->createIntentRouter(),
            $this->createBackofficeAssistantPromptRequestValidator(),
            $this->getGlossaryFacade(),
        );
    }

    public function createAttachmentBuilder(): AttachmentBuilderInterface
    {
        return new AttachmentBuilder();
    }

    public function createSseEventEmitter(): SseEventEmitterInterface
    {
        return new SseEventEmitter();
    }

    public function createIntentRouter(): IntentRouterInterface
    {
        return new IntentRouter(
            $this->getAiFoundationFacade(),
            $this->getBackofficeAssistantAgentPlugins(),
        );
    }

    public function createBackofficeAssistantPromptRequestValidator(): BackofficeAssistantPromptRequestValidatorInterface
    {
        return new BackofficeAssistantPromptRequestValidator($this->getConfig());
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

    public function getGlossaryFacade(): GlossaryFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_GLOSSARY);
    }
}
