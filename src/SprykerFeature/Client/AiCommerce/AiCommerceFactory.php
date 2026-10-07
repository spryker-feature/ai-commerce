<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Money\MoneyClientInterface;
use Spryker\Client\Price\PriceClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductImageStorage\ProductImageStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\SearchByImage\AiSearchByImageTermResolver;
use SprykerFeature\Client\AiCommerce\SearchByImage\AiSearchByImageTermResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\AgentSelector;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\AgentSelectorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Attachment\AttachmentValidator;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Attachment\AttachmentValidatorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryNodeNameReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryNodeNameReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatter;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationCollectionDeleter;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationCollectionDeleterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationCollectionReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationCollectionReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationMessageCollectionReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationMessageCollectionReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationMessageExtractor;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationMessageExtractorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationReferenceDeriver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ConversationReferenceDeriverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Gate\StorefrontAssistantFeatureGate;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Gate\StorefrontAssistantFeatureGateInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\PriceProductProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductCollectionExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductCollectionExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductImageProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductStorageProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductEnricher;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductEnricherInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Prompt\PageContextSystemPromptComposer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Prompt\PageContextSystemPromptComposerInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Prompt\ProductDiscoveryPromptRequestBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Prompt\ProductDiscoveryPromptRequestBuilderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchArgumentResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchArgumentResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchPageResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchPageResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchRefinementMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchRefinementMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSuggestionReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSuggestionReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchFilterConfigReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchFilterConfigReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchResultDataExtractor;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchResultDataExtractorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SuggestionResultFormatter;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SuggestionResultFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage\ConversationStorage;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Storage\ConversationStorageInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\StorefrontAssistantChatStreamer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\StorefrontAssistantChatStreamerInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool\ToolArgumentNormalizerInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Translation\StorefrontAssistantTranslator;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Translation\StorefrontAssistantTranslatorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\ConversationReferenceValidator;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\ConversationReferenceValidatorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\StorefrontAssistantChatRequestValidator;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator\StorefrontAssistantChatRequestValidatorInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTracker;
use SprykerFeature\Shared\AiCommerce\Stream\StreamBlockTrackerInterface;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitter;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventEmitterInterface;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallIdResolver;
use SprykerFeature\Shared\AiCommerce\Stream\ToolCallIdResolverInterface;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 */
class AiCommerceFactory extends AbstractFactory
{
    public function createAiSearchByImageTermResolver(): AiSearchByImageTermResolverInterface
    {
        return new AiSearchByImageTermResolver(
            $this->getAiFoundationClient(),
            $this->getConfig(),
        );
    }

    public function createStorefrontAssistantFeatureGate(): StorefrontAssistantFeatureGateInterface
    {
        return new StorefrontAssistantFeatureGate($this->getConfig());
    }

    public function createStreamEventEmitter(): StreamEventEmitterInterface
    {
        return new StreamEventEmitter();
    }

    public function createStreamBlockTracker(): StreamBlockTrackerInterface
    {
        return new StreamBlockTracker($this->createStreamEventEmitter());
    }

    public function createToolCallIdResolver(): ToolCallIdResolverInterface
    {
        return new ToolCallIdResolver();
    }

    public function createStorefrontAssistantChatStreamer(): StorefrontAssistantChatStreamerInterface
    {
        return new StorefrontAssistantChatStreamer(
            $this->createStorefrontAssistantChatRequestValidator(),
            $this->createConversationStorage(),
            $this->createAgentSelector(),
            $this->createStreamEventEmitter(),
            $this->createStreamBlockTracker(),
            $this->createStorefrontAssistantTranslator(),
        );
    }

    public function createStorefrontAssistantChatRequestValidator(): StorefrontAssistantChatRequestValidatorInterface
    {
        return new StorefrontAssistantChatRequestValidator(
            $this->createStorefrontAssistantFeatureGate(),
            $this->createStorefrontAssistantAttachmentValidator(),
            $this->createConversationReferenceValidator(),
            $this->getConfig(),
        );
    }

    public function createConversationReferenceValidator(): ConversationReferenceValidatorInterface
    {
        return new ConversationReferenceValidator($this->getConfig());
    }

    public function createStorefrontAssistantTranslator(): StorefrontAssistantTranslatorInterface
    {
        return new StorefrontAssistantTranslator(
            $this->getGlossaryStorageClient(),
            $this->getLocaleClient(),
        );
    }

    public function getGlossaryStorageClient(): GlossaryStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_GLOSSARY_STORAGE);
    }

    public function createStorefrontAssistantAttachmentValidator(): AttachmentValidatorInterface
    {
        return new AttachmentValidator($this->getConfig());
    }

    public function createAgentSelector(): AgentSelectorInterface
    {
        return new AgentSelector(
            $this->createStorefrontAssistantFeatureGate(),
            $this->getStorefrontAssistantAgentPlugins(),
            $this->createStorefrontAssistantTranslator(),
        );
    }

    public function createToolResultProductExpander(): ToolResultProductExpanderInterface
    {
        return new ToolResultProductExpander(
            $this->createStorefrontAssistantProductMapper(),
            $this->createProductCollectionExpander(),
        );
    }

    public function createToolResultProductEnricher(): ToolResultProductEnricherInterface
    {
        return new ToolResultProductEnricher($this->createToolResultProductExpander());
    }

    public function createStorefrontAssistantProductMapper(): StorefrontAssistantProductMapperInterface
    {
        return new StorefrontAssistantProductMapper();
    }

    public function createProductCollectionExpander(): ProductCollectionExpanderInterface
    {
        return new ProductCollectionExpander(
            $this->getProductExpanders(),
            $this->getStorefrontAssistantProductCollectionExpanderPlugins(),
            $this->getStoreClient(),
            $this->getLocaleClient(),
        );
    }

    /**
     * @return array<\SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductExpanderInterface>
     */
    public function getProductExpanders(): array
    {
        return [
            $this->createProductStorageProductExpander(),
            $this->createProductImageProductExpander(),
            $this->createPriceProductProductExpander(),
        ];
    }

    public function createProductStorageProductExpander(): ProductExpanderInterface
    {
        return new ProductStorageProductExpander($this->getProductStorageClient());
    }

    public function createProductImageProductExpander(): ProductExpanderInterface
    {
        return new ProductImageProductExpander($this->getProductImageStorageClient());
    }

    public function createPriceProductProductExpander(): ProductExpanderInterface
    {
        return new PriceProductProductExpander(
            $this->getPriceProductStorageClient(),
            $this->getProductStorageClient(),
            $this->getProductOfferStorageClient(),
            $this->getMoneyClient(),
            $this->getPriceClient(),
            $this->getCurrencyClient(),
        );
    }

    public function createPageContextSystemPromptComposer(): PageContextSystemPromptComposerInterface
    {
        return new PageContextSystemPromptComposer($this->createCategoryTreeFormatter());
    }

    public function createSearchFilterConfigReader(): SearchFilterConfigReaderInterface
    {
        return new SearchFilterConfigReader($this->getSearchConfigExpanderPlugins());
    }

    public function createSuggestionResultFormatter(): SuggestionResultFormatterInterface
    {
        return new SuggestionResultFormatter();
    }

    public function createCatalogSearchReader(): CatalogSearchReaderInterface
    {
        return new CatalogSearchReader(
            $this->getCatalogClient(),
            $this->createToolArgumentNormalizer(),
            $this->createCatalogSearchArgumentResolver(),
            $this->createCatalogSearchResultMapper(),
            $this->createSearchFilterConfigReader(),
            $this->createCategoryNodeNameReader(),
            $this->createToolResultProductExpander(),
        );
    }

    public function createCatalogSearchArgumentResolver(): CatalogSearchArgumentResolverInterface
    {
        return new CatalogSearchArgumentResolver(
            $this->createCatalogSearchPageResolver(),
        );
    }

    public function createCatalogSearchPageResolver(): CatalogSearchPageResolverInterface
    {
        return new CatalogSearchPageResolver();
    }

    public function createCatalogSearchResultMapper(): CatalogSearchResultMapperInterface
    {
        return new CatalogSearchResultMapper(
            $this->createCatalogSearchRefinementMapper(),
            $this->createCatalogSearchPageResolver(),
            $this->createSearchResultDataExtractor(),
        );
    }

    public function createCatalogSearchRefinementMapper(): CatalogSearchRefinementMapperInterface
    {
        return new CatalogSearchRefinementMapper(
            $this->createCatalogSearchArgumentResolver(),
            $this->createSearchResultDataExtractor(),
        );
    }

    public function createCategoryNodeNameReader(): CategoryNodeNameReaderInterface
    {
        return new CategoryNodeNameReader(
            $this->getCategoryStorageClient(),
            $this->getLocaleClient(),
            $this->getStoreClient(),
        );
    }

    public function createSearchResultDataExtractor(): SearchResultDataExtractorInterface
    {
        return new SearchResultDataExtractor();
    }

    public function createCatalogSuggestionReader(): CatalogSuggestionReaderInterface
    {
        return new CatalogSuggestionReader(
            $this->getCatalogClient(),
            $this->createSuggestionResultFormatter(),
            $this->createToolArgumentNormalizer(),
        );
    }

    public function createDisplayProductReader(): DisplayProductReaderInterface
    {
        return new DisplayProductReader(
            $this->createToolResultProductExpander(),
            $this->createToolArgumentNormalizer(),
        );
    }

    public function createToolArgumentNormalizer(): ToolArgumentNormalizerInterface
    {
        return new ToolArgumentNormalizer();
    }

    /**
     * @return list<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface>
     */
    public function getSearchConfigExpanderPlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_SEARCH_CONFIG_EXPANDER);
    }

    public function createCategoryTreeFormatter(): CategoryTreeFormatterInterface
    {
        return new CategoryTreeFormatter(
            $this->getCategoryStorageClient(),
            $this->getStoreClient(),
            $this->getLocaleClient(),
            $this->getConfig()->getStorefrontAssistantCategoryTreeLimit(),
        );
    }

    /**
     * @return array<\SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantAgentPluginInterface>
     */
    public function getStorefrontAssistantAgentPlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_STOREFRONT_ASSISTANT_AGENT);
    }

    public function getProductImageStorageClient(): ProductImageStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_IMAGE_STORAGE);
    }

    public function getPriceProductStorageClient(): PriceProductStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE);
    }

    public function getProductOfferStorageClient(): ProductOfferStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_OFFER_STORAGE);
    }

    public function getMoneyClient(): MoneyClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_MONEY);
    }

    public function getPriceClient(): PriceClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRICE);
    }

    public function getCurrencyClient(): CurrencyClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_CURRENCY);
    }

    /**
     * @return array<\SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantProductCollectionExpanderPluginInterface>
     */
    public function getStorefrontAssistantProductCollectionExpanderPlugins(): array
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::PLUGINS_STOREFRONT_ASSISTANT_PRODUCT_COLLECTION_EXPANDER);
    }

    public function getCatalogClient(): CatalogClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_CATALOG);
    }

    public function getCategoryStorageClient(): CategoryStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_CATEGORY_STORAGE);
    }

    public function getProductStorageClient(): ProductStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    public function getStoreClient(): StoreClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_STORE);
    }

    public function getLocaleClient(): LocaleClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_LOCALE);
    }

    public function createStorefrontAssistantRequestMapper(): StorefrontAssistantRequestMapperInterface
    {
        return new StorefrontAssistantRequestMapper();
    }

    public function createProductDiscoveryPromptRequestBuilder(): ProductDiscoveryPromptRequestBuilderInterface
    {
        return new ProductDiscoveryPromptRequestBuilder(
            $this->createConversationReferenceDeriver(),
            $this->createStorefrontAssistantRequestMapper(),
            $this->createPageContextSystemPromptComposer(),
            $this->getConfig(),
        );
    }

    public function createConversationReferenceDeriver(): ConversationReferenceDeriverInterface
    {
        return new ConversationReferenceDeriver();
    }

    public function createConversationStorage(): ConversationStorageInterface
    {
        return new ConversationStorage(
            $this->getStorageClient(),
            $this->getConfig(),
        );
    }

    public function createConversationCollectionReader(): ConversationCollectionReaderInterface
    {
        return new ConversationCollectionReader(
            $this->createConversationStorage(),
            $this->getConfig(),
        );
    }

    public function createConversationCollectionDeleter(): ConversationCollectionDeleterInterface
    {
        return new ConversationCollectionDeleter(
            $this->createConversationStorage(),
            $this->getAiFoundationClient(),
            $this->createConversationReferenceDeriver(),
            $this->createConversationReferenceValidator(),
        );
    }

    public function createConversationMessageCollectionReader(): ConversationMessageCollectionReaderInterface
    {
        return new ConversationMessageCollectionReader(
            $this->createConversationStorage(),
            $this->getAiFoundationClient(),
            $this->createConversationMessageExtractor(),
            $this->createConversationReferenceDeriver(),
            $this->createToolResultProductEnricher(),
            $this->createConversationReferenceValidator(),
            $this->getConfig(),
        );
    }

    public function createConversationMessageExtractor(): ConversationMessageExtractorInterface
    {
        return new ConversationMessageExtractor();
    }

    public function getStorageClient(): StorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_STORAGE);
    }

    public function getAiFoundationClient(): AiFoundationClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION);
    }
}
