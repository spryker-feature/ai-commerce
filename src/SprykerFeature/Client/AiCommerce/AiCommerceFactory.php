<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\AvailabilityStorage\AvailabilityStorageClientInterface;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Spryker\Client\CmsStorage\CmsStorageClientInterface;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\Money\MoneyClientInterface;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Client\Price\PriceClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductAlternativeStorage\ProductAlternativeStorageClientInterface;
use Spryker\Client\ProductBundleStorage\ProductBundleStorageClientInterface;
use Spryker\Client\ProductCategoryStorage\ProductCategoryStorageClientInterface;
use Spryker\Client\ProductDiscontinuedStorage\ProductDiscontinuedStorageClientInterface;
use Spryker\Client\ProductImageStorage\ProductImageStorageClientInterface;
use Spryker\Client\ProductLabelStorage\ProductLabelStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductOptionStorage\ProductOptionStorageClientInterface;
use Spryker\Client\ProductRelationStorage\ProductRelationStorageClientInterface;
use Spryker\Client\ProductReviewStorage\ProductReviewStorageClientInterface;
use Spryker\Client\ProductSetPageSearch\ProductSetPageSearchClientInterface;
use Spryker\Client\ProductSetStorage\ProductSetStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AiCommerce\SearchByImage\AiSearchByImageTermResolver;
use SprykerFeature\Client\AiCommerce\SearchByImage\AiSearchByImageTermResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Action\ActionItemOfferResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Action\ActionItemOfferResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\AgentSelector;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\AgentSelectorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\ProductDiscoveryChatRequestExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\ProductDiscoveryChatRequestExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\ProductDiscoveryToolNameResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent\ProductDiscoveryToolNameResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Attachment\AttachmentValidator;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Attachment\AttachmentValidatorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryNodeNameReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryNodeNameReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatter;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice\ChoiceOfferReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice\ChoiceOfferReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice\ChoiceOfferRegistry;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Choice\ChoiceOfferRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ComparisonRegistry;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ComparisonRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ProductComparisonBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison\ProductComparisonBuilderInterface;
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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ShownProductReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ShownProductReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ShownProductRegistry;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Conversation\ShownProductRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Gate\StorefrontAssistantFeatureGate;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Gate\StorefrontAssistantFeatureGateInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\PageContext\PageContextProductResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\PageContext\PageContextProductResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatter;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Price\StorefrontAssistantPriceFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReasonResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReasonResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\PriceOutlierMarker;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\PriceOutlierMarkerInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\PriceProductProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductAbstractIdResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductAbstractIdResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductCollectionExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductCollectionExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductImageProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductLabelProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductStorageProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductEnricher;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductEnricherInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ToolResultProductExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductAttributeDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductAvailabilityDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductBundleDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDiscontinuedDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductOfferDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductOptionDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductReviewDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductVolumePriceDetailsExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\ProductRelationIdReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\ProductRelationIdReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\ProductRelationReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\ProductRelationReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\RelatedProductCategoryFilter;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductRelation\RelatedProductCategoryFilterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductSet\ProductSetReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductSet\ProductSetReaderInterface;
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
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultMapperInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultRegistry;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchResultRegistryInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchZeroResultAnalyzer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSearchZeroResultAnalyzerInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSuggestionReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\CatalogSuggestionReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\DisplayProductRefinementExpander;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\DisplayProductRefinementExpanderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\ProductLabelNameResolver;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\ProductLabelNameResolverInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\RefinementChipBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\RefinementChipBuilderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchFilterConfigReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchFilterConfigReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchResultDataExtractor;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SearchResultDataExtractorInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SeeAllLinkBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SeeAllLinkBuilderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SuggestionResultFormatter;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search\SuggestionResultFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Setup\SetupProposalBuilder;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Setup\SetupProposalBuilderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage\ShopPageReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage\ShopPageReaderInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage\ShopPageTextExtractor;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ShopPage\ShopPageTextExtractorInterface;
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
            $this->createShownProductRegistry(),
            $this->createCatalogSearchResultRegistry(),
            $this->createChoiceOfferRegistry(),
            $this->createComparisonRegistry(),
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
            $this->createProductLabelProductExpander(),
            $this->createProductImageProductExpander(),
            $this->createPriceProductProductExpander(),
        ];
    }

    public function createProductStorageProductExpander(): ProductExpanderInterface
    {
        return new ProductStorageProductExpander($this->getProductStorageClient());
    }

    public function createProductLabelProductExpander(): ProductExpanderInterface
    {
        return new ProductLabelProductExpander($this->getProductLabelStorageClient());
    }

    public function createProductImageProductExpander(): ProductExpanderInterface
    {
        return new ProductImageProductExpander($this->getProductImageStorageClient());
    }

    public function createPriceProductProductExpander(): ProductExpanderInterface
    {
        return new PriceProductProductExpander(
            $this->getPriceProductStorageClient(),
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

    public function createPageContextProductResolver(): PageContextProductResolverInterface
    {
        return new PageContextProductResolver(
            $this->createProductAbstractIdResolver(),
            $this->getProductStorageClient(),
            $this->getProductLabelStorageClient(),
            $this->getStoreClient(),
        );
    }

    public function createProductAbstractIdResolver(): ProductAbstractIdResolverInterface
    {
        return new ProductAbstractIdResolver($this->getProductStorageClient());
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
            $this->createCatalogSearchZeroResultAnalyzer(),
            $this->createShownProductRegistry(),
            $this->createCatalogSearchResultRegistry(),
            $this->createCategoryNodeNameReader(),
            $this->createCatalogSearchResultExpander(),
        );
    }

    public function createCatalogSearchResultExpander(): CatalogSearchResultExpanderInterface
    {
        return new CatalogSearchResultExpander(
            $this->createToolResultProductExpander(),
            $this->createPriceOutlierMarker(),
        );
    }

    public function createCatalogSearchResultRegistry(): CatalogSearchResultRegistryInterface
    {
        return new CatalogSearchResultRegistry();
    }

    public function createRefinementChipBuilder(): RefinementChipBuilderInterface
    {
        return new RefinementChipBuilder(
            $this->createCatalogSearchArgumentResolver(),
            $this->createStorefrontAssistantPriceFormatter(),
            $this->getCategoryStorageClient(),
            $this->getLocaleClient(),
            $this->getStoreClient(),
        );
    }

    public function createDisplayProductRefinementExpander(): DisplayProductRefinementExpanderInterface
    {
        return new DisplayProductRefinementExpander(
            $this->createCatalogSearchResultRegistry(),
            $this->createRefinementChipBuilder(),
            $this->createSeeAllLinkBuilder(),
            $this->getConfig()->getStorefrontAssistantMaxRefinementChips(),
        );
    }

    public function createSeeAllLinkBuilder(): SeeAllLinkBuilderInterface
    {
        return new SeeAllLinkBuilder(
            $this->getCategoryStorageClient(),
            $this->getLocaleClient(),
            $this->getStoreClient(),
            DisplayProductReader::MAX_PRODUCTS,
        );
    }

    public function createCatalogSearchZeroResultAnalyzer(): CatalogSearchZeroResultAnalyzerInterface
    {
        return new CatalogSearchZeroResultAnalyzer($this->createCatalogSearchArgumentResolver());
    }

    public function createCatalogSearchArgumentResolver(): CatalogSearchArgumentResolverInterface
    {
        return new CatalogSearchArgumentResolver(
            $this->createCatalogSearchPageResolver(),
            $this->createProductLabelNameResolver(),
        );
    }

    public function createProductLabelNameResolver(): ProductLabelNameResolverInterface
    {
        return new ProductLabelNameResolver(
            $this->getProductLabelStorageClient(),
            $this->getLocaleClient(),
            $this->getStoreClient(),
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
            $this->createCatalogSearchZeroResultAnalyzer(),
        );
    }

    public function createPriceOutlierMarker(): PriceOutlierMarkerInterface
    {
        return new PriceOutlierMarker($this->getConfig()->getStorefrontAssistantPriceOutlierFactor());
    }

    public function createCatalogSearchRefinementMapper(): CatalogSearchRefinementMapperInterface
    {
        return new CatalogSearchRefinementMapper(
            $this->createCatalogSearchArgumentResolver(),
            $this->createSearchResultDataExtractor(),
            $this->createStorefrontAssistantPriceFormatter(),
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

    public function createStorefrontAssistantPriceFormatter(): StorefrontAssistantPriceFormatterInterface
    {
        return new StorefrontAssistantPriceFormatter(
            $this->getMoneyClient(),
            $this->getCurrencyClient(),
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
            $this->createShownProductRegistry(),
            $this->createDisplayProductReasonResolver(),
            $this->createPriceOutlierMarker(),
            $this->createCatalogSearchResultRegistry(),
            $this->createComparisonRegistry(),
        );
    }

    public function createDisplayProductReasonResolver(): DisplayProductReasonResolverInterface
    {
        return new DisplayProductReasonResolver();
    }

    public function createProductDetailsReader(): ProductDetailsReaderInterface
    {
        return new ProductDetailsReader(
            $this->createToolArgumentNormalizer(),
            $this->createProductAbstractIdResolver(),
            $this->getProductStorageClient(),
            $this->createToolResultProductExpander(),
            $this->getStoreClient(),
            $this->getLocaleClient(),
            $this->getProductDetailsExpanders(),
        );
    }

    /**
     * @return array<\SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductDetailsExpanderInterface>
     */
    public function getProductDetailsExpanders(): array
    {
        return [
            $this->createProductAttributeDetailsExpander(),
            $this->createProductDiscontinuedDetailsExpander(),
            $this->createProductAvailabilityDetailsExpander(),
            $this->createProductOptionDetailsExpander(),
            $this->createProductOfferDetailsExpander(),
            $this->createProductReviewDetailsExpander(),
            $this->createProductBundleDetailsExpander(),
            $this->createProductVolumePriceDetailsExpander(),
        ];
    }

    public function createProductVolumePriceDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductVolumePriceDetailsExpander(
            $this->getPriceProductStorageClient(),
            $this->getPermissionClient(),
            $this->createStorefrontAssistantPriceFormatter(),
            $this->createActionItemOfferResolver(),
        );
    }

    public function createProductBundleDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductBundleDetailsExpander(
            $this->getProductBundleStorageClient(),
            $this->getProductStorageClient(),
        );
    }

    public function createProductSetReader(): ProductSetReaderInterface
    {
        return new ProductSetReader(
            $this->getProductSetPageSearchClient(),
            $this->getProductSetStorageClient(),
            $this->getProductStorageClient(),
            $this->getStoreClient(),
            $this->getLocaleClient(),
        );
    }

    public function createProductReviewDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductReviewDetailsExpander($this->getProductReviewStorageClient());
    }

    public function createChoiceOfferReader(): ChoiceOfferReaderInterface
    {
        return new ChoiceOfferReader(
            $this->createToolArgumentNormalizer(),
            $this->createChoiceOfferRegistry(),
        );
    }

    public function createChoiceOfferRegistry(): ChoiceOfferRegistryInterface
    {
        return new ChoiceOfferRegistry();
    }

    public function createSetupProposalBuilder(): SetupProposalBuilderInterface
    {
        return new SetupProposalBuilder(
            $this->createToolResultProductExpander(),
            $this->createToolArgumentNormalizer(),
            $this->createShownProductRegistry(),
            $this->createStorefrontAssistantPriceFormatter(),
            $this->createDisplayProductReasonResolver(),
        );
    }

    public function createActionItemOfferResolver(): ActionItemOfferResolverInterface
    {
        return new ActionItemOfferResolver($this->getProductOfferStorageClient());
    }

    public function createProductDiscoveryChatRequestExpander(): ProductDiscoveryChatRequestExpanderInterface
    {
        return new ProductDiscoveryChatRequestExpander(
            $this->getLocaleClient(),
            $this->createPageContextProductResolver(),
        );
    }

    public function createProductDiscoveryToolNameResolver(): ProductDiscoveryToolNameResolverInterface
    {
        return new ProductDiscoveryToolNameResolver();
    }

    public function createProductComparisonBuilder(): ProductComparisonBuilderInterface
    {
        return new ProductComparisonBuilder(
            $this->createToolArgumentNormalizer(),
            $this->createProductDetailsReader(),
            $this->getGlossaryStorageClient(),
            $this->getLocaleClient(),
            $this->createComparisonRegistry(),
        );
    }

    public function createComparisonRegistry(): ComparisonRegistryInterface
    {
        return new ComparisonRegistry();
    }

    public function createProductAttributeDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductAttributeDetailsExpander();
    }

    public function createProductDiscontinuedDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductDiscontinuedDetailsExpander(
            $this->getProductDiscontinuedStorageClient(),
            $this->getProductAlternativeStorageClient(),
        );
    }

    public function createProductAvailabilityDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductAvailabilityDetailsExpander($this->getAvailabilityStorageClient());
    }

    public function createProductOptionDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductOptionDetailsExpander(
            $this->getProductOptionStorageClient(),
            $this->getGlossaryStorageClient(),
            $this->createStorefrontAssistantPriceFormatter(),
        );
    }

    public function createProductOfferDetailsExpander(): ProductDetailsExpanderInterface
    {
        return new ProductOfferDetailsExpander(
            $this->getProductOfferStorageClient(),
            $this->getPriceProductStorageClient(),
            $this->getMerchantStorageClient(),
            $this->getPermissionClient(),
            $this->createStorefrontAssistantPriceFormatter(),
        );
    }

    public function createProductRelationReader(): ProductRelationReaderInterface
    {
        return new ProductRelationReader(
            $this->createToolArgumentNormalizer(),
            $this->createProductRelationIdReader(),
            $this->getProductStorageClient(),
            $this->getProductCategoryStorageClient(),
            $this->createToolResultProductExpander(),
            $this->createCatalogSearchReader(),
            $this->getStoreClient(),
            $this->getLocaleClient(),
            $this->createRelatedProductCategoryFilter(),
        );
    }

    public function createRelatedProductCategoryFilter(): RelatedProductCategoryFilterInterface
    {
        return new RelatedProductCategoryFilter($this->getProductCategoryStorageClient());
    }

    public function createProductRelationIdReader(): ProductRelationIdReaderInterface
    {
        return new ProductRelationIdReader(
            $this->getProductRelationStorageClient(),
            $this->getProductAlternativeStorageClient(),
        );
    }

    public function createShopPageReader(): ShopPageReaderInterface
    {
        return new ShopPageReader(
            $this->createToolArgumentNormalizer(),
            $this->getCmsStorageClient(),
            $this->createShopPageTextExtractor(),
            $this->getStoreClient(),
            $this->getLocaleClient(),
        );
    }

    public function createShopPageTextExtractor(): ShopPageTextExtractorInterface
    {
        return new ShopPageTextExtractor();
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

    public function getProductLabelStorageClient(): ProductLabelStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_LABEL_STORAGE);
    }

    public function getAvailabilityStorageClient(): AvailabilityStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_AVAILABILITY_STORAGE);
    }

    public function getProductDiscontinuedStorageClient(): ProductDiscontinuedStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_DISCONTINUED_STORAGE);
    }

    public function getProductAlternativeStorageClient(): ProductAlternativeStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_ALTERNATIVE_STORAGE);
    }

    public function getProductOptionStorageClient(): ProductOptionStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_OPTION_STORAGE);
    }

    public function getMerchantStorageClient(): MerchantStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_MERCHANT_STORAGE);
    }

    public function getPermissionClient(): PermissionClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PERMISSION);
    }

    public function getProductRelationStorageClient(): ProductRelationStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_RELATION_STORAGE);
    }

    public function getProductCategoryStorageClient(): ProductCategoryStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_CATEGORY_STORAGE);
    }

    public function getCmsStorageClient(): CmsStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_CMS_STORAGE);
    }

    public function getProductReviewStorageClient(): ProductReviewStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_REVIEW_STORAGE);
    }

    public function getProductSetPageSearchClient(): ProductSetPageSearchClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_SET_PAGE_SEARCH);
    }

    public function getProductSetStorageClient(): ProductSetStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_SET_STORAGE);
    }

    public function getProductBundleStorageClient(): ProductBundleStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_PRODUCT_BUNDLE_STORAGE);
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
            $this->createProductDiscoveryChatRequestExpander(),
            $this->createCategoryTreeFormatter(),
            $this->createProductDiscoveryToolNameResolver(),
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

    public function createShownProductRegistry(): ShownProductRegistryInterface
    {
        return new ShownProductRegistry($this->createShownProductReader());
    }

    public function createShownProductReader(): ShownProductReaderInterface
    {
        return new ShownProductReader(
            $this->getAiFoundationClient(),
            $this->createConversationMessageExtractor(),
            $this->createConversationReferenceDeriver(),
            $this->getConfig(),
        );
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
