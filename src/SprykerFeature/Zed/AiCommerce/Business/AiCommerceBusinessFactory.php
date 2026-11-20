<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business;

use Aws\BedrockAgent\BedrockAgentClient;
use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Spryker\Client\Queue\QueueClientInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\Category\Business\CategoryFacadeInterface;
use Spryker\Zed\ContentGui\Business\ContentGuiFacadeInterface;
use Spryker\Zed\DataImport\Business\DataImportFactoryTrait;
use Spryker\Zed\DataImport\Business\Model\DataImporterInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\Discount\Business\DiscountFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductAttachment\Business\ProductAttachmentFacadeInterface;
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
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Checker\BedrockKnowledgeBaseConfigurationChecker;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Checker\BedrockKnowledgeBaseConfigurationCheckerInterface;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Indexer\BedrockKnowledgeBaseDocumentIndexer;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Indexer\BedrockKnowledgeBaseDocumentIndexerInterface;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper\BedrockKnowledgeBaseDocumentMapper;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper\BedrockKnowledgeBaseDocumentMapperInterface;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper\BedrockKnowledgeBaseDocumentStatusMapper;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Mapper\BedrockKnowledgeBaseDocumentStatusMapperInterface;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Reader\BedrockKnowledgeBaseReferenceReader;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Reader\BedrockKnowledgeBaseReferenceReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Setup\BedrockKnowledgeBaseSetup;
use SprykerFeature\Zed\AiCommerce\Business\BedrockKnowledgeBase\Setup\BedrockKnowledgeBaseSetupInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Builder\KnowledgeDocumentIndexResponseBuilder;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Builder\KnowledgeDocumentIndexResponseBuilderInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Deleter\KnowledgeDocumentDeleter;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Deleter\KnowledgeDocumentDeleterInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentContentIndexer;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentContentIndexerInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentIndexer;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer\KnowledgeDocumentIndexerInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Mapper\KnowledgeDocumentIndexMessageMapper;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Mapper\KnowledgeDocumentIndexMessageMapperInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader\KnowledgeDocumentSourceReader;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader\KnowledgeDocumentSourceReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader\UrlDocumentContentReader;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader\UrlDocumentContentReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Refresher\KnowledgeDocumentStatusRefresher;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Refresher\KnowledgeDocumentStatusRefresherInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reindexer\KnowledgeDocumentReindexer;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reindexer\KnowledgeDocumentReindexerInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\HostAddressResolver;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\HostAddressResolverInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\KnowledgeDocumentSourcePluginResolver;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\KnowledgeDocumentSourcePluginResolverInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Sender\KnowledgeDocumentIndexMessageSender;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Sender\KnowledgeDocumentIndexMessageSenderInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Validator\KnowledgeDocumentIndexMessageValidator;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Validator\KnowledgeDocumentIndexMessageValidatorInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentEventWriter;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentEventWriterInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentIndexFailureWriter;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentIndexFailureWriterInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentPendingWriter;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentPendingWriterInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeStore\Setup\KnowledgeStoreSetup;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeStore\Setup\KnowledgeStoreSetupInterface;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeDocument\Reader\ProductAttachmentDocumentSourceReader;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeDocument\Reader\ProductAttachmentDocumentSourceReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\DataImport\Step\ProductAttachmentKnowledgeSelectionWriterStep;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Expander\ProductAttachmentKnowledgeSelectionExpander;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Expander\ProductAttachmentKnowledgeSelectionExpanderInterface;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Reader\ProductAttachmentKnowledgeSelectionReader;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Reader\ProductAttachmentKnowledgeSelectionReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Writer\ProductAttachmentKnowledgeSelectionWriter;
use SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Writer\ProductAttachmentKnowledgeSelectionWriterInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Collapser\SmartCmsContentItemHtmlCollapser;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Collapser\SmartCmsContentItemHtmlCollapserInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Expander\SmartCmsContentItemHtmlExpander;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Expander\SmartCmsContentItemHtmlExpanderInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Generator\SmartCmsContentGenerator;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Generator\SmartCmsContentGeneratorInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Reader\SmartCmsContentItemReader;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Reader\SmartCmsContentItemReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Downloader\ImageDownloader;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Downloader\ImageDownloaderInterface;
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
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface getRepository()
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AiCommerceBusinessFactory extends AbstractBusinessFactory
{
    use DataImportFactoryTrait;

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
            $this->getRepository(),
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
            $this->createImageDownloader(),
        );
    }

    public function createImageDownloader(): ImageDownloaderInterface
    {
        return new ImageDownloader($this->getConfig());
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

    public function createSmartCmsContentGenerator(): SmartCmsContentGeneratorInterface
    {
        return new SmartCmsContentGenerator(
            $this->getConfig(),
            $this->createSmartProductManagementPromptExecutor(),
            $this->createSmartCmsContentItemHtmlCollapser(),
            $this->createSmartCmsContentItemHtmlExpander(),
        );
    }

    public function createSmartCmsContentItemHtmlCollapser(): SmartCmsContentItemHtmlCollapserInterface
    {
        return new SmartCmsContentItemHtmlCollapser(
            $this->getContentGuiFacade(),
        );
    }

    public function createSmartCmsContentItemHtmlExpander(): SmartCmsContentItemHtmlExpanderInterface
    {
        return new SmartCmsContentItemHtmlExpander(
            $this->getContentGuiFacade(),
        );
    }

    public function createSmartCmsContentItemReader(): SmartCmsContentItemReaderInterface
    {
        return new SmartCmsContentItemReader(
            $this->getRepository(),
            $this->getConfig(),
            $this->getContentGuiEditorPlugins(),
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

    public function getContentGuiFacade(): ContentGuiFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_CONTENT_GUI);
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

    /**
     * @return array<\Spryker\Zed\ContentGuiExtension\Dependency\Plugin\ContentGuiEditorPluginInterface>
     */
    public function getContentGuiEditorPlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_CONTENT_GUI_EDITOR);
    }

    public function createKnowledgeDocumentEventWriter(): KnowledgeDocumentEventWriterInterface
    {
        return new KnowledgeDocumentEventWriter(
            $this->createKnowledgeDocumentSourcePluginResolver(),
            $this->createKnowledgeDocumentPendingWriter(),
            $this->createKnowledgeDocumentIndexMessageSender(),
            $this->getKnowledgeStorePlugin(),
        );
    }

    public function createKnowledgeDocumentReindexer(): KnowledgeDocumentReindexerInterface
    {
        return new KnowledgeDocumentReindexer(
            $this->createKnowledgeDocumentSourcePluginResolver(),
            $this->createKnowledgeDocumentPendingWriter(),
            $this->createKnowledgeDocumentIndexMessageSender(),
            $this->getConfig(),
            $this->getRepository(),
            $this->getKnowledgeStorePlugin(),
        );
    }

    public function createKnowledgeDocumentIndexer(): KnowledgeDocumentIndexerInterface
    {
        return new KnowledgeDocumentIndexer(
            $this->createKnowledgeDocumentIndexMessageValidator(),
            $this->createKnowledgeDocumentIndexMessageMapper(),
            $this->createKnowledgeDocumentSourcePluginResolver(),
            $this->createKnowledgeDocumentSourceReader(),
            $this->createKnowledgeDocumentContentIndexer(),
            $this->createKnowledgeDocumentDeleter(),
            $this->createKnowledgeDocumentIndexFailureWriter(),
            $this->getRepository(),
            $this->getKnowledgeStorePlugin(),
        );
    }

    public function createKnowledgeDocumentSourceReader(): KnowledgeDocumentSourceReaderInterface
    {
        return new KnowledgeDocumentSourceReader();
    }

    public function createKnowledgeDocumentContentIndexer(): KnowledgeDocumentContentIndexerInterface
    {
        return new KnowledgeDocumentContentIndexer(
            $this->getKnowledgeStorePlugin(),
            $this->createKnowledgeDocumentIndexResponseBuilder(),
            $this->getEntityManager(),
            $this->getConfig(),
        );
    }

    public function createKnowledgeDocumentDeleter(): KnowledgeDocumentDeleterInterface
    {
        return new KnowledgeDocumentDeleter(
            $this->getKnowledgeStorePlugin(),
            $this->createKnowledgeDocumentIndexResponseBuilder(),
            $this->getEntityManager(),
        );
    }

    public function createKnowledgeDocumentIndexFailureWriter(): KnowledgeDocumentIndexFailureWriterInterface
    {
        return new KnowledgeDocumentIndexFailureWriter(
            $this->createKnowledgeDocumentIndexMessageSender(),
            $this->getEntityManager(),
            $this->getConfig(),
        );
    }

    public function createKnowledgeDocumentIndexResponseBuilder(): KnowledgeDocumentIndexResponseBuilderInterface
    {
        return new KnowledgeDocumentIndexResponseBuilder();
    }

    public function createKnowledgeDocumentSourcePluginResolver(): KnowledgeDocumentSourcePluginResolverInterface
    {
        return new KnowledgeDocumentSourcePluginResolver($this->getKnowledgeDocumentSourcePlugins());
    }

    public function createKnowledgeDocumentPendingWriter(): KnowledgeDocumentPendingWriterInterface
    {
        return new KnowledgeDocumentPendingWriter(
            $this->getRepository(),
            $this->getEntityManager(),
        );
    }

    public function createKnowledgeDocumentIndexMessageSender(): KnowledgeDocumentIndexMessageSenderInterface
    {
        return new KnowledgeDocumentIndexMessageSender(
            $this->getQueueClient(),
            $this->createKnowledgeDocumentIndexMessageMapper(),
        );
    }

    public function createKnowledgeDocumentIndexMessageMapper(): KnowledgeDocumentIndexMessageMapperInterface
    {
        return new KnowledgeDocumentIndexMessageMapper($this->getUtilEncodingService());
    }

    public function createKnowledgeDocumentIndexMessageValidator(): KnowledgeDocumentIndexMessageValidatorInterface
    {
        return new KnowledgeDocumentIndexMessageValidator($this->getUtilEncodingService());
    }

    public function createUrlDocumentContentReader(): UrlDocumentContentReaderInterface
    {
        return new UrlDocumentContentReader(
            $this->getHttpClient(),
            $this->createHostAddressResolver(),
            $this->getConfig(),
        );
    }

    public function createHostAddressResolver(): HostAddressResolverInterface
    {
        return new HostAddressResolver();
    }

    public function createProductAttachmentDocumentSourceReader(): ProductAttachmentDocumentSourceReaderInterface
    {
        return new ProductAttachmentDocumentSourceReader(
            $this->getRepository(),
            $this->getLocaleFacade(),
        );
    }

    public function getProductAttachmentFacade(): ProductAttachmentFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_PRODUCT_ATTACHMENT);
    }

    public function getProductFacade(): ProductFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_PRODUCT);
    }

    public function getQueueClient(): QueueClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_QUEUE);
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::HTTP_CLIENT);
    }

    /**
     * @return list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface>
     */
    public function getKnowledgeDocumentSourcePlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_KNOWLEDGE_DOCUMENT_SOURCE);
    }

    public function getKnowledgeStorePlugin(): KnowledgeStorePluginInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGIN_KNOWLEDGE_STORE);
    }

    public function createKnowledgeDocumentStatusRefresher(): KnowledgeDocumentStatusRefresherInterface
    {
        return new KnowledgeDocumentStatusRefresher(
            $this->getKnowledgeStorePlugin(),
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getConfig(),
        );
    }

    public function createBedrockKnowledgeBaseSetup(): BedrockKnowledgeBaseSetupInterface
    {
        return new BedrockKnowledgeBaseSetup(
            $this->getBedrockAgentClient(),
            $this->getConfig(),
        );
    }

    public function createBedrockKnowledgeBaseDocumentIndexer(): BedrockKnowledgeBaseDocumentIndexerInterface
    {
        return new BedrockKnowledgeBaseDocumentIndexer(
            $this->getBedrockAgentClient(),
            $this->createBedrockKnowledgeBaseReferenceReader(),
            $this->createBedrockKnowledgeBaseDocumentMapper(),
            $this->createBedrockKnowledgeBaseDocumentStatusMapper(),
            $this->getConfig(),
        );
    }

    public function createBedrockKnowledgeBaseConfigurationChecker(): BedrockKnowledgeBaseConfigurationCheckerInterface
    {
        return new BedrockKnowledgeBaseConfigurationChecker($this->createBedrockKnowledgeBaseReferenceReader());
    }

    public function createBedrockKnowledgeBaseReferenceReader(): BedrockKnowledgeBaseReferenceReaderInterface
    {
        return new BedrockKnowledgeBaseReferenceReader($this->getConfig());
    }

    public function createBedrockKnowledgeBaseDocumentMapper(): BedrockKnowledgeBaseDocumentMapperInterface
    {
        return new BedrockKnowledgeBaseDocumentMapper($this->getConfig());
    }

    public function createBedrockKnowledgeBaseDocumentStatusMapper(): BedrockKnowledgeBaseDocumentStatusMapperInterface
    {
        return new BedrockKnowledgeBaseDocumentStatusMapper();
    }

    public function getBedrockAgentClient(): BedrockAgentClient
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::BEDROCK_AGENT_CLIENT);
    }

    public function createKnowledgeStoreSetup(): KnowledgeStoreSetupInterface
    {
        return new KnowledgeStoreSetup(
            $this->getKnowledgeStorePlugin(),
            $this->createKnowledgeDocumentSourcePluginResolver(),
        );
    }

    public function createProductAttachmentKnowledgeSelectionReader(): ProductAttachmentKnowledgeSelectionReaderInterface
    {
        return new ProductAttachmentKnowledgeSelectionReader($this->getRepository());
    }

    public function createProductAttachmentKnowledgeSelectionExpander(): ProductAttachmentKnowledgeSelectionExpanderInterface
    {
        return new ProductAttachmentKnowledgeSelectionExpander($this->createProductAttachmentKnowledgeSelectionReader());
    }

    public function createProductAttachmentKnowledgeSelectionWriter(): ProductAttachmentKnowledgeSelectionWriterInterface
    {
        return new ProductAttachmentKnowledgeSelectionWriter(
            $this->createProductAttachmentKnowledgeSelectionReader(),
            $this->getEntityManager(),
        );
    }

    public function getProductAttachmentKnowledgeSelectionDataImporter(
        ?DataImporterConfigurationTransfer $dataImporterConfigurationTransfer = null,
    ): DataImporterInterface {
        /** @var \Spryker\Zed\DataImport\Business\Model\DataImporter $dataImporter */
        $dataImporter = $this->getProductAttachmentKnowledgeSelectionCsvDataImporter($dataImporterConfigurationTransfer);

        /** @var \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetStepBroker $dataSetStepBroker */
        $dataSetStepBroker = $this->createTransactionAwareDataSetStepBroker();
        $dataSetStepBroker->addStep($this->createProductAttachmentKnowledgeSelectionWriterStep());

        $dataImporter->addDataSetStepBroker($dataSetStepBroker);

        return $dataImporter;
    }

    public function getProductAttachmentKnowledgeSelectionCsvDataImporter(
        ?DataImporterConfigurationTransfer $dataImporterConfigurationTransfer = null,
    ): DataImporterInterface {
        if ($dataImporterConfigurationTransfer?->getReaderConfiguration() !== null) {
            return $this->getDataImportFactory()->getCsvDataImporterFromConfig($dataImporterConfigurationTransfer);
        }

        return $this->getCsvDataImporterFromConfig(
            $this->getConfig()->getProductAttachmentKnowledgeSelectionDataImporterConfiguration(),
        );
    }

    public function createProductAttachmentKnowledgeSelectionWriterStep(): DataImportStepInterface
    {
        return new ProductAttachmentKnowledgeSelectionWriterStep(
            $this->getProductFacade(),
            $this->getLocaleFacade(),
            $this->getProductAttachmentFacade(),
            $this->createProductAttachmentKnowledgeSelectionReader(),
            $this->getEntityManager(),
        );
    }
}
