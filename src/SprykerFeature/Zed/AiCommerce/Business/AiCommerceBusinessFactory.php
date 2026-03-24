<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business;

use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Agent\GeneralPurposeAgentExecutor;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Agent\GeneralPurposeAgentExecutorInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Attachment\AttachmentBuilder;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Attachment\AttachmentBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationCreator;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationCreatorInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationDeleter;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationDeleterInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationReader;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationUpdater;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationUpdaterInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Emitter\SseEventEmitter;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Emitter\SseEventEmitterInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Generator\ConversationReferenceGenerator;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Generator\ConversationReferenceGeneratorInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt\BackofficeAssistantPromptRequestValidator;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt\BackofficeAssistantPromptRequestValidatorInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt\IntentRouter;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt\IntentRouterInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt\PromptHandler;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt\PromptHandlerInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface getRepository()
 */
class AiCommerceBusinessFactory extends AbstractBusinessFactory
{
    public function createBackofficeAssistantConversationReader(): BackofficeAssistantConversationReaderInterface
    {
        return new BackofficeAssistantConversationReader(
            $this->getRepository(),
            $this->getAiFoundationFacade(),
        );
    }

    public function createBackofficeAssistantConversationCreator(): BackofficeAssistantConversationCreatorInterface
    {
        return new BackofficeAssistantConversationCreator(
            $this->getEntityManager(),
            $this->createConversationReferenceGenerator(),
        );
    }

    public function createConversationReferenceGenerator(): ConversationReferenceGeneratorInterface
    {
        return new ConversationReferenceGenerator();
    }

    public function createBackofficeAssistantConversationUpdater(): BackofficeAssistantConversationUpdaterInterface
    {
        return new BackofficeAssistantConversationUpdater(
            $this->getEntityManager(),
        );
    }

    public function createBackofficeAssistantConversationDeleter(): BackofficeAssistantConversationDeleterInterface
    {
        return new BackofficeAssistantConversationDeleter(
            $this->getEntityManager(),
        );
    }

    public function createSseEventEmitter(): SseEventEmitterInterface
    {
        return new SseEventEmitter();
    }

    public function createPromptHandler(): PromptHandlerInterface
    {
        return new PromptHandler(
            $this->createBackofficeAssistantConversationReader(),
            $this->createBackofficeAssistantConversationCreator(),
            $this->createBackofficeAssistantConversationUpdater(),
            $this->getAiFoundationFacade(),
            $this->getBackofficeAssistantAgentPlugins(),
            $this->createAttachmentBuilder(),
            $this->createSseEventEmitter(),
            $this->createIntentRouter(),
            $this->createBackofficeAssistantPromptRequestValidator(),
            $this->getGlossaryFacade(),
        );
    }

    public function getGlossaryFacade(): GlossaryFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_GLOSSARY);
    }

    public function createBackofficeAssistantPromptRequestValidator(): BackofficeAssistantPromptRequestValidatorInterface
    {
        return new BackofficeAssistantPromptRequestValidator($this->getConfig());
    }

    public function createIntentRouter(): IntentRouterInterface
    {
        return new IntentRouter(
            $this->getAiFoundationFacade(),
            $this->getBackofficeAssistantAgentPlugins(),
        );
    }

    public function getAiFoundationFacade(): AiFoundationFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_AI_FOUNDATION);
    }

    /**
     * @return array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface>
     */
    public function getBackofficeAssistantAgentPlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_BACKOFFICE_ASSISTANT_AGENT);
    }

    public function createAttachmentBuilder(): AttachmentBuilderInterface
    {
        return new AttachmentBuilder();
    }

    public function createGeneralPurposeAgentExecutor(): GeneralPurposeAgentExecutorInterface
    {
        return new GeneralPurposeAgentExecutor($this->getAiFoundationFacade());
    }
}
