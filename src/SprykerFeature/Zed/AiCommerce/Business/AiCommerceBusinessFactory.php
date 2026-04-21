<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business;

use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\Category\Business\CategoryFacadeInterface;
use Spryker\Zed\Discount\Business\DiscountFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationCreator;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationCreatorInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationDeleter;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationDeleterInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationReader;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationUpdater;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Conversation\BackofficeAssistantConversationUpdaterInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement\DiscountDetailsReader;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement\DiscountDetailsReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement\DiscountWriter;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement\DiscountWriterInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OmsTransitionDataBuilder;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OmsTransitionDataBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderDetailsReader;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderDetailsReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderManualEventsReader;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderManualEventsReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderOmsTransitionsReader;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderOmsTransitionsReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderStateFlagsReader;
use SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\OrderManagement\OrderStateFlagsReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor\SmartProductManagementPromptExecutor;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor\SmartProductManagementPromptExecutorInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Generator\SmartProductManagementImageAltTextGenerator;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Generator\SmartProductManagementImageAltTextGeneratorInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Improver\SmartProductManagementContentImprover;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Improver\SmartProductManagementContentImproverInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Proposer\SmartProductManagementCategoryProposer;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Proposer\SmartProductManagementCategoryProposerInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Reader\SmartProductManagementCategoryReader;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Reader\SmartProductManagementCategoryReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Translator\SmartProductManagementCollectionTranslator;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Translator\SmartProductManagementCollectionTranslatorInterface;

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
        );
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

    public function createOrderOmsTransitionsReader(): OrderOmsTransitionsReaderInterface
    {
        return new OrderOmsTransitionsReader(
            $this->getRepository(),
            $this->getOmsFacade(),
            $this->createOmsTransitionDataBuilder(),
        );
    }

    public function createOmsTransitionDataBuilder(): OmsTransitionDataBuilderInterface
    {
        return new OmsTransitionDataBuilder();
    }

    public function createOrderDetailsReader(): OrderDetailsReaderInterface
    {
        return new OrderDetailsReader($this->getSalesFacade());
    }

    public function createOrderManualEventsReader(): OrderManualEventsReaderInterface
    {
        return new OrderManualEventsReader($this->getOmsFacade(), $this->getSalesFacade());
    }

    public function createOrderStateFlagsReader(): OrderStateFlagsReaderInterface
    {
        return new OrderStateFlagsReader($this->getRepository(), $this->getOmsFacade());
    }

    public function createDiscountDetailsReader(): DiscountDetailsReaderInterface
    {
        return new DiscountDetailsReader($this->getDiscountFacade());
    }

    public function createDiscountWriter(): DiscountWriterInterface
    {
        return new DiscountWriter($this->getDiscountFacade(), $this->getRepository());
    }

    public function createSmartProductManagementCategoryReader(): SmartProductManagementCategoryReaderInterface
    {
        return new SmartProductManagementCategoryReader(
            $this->getCategoryFacade(),
            $this->getLocaleFacade(),
        );
    }

    public function createSmartProductManagementCategoryProposer(): SmartProductManagementCategoryProposerInterface
    {
        return new SmartProductManagementCategoryProposer(
            $this->getUtilEncodingService(),
            $this->createSmartProductManagementCategoryReader(),
            $this->getConfig(),
            $this->createSmartProductManagementPromptExecutor(),
        );
    }

    public function createSmartProductManagementImageAltTextGenerator(): SmartProductManagementImageAltTextGeneratorInterface
    {
        return new SmartProductManagementImageAltTextGenerator(
            $this->getConfig(),
            $this->createSmartProductManagementPromptExecutor(),
        );
    }

    public function createSmartProductManagementCollectionTranslator(): SmartProductManagementCollectionTranslatorInterface
    {
        return new SmartProductManagementCollectionTranslator(
            $this->getConfig(),
            $this->createSmartProductManagementPromptExecutor(),
        );
    }

    public function createSmartProductManagementContentImprover(): SmartProductManagementContentImproverInterface
    {
        return new SmartProductManagementContentImprover(
            $this->getConfig(),
            $this->createSmartProductManagementPromptExecutor(),
        );
    }

    public function createSmartProductManagementPromptExecutor(): SmartProductManagementPromptExecutorInterface
    {
        return new SmartProductManagementPromptExecutor(
            $this->getAiFoundationFacade(),
            $this->getConfig(),
        );
    }

    public function getAiFoundationFacade(): AiFoundationFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_AI_FOUNDATION);
    }

    public function getDiscountFacade(): DiscountFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_DISCOUNT);
    }

    public function getOmsFacade(): OmsFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_OMS);
    }

    public function getSalesFacade(): SalesFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_SALES);
    }

    public function getCategoryFacade(): CategoryFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_CATEGORY);
    }

    public function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_LOCALE);
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
