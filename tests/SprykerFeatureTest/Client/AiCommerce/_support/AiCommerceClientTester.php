<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce;

use ArrayObject;
use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\AiToolCallTransfer;
use Generated\Shared\Transfer\CategoryNodeStorageTransfer;
use Generated\Shared\Transfer\CmsPageStorageTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductCollectionTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductConditionsTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductCriteriaTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductTransfer;
use Generated\Shared\Transfer\ConversationHistoryCollectionTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\FacetConfigTransfer;
use Generated\Shared\Transfer\MerchantStorageTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\ProductAbstractAvailabilityTransfer;
use Generated\Shared\Transfer\ProductAbstractCategoryStorageTransfer;
use Generated\Shared\Transfer\ProductAbstractOptionStorageTransfer;
use Generated\Shared\Transfer\ProductBundleStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductBundleStorageTransfer;
use Generated\Shared\Transfer\ProductCategoryStorageTransfer;
use Generated\Shared\Transfer\ProductConcreteAvailabilityTransfer;
use Generated\Shared\Transfer\ProductDiscontinuedStorageTransfer;
use Generated\Shared\Transfer\ProductForProductBundleStorageTransfer;
use Generated\Shared\Transfer\ProductLabelDictionaryItemTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Generated\Shared\Transfer\ProductOptionGroupStorageTransfer;
use Generated\Shared\Transfer\ProductOptionValueStorageTransfer;
use Generated\Shared\Transfer\ProductReviewStorageTransfer;
use Generated\Shared\Transfer\ProductSetDataStorageTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RangeSearchResultTransfer;
use Generated\Shared\Transfer\SearchByImagePromptResponseTransfer;
use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchConfigExtensionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationConditionsTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationDeleteConditionsTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageConditionsTransfer;
use Generated\Shared\Transfer\StorefrontAssistantPageContextTransfer;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\ProductLabel\Persistence\SpyProductLabelQuery;
use Orm\Zed\Store\Persistence\SpyStoreQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use ReflectionProperty;
use RuntimeException;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\AvailabilityStorage\AvailabilityStorageClientInterface;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Spryker\Client\CmsStorage\CmsStorageClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\Permission\PermissionClientInterface;
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
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Client\Storage\StorageDependencyProvider;
use Spryker\Client\StorageRedis\Plugin\StorageRedisPlugin;
use Spryker\Client\Store\StoreClientInterface;
use Spryker\Client\Store\StoreDependencyProvider as ClientStoreDependencyProvider;
use Spryker\Client\StoreStorage\Plugin\Store\StoreStorageStoreExpanderPlugin;
use Spryker\Shared\ProductStorage\ProductStorageConfig as SharedProductStorageConfig;
use Spryker\Zed\Country\Communication\Plugin\Store\CountryStoreCollectionExpanderPlugin;
use Spryker\Zed\Currency\Communication\Plugin\Store\CurrencyStoreCollectionExpanderPlugin;
use Spryker\Zed\Locale\Communication\Plugin\Store\LocaleStoreCollectionExpanderPlugin;
use Spryker\Zed\Store\StoreDependencyProvider as ZedStoreDependencyProvider;
use SprykerFeature\Client\AiCommerce\AiCommerceClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSsePostToolCallPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSsePreToolCallPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSseStreamEventPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\ProductDiscoveryAgentPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CompareProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplaySetupToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\OfferChoicesToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductDetailsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductRelationsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductSetsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ReadShopPageToolPlugin;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatterInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\DisplayProductReader;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Setup\SetupProposalBuilder;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Shared\AiCommerce\Stream\StreamEventKey;
use SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\RecordingTranscriptAiFoundationClient;

/**
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getModuleConfig()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceClient getClient()
 *
 * @SuppressWarnings(PHPMD)
 */
class AiCommerceClientTester extends Actor
{
    use _generated\AiCommerceClientTesterActions;

    /**
     * @var list<\Generated\Shared\Transfer\ConcreteAlternativeProductCriteriaTransfer>
     */
    protected array $concreteAlternativeProductCriteriaTransfers = [];

    protected const string FAKE_IMAGE_DATA = 'fake-image-data';

    protected const string IMAGE_MEDIA_TYPE = 'image/jpeg';

    protected const string DEFAULT_ERROR_MESSAGE = 'AI service error';

    protected const int PRODUCT_ABSTRACT_LOOKUP_LIMIT = 200;

    protected const string SERVICE_STORE = 'store';

    protected const string RESULT_FORMATTER_KEY_PRODUCTS = 'products';

    protected const string RESULT_FORMATTER_KEY_FACETS = 'facets';

    protected const string RESULT_FORMATTER_KEY_PAGINATION = 'pagination';

    protected const string PAGINATION_KEY_NUM_FOUND = 'num_found';

    protected const string RESULT_FORMATTER_KEY_SPELLING_SUGGESTION = 'spellingSuggestion';

    protected const string RESULT_FORMATTER_KEY_COMPLETION = 'completion';

    protected const string RESULT_FORMATTER_KEY_SUGGESTION_BY_TYPE = 'suggestionByType';

    protected const string FACET_KEY_NAME = 'name';

    protected const string FACET_KEY_VALUES = 'values';

    protected const string FACET_NAME_CATEGORY = 'category';

    protected const string FACET_VALUE_KEY_VALUE = 'value';

    protected const string FACET_VALUE_KEY_DOC_COUNT = 'doc_count';

    protected const int FACET_VALUE_DOC_COUNT = 7;

    protected const string PRODUCT_KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string PRODUCT_STORAGE_MAPPING_TYPE_SKU = 'sku';

    protected const string PRODUCT_KEY_COST_PRICE = 'cost_price';

    protected const string PRODUCT_KEY_PRICE = 'price';

    protected const string RESULT_KEY_PRODUCTS = 'products';

    protected const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    protected const string RESULT_KEY_DISPLAYED_COUNT = 'displayedCount';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string KEY_ROLE = 'role';

    protected const string KEY_CONTENT = 'content';

    protected const string KEY_TYPE = 'type';

    protected const string KEY_TOOLS = 'tools';

    protected const string KEY_NAME = 'name';

    protected const string KEY_RESULT = 'result';

    protected const string ROLE_USER = 'user';

    protected const string MESSAGE_TYPE_TOOL_CALL_RESULT = 'tool_call_result';

    protected const int PRODUCT_COST_PRICE = 1000;

    protected const string USER_MESSAGE = 'What do you sell?';

    protected const string ASSISTANT_MESSAGE = 'We sell cameras and lenses.';

    protected const string MESSAGE_AI_PROVIDER_UNREACHABLE = 'The AI provider is unreachable.';

    protected const string CONVERSATION_REFERENCE_PREFIX = 'test-conversation-';

    protected const string CUSTOMER_REFERENCE_PREFIX = 'DE--test-customer-';

    protected const string SSE_DATA_PREFIX = 'data: ';

    protected const string SYSTEM_PROMPT = 'You help the customer find products.';

    protected const string ATTACHMENT_FILENAME_PNG = 'photo.png';

    protected const string ATTACHMENT_FILENAME_EXTENSION_PNG = '.png';

    protected const string MEDIA_TYPE_PNG = 'image/png';

    protected const string SIGNATURE_PNG = "\x89PNG\r\n\x1A\n";

    protected const string SIGNATURE_GIF = 'GIF89a';

    protected const string ATTACHMENT_PAYLOAD = 'small';

    protected const string STORAGE_KEY_PATTERN_CONVERSATION = 'ai_commerce:storefront_assistant:conversation:%s:%s';

    protected const string STORAGE_KEY_PATTERN_CONVERSATION_INDEX = 'ai_commerce:storefront_assistant:index:%s';

    protected const string AI_CONFIGURATION_NAME_STOREFRONT_ASSISTANT = 'AI_COMMERCE:AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI';

    /**
     * @var list<string>
     */
    protected const array CUSTOMER_FACING_TOOL_NAMES = [
        DisplayProductsToolPlugin::TOOL_NAME,
        CompareProductsToolPlugin::TOOL_NAME,
        OfferChoicesToolPlugin::TOOL_NAME,
        DisplaySetupToolPlugin::TOOL_NAME,
    ];

    public const string CAPTURED_CALL_KEY_SEARCH_STRING = 'searchString';

    public const string CAPTURED_CALL_KEY_REQUEST_PARAMETERS = 'requestParameters';

    public function setUpCurrentStore(): string
    {
        $storeEntity = SpyStoreQuery::create()->orderByIdStore(Criteria::ASC)->findOne();
        $storeName = (string)$storeEntity?->getName();

        if (!defined('APPLICATION_STORE')) {
            define('APPLICATION_STORE', $storeName);
        }

        // Dynamic store resolves the store from an application service that the HTTP request normally sets.
        $this->getContainer()->set(static::SERVICE_STORE, $storeName);
        $this->setDependency(StorageDependencyProvider::PLUGIN_STORAGE, new StorageRedisPlugin());
        $this->setDependency(ZedStoreDependencyProvider::PLUGINS_STORE_COLLECTION_EXPANDER, [
            new CountryStoreCollectionExpanderPlugin(),
            new CurrencyStoreCollectionExpanderPlugin(),
            new LocaleStoreCollectionExpanderPlugin(),
        ]);
        $this->setDependency(ClientStoreDependencyProvider::PLUGINS_STORE_EXPANDER, [
            new StoreStorageStoreExpanderPlugin(),
        ]);

        return $storeName;
    }

    public function getIdProductAbstractResolvableByExpanders(): int
    {
        $idProductAbstract = $this->getIdProductAbstractFromStorage();
        $result = (new DisplayProductsToolPlugin())
            ->execute([static::PARAMETER_ID_PRODUCT_ABSTRACTS => [$idProductAbstract]]);

        if (($result[static::RESULT_KEY_DISPLAYED_COUNT] ?? 0) === 0) {
            $this->markTestSkipped(
                'The product expanders resolve nothing in this suite: it reads the core ProductStorage '
                . 'config, which keys storage per store, while this environment publishes under the unified store key.',
            );
        }

        return $idProductAbstract;
    }

    public function getIdProductAbstractFromStorage(): int
    {
        $this->setUpCurrentStore();
        $productStorageClient = $this->getLocator()->productStorage()->client();
        $localeName = $this->getLocator()->locale()->client()->getCurrentLocale();
        $productAbstractStorageStoreName = $this->getProductAbstractStorageStoreName();

        $productAbstractEntities = SpyProductAbstractQuery::create()
            ->orderByIdProductAbstract(Criteria::ASC)
            ->limit(static::PRODUCT_ABSTRACT_LOOKUP_LIMIT)
            ->find();

        foreach ($productAbstractEntities as $productAbstractEntity) {
            $idProductAbstract = $productAbstractEntity->getIdProductAbstract();
            $productStorageData = $productStorageClient
                ->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
                    [$idProductAbstract],
                    $localeName,
                    $productAbstractStorageStoreName,
                );

            if (($productStorageData[$idProductAbstract]['name'] ?? '') !== '') {
                return $idProductAbstract;
            }
        }

        $this->markTestSkipped(sprintf(
            'No product abstract is readable from storage for store "%s" and locale "%s". Run publish & synchronize to populate it.',
            $productAbstractStorageStoreName,
            $localeName,
        ));
    }

    public function getStorageClient(): StorageClientInterface
    {
        $this->setDependency(StorageDependencyProvider::PLUGIN_STORAGE, new StorageRedisPlugin());

        return $this->getLocator()->storage()->client();
    }

    public function getProductStorageClient(): ProductStorageClientInterface
    {
        return $this->getLocator()->productStorage()->client();
    }

    public function getProductImageStorageClient(): ProductImageStorageClientInterface
    {
        return $this->getLocator()->productImageStorage()->client();
    }

    public function getStoreClient(): StoreClientInterface
    {
        return $this->getLocator()->store()->client();
    }

    public function getLocaleClient(): LocaleClientInterface
    {
        return $this->getLocator()->locale()->client();
    }

    /**
     * @param array<int, string> $keys
     */
    public function cleanUpStorageKeys(array $keys): void
    {
        $storageClient = $this->getStorageClient();

        foreach ($keys as $key) {
            $storageClient->delete($key);
        }
    }

    /**
     * @param array<string, mixed> $searchResult
     */
    public function setUpCatalogSearchResult(array $searchResult): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_CATALOG,
            Stub::makeEmpty(CatalogClientInterface::class, ['catalogSearch' => $searchResult]),
        );
    }

    /**
     * @param array<string, bool> $isMultiValuedByParameterName
     */
    public function setUpFilterableFacetConfigs(array $isMultiValuedByParameterName): void
    {
        $searchConfigExtensionTransfer = new SearchConfigExtensionTransfer();

        foreach ($isMultiValuedByParameterName as $parameterName => $isMultiValued) {
            $searchConfigExtensionTransfer->addFacetConfig(
                (new FacetConfigTransfer())
                    ->setName($parameterName)
                    ->setParameterName($parameterName)
                    ->setIsMultiValued($isMultiValued),
            );
        }

        $this->setDependency(
            AiCommerceDependencyProvider::PLUGINS_SEARCH_CONFIG_EXPANDER,
            [
                Stub::makeEmpty(SearchConfigExpanderPluginInterface::class, [
                    'getSearchConfigExtension' => $searchConfigExtensionTransfer,
                ]),
            ],
        );
    }

    /**
     * @param array<int, string> $facetValues
     *
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithFacet(string $facetName, array $facetValues): array
    {
        return $this->createCatalogSearchResultWithFacets([
            $this->createCatalogSearchFacet($facetName, $facetValues),
        ]);
    }

    /**
     * @param array<int, string> $facetValues
     *
     * @return array<string, mixed>
     */
    public function createCatalogSearchFacet(string $facetName, array $facetValues): array
    {
        $mappedFacetValues = [];

        foreach ($facetValues as $facetValue) {
            $mappedFacetValues[] = [
                static::FACET_VALUE_KEY_VALUE => $facetValue,
                static::FACET_VALUE_KEY_DOC_COUNT => static::FACET_VALUE_DOC_COUNT,
            ];
        }

        return [
            static::FACET_KEY_NAME => $facetName,
            static::FACET_KEY_VALUES => $mappedFacetValues,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithTotal(int $totalResults): array
    {
        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => [],
            static::RESULT_FORMATTER_KEY_PAGINATION => [
                static::PAGINATION_KEY_NUM_FOUND => $totalResults,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithSpellingSuggestion(
        int $totalResults,
        string $spellingSuggestion
    ): array {
        return $this->createCatalogSearchResultWithTotal($totalResults)
            + [static::RESULT_FORMATTER_KEY_SPELLING_SUGGESTION => $spellingSuggestion];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithProducts(int $productCount): array
    {
        $products = [];

        for ($i = 1; $i <= $productCount; $i++) {
            $products[] = [static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => $i];
        }

        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => $products,
            static::RESULT_FORMATTER_KEY_PAGINATION => [
                static::PAGINATION_KEY_NUM_FOUND => $productCount,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $suggestResult
     */
    public function setUpCatalogSuggestResult(array $suggestResult): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_CATALOG,
            Stub::makeEmpty(CatalogClientInterface::class, ['catalogSuggestSearch' => $suggestResult]),
        );
    }

    /**
     * @param array<int, string> $completionTerms
     * @param array<string, array<int, array<string, mixed>>> $suggestionsByType
     *
     * @return array<string, mixed>
     */
    public function createCatalogSuggestResult(array $completionTerms, array $suggestionsByType): array
    {
        return [
            static::RESULT_FORMATTER_KEY_COMPLETION => $completionTerms,
            static::RESULT_FORMATTER_KEY_SUGGESTION_BY_TYPE => $suggestionsByType,
        ];
    }

    /**
     * @param array<string, mixed> $searchResult
     * @param array<string, mixed> $capturedRequestParameters
     */
    public function setUpCatalogSearchResultCapturingRequestParameters(
        array $searchResult,
        array &$capturedRequestParameters,
        ?string &$capturedSearchString = null
    ): void {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_CATALOG,
            Stub::makeEmpty(CatalogClientInterface::class, [
                'catalogSearch' => function (
                    string $searchString,
                    array $requestParameters
                ) use (
                    $searchResult,
                    &$capturedRequestParameters,
                    &$capturedSearchString,
                ): array {
                    $capturedRequestParameters = $requestParameters;
                    $capturedSearchString = $searchString;

                    return $searchResult;
                },
            ]),
        );
    }

    public function getProductAbstractStorageStoreName(): string
    {
        return SharedProductStorageConfig::PRODUCT_ABSTRACT_STORAGE_UNIFIED_STORE_KEY;
    }

    /**
     * @return array<string, mixed>
     */
    public function createCatalogSearchResult(int $idProductAbstract): array
    {
        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => [
                [
                    static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract,
                    static::PRODUCT_KEY_COST_PRICE => static::PRODUCT_COST_PRICE,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithPrice(int $idProductAbstract, int $price): array
    {
        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => [
                [
                    static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract,
                    static::PRODUCT_KEY_PRICE => $price,
                ],
            ],
        ];
    }

    public function findDisplayedProductPrice(int $idProductAbstract): ?int
    {
        $price = $this->getDisplayedProductCards([$idProductAbstract])[0][static::PRODUCT_KEY_PRICE] ?? null;

        return is_int($price) ? $price : null;
    }

    public function getUnknownIdProductAbstract(): int
    {
        $productAbstractEntity = SpyProductAbstractQuery::create()
            ->orderByIdProductAbstract(Criteria::DESC)
            ->findOne();

        return ($productAbstractEntity?->getIdProductAbstract() ?? 0) + 1;
    }

    public function createSearchByImageRequest(): SearchByImageRequestTransfer
    {
        return (new SearchByImageRequestTransfer())
            ->setImageContent(base64_encode(static::FAKE_IMAGE_DATA))
            ->setImageMediaType(static::IMAGE_MEDIA_TYPE);
    }

    public function createSuccessfulSearchByImagePromptResponse(string $searchTerm): PromptResponseTransfer
    {
        return (new PromptResponseTransfer())
            ->setIsSuccessful(true)
            ->setStructuredMessage((new SearchByImagePromptResponseTransfer())->setSearchTerm($searchTerm));
    }

    public function createFailedPromptResponse(string $errorMessage = self::DEFAULT_ERROR_MESSAGE): PromptResponseTransfer
    {
        return (new PromptResponseTransfer())
            ->setIsSuccessful(false)
            ->addError((new ErrorTransfer())->setMessage($errorMessage));
    }

    public function enableStorefrontAssistant(bool $isEnabled = true, bool $isAgentEnabled = true): void
    {
        $this->setDependency(StorageDependencyProvider::PLUGIN_STORAGE, new StorageRedisPlugin());
        $this->mockConfigMethod('isStorefrontAssistantEnabled', $isEnabled);
        $this->mockConfigMethod('isStorefrontAssistantAgentEnabled', $isAgentEnabled);
        $this->mockConfigMethod('getStorefrontAssistantAiConfigurationName', static::AI_CONFIGURATION_NAME_STOREFRONT_ASSISTANT);
        $this->mockConfigMethod('getStorefrontAssistantCustomerFacingToolNames', static::CUSTOMER_FACING_TOOL_NAMES);
    }

    public function createStorefrontAssistantSsePreToolCallPlugin(): StorefrontAssistantSsePreToolCallPlugin
    {
        $storefrontAssistantSsePreToolCallPlugin = new StorefrontAssistantSsePreToolCallPlugin();
        $storefrontAssistantSsePreToolCallPlugin->setFactory($this->getFactory());
        $storefrontAssistantSsePreToolCallPlugin->setConfig($this->getModuleConfig());

        return $storefrontAssistantSsePreToolCallPlugin;
    }

    public function createStorefrontAssistantSsePostToolCallPlugin(): StorefrontAssistantSsePostToolCallPlugin
    {
        $storefrontAssistantSsePostToolCallPlugin = new StorefrontAssistantSsePostToolCallPlugin();
        $storefrontAssistantSsePostToolCallPlugin->setFactory($this->getFactory());
        $storefrontAssistantSsePostToolCallPlugin->setConfig($this->getModuleConfig());

        return $storefrontAssistantSsePostToolCallPlugin;
    }

    public function createStorefrontAssistantSseStreamEventPlugin(): StorefrontAssistantSseStreamEventPlugin
    {
        $storefrontAssistantSseStreamEventPlugin = new StorefrontAssistantSseStreamEventPlugin();
        $storefrontAssistantSseStreamEventPlugin->setFactory($this->getFactory());
        $storefrontAssistantSseStreamEventPlugin->setConfig($this->getModuleConfig());

        return $storefrontAssistantSseStreamEventPlugin;
    }

    public function haveStorefrontAssistantConversationIndexLimit(int $conversationIndexLimit): void
    {
        $this->mockConfigMethod('getStorefrontAssistantConversationIndexLimit', $conversationIndexLimit);
    }

    public function getStorefrontAssistantClient(): AiCommerceClientInterface
    {
        $aiCommerceClient = $this->getClient();
        // The module ships no shared config class, so the Client helper silently drops a mocked module config.
        $aiCommerceFactory = (new ReflectionProperty($aiCommerceClient, 'factory'))->getValue($aiCommerceClient);
        $aiCommerceFactory->setConfig($this->getModuleConfig());

        return $aiCommerceClient;
    }

    public function haveStorefrontAssistantAgentPlugin(
        string $assistantMessage = self::ASSISTANT_MESSAGE
    ): RecordingTranscriptAiFoundationClient {
        $recordingTranscriptAiFoundationClient = new RecordingTranscriptAiFoundationClient($assistantMessage);

        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION,
            $recordingTranscriptAiFoundationClient,
        );
        $this->setDependency(
            AiCommerceDependencyProvider::PLUGINS_STOREFRONT_ASSISTANT_AGENT,
            [new ProductDiscoveryAgentPlugin()],
        );
        $this->setUpCurrentStore();

        return $recordingTranscriptAiFoundationClient;
    }

    public function haveNoStorefrontAssistantAgentPlugin(): void
    {
        $this->setDependency(AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION, new RecordingTranscriptAiFoundationClient());
        $this->setDependency(AiCommerceDependencyProvider::PLUGINS_STOREFRONT_ASSISTANT_AGENT, []);
    }

    public function haveThrowingStorefrontAssistantAgentPlugin(): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION,
            Stub::makeEmpty(AiFoundationClientInterface::class, [
                'streamPrompt' => function (): PromptResponseTransfer {
                    throw new RuntimeException(static::MESSAGE_AI_PROVIDER_UNREACHABLE);
                },
            ]),
        );
        $this->setDependency(
            AiCommerceDependencyProvider::PLUGINS_STOREFRONT_ASSISTANT_AGENT,
            [new ProductDiscoveryAgentPlugin()],
        );
        $this->setUpCurrentStore();
    }

    public function haveFailingStorefrontAssistantAgentPlugin(string $errorMessage): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION,
            Stub::makeEmpty(AiFoundationClientInterface::class, [
                'streamPrompt' => fn (): PromptResponseTransfer => $this->createFailedPromptResponse($errorMessage),
            ]),
        );
        $this->setDependency(
            AiCommerceDependencyProvider::PLUGINS_STOREFRONT_ASSISTANT_AGENT,
            [new ProductDiscoveryAgentPlugin()],
        );
        $this->setUpCurrentStore();
    }

    /**
     * @return array<int, string>
     */
    public function executeStorefrontAssistantChatAndGetEventTypes(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): array {
        $this->addStorefrontAssistantConversationCleanup(
            (string)$storefrontAssistantChatRequestTransfer->getCustomerReference(),
            (string)$storefrontAssistantChatRequestTransfer->getConversationReference(),
        );

        ob_start();
        ob_start();

        try {
            $this->getStorefrontAssistantClient()->executeStorefrontAssistantChat($storefrontAssistantChatRequestTransfer);
        } finally {
            $streamedEvents = (string)ob_get_clean() . (string)ob_get_clean();
        }

        return $this->extractEventTypes($streamedEvents);
    }

    public function addStorefrontAssistantConversationCleanup(string $customerReference, string $conversationReference): void
    {
        if (trim($customerReference) === '' || trim($conversationReference) === '') {
            return;
        }

        $storageClient = $this->getStorageClient();

        $this->addCleanup(static function () use ($storageClient, $customerReference, $conversationReference): void {
            $storageClient->deleteMulti([
                sprintf(static::STORAGE_KEY_PATTERN_CONVERSATION, $customerReference, $conversationReference),
                sprintf(static::STORAGE_KEY_PATTERN_CONVERSATION_INDEX, $customerReference),
            ]);
        });
    }

    public function haveStorefrontAssistantConversation(string $customerReference): string
    {
        $conversationReference = $this->generateUniqueConversationReference();

        $this->executeStorefrontAssistantChatAndGetEventTypes(
            $this->createStorefrontAssistantChatRequest($customerReference, $conversationReference),
        );

        return $conversationReference;
    }

    public function createStorefrontAssistantChatRequest(
        string $customerReference,
        string $conversationReference
    ): StorefrontAssistantChatRequestTransfer {
        return (new StorefrontAssistantChatRequestTransfer())
            ->setMessage(static::USER_MESSAGE)
            ->setConversationReference($conversationReference)
            ->setCustomerReference($customerReference)
            ->setLocaleName($this->getLocator()->locale()->client()->getCurrentLocale());
    }

    public function createConversationCollectionCriteria(
        string $customerReference
    ): StorefrontAssistantConversationCollectionCriteriaTransfer {
        return (new StorefrontAssistantConversationCollectionCriteriaTransfer())
            ->setStorefrontAssistantConversationConditions(
                (new StorefrontAssistantConversationConditionsTransfer())->setCustomerReference($customerReference),
            );
    }

    /**
     * @param array<int, string> $conversationReferences
     */
    public function createConversationCollectionDeleteCriteria(
        string $customerReference,
        array $conversationReferences
    ): StorefrontAssistantConversationCollectionDeleteCriteriaTransfer {
        return (new StorefrontAssistantConversationCollectionDeleteCriteriaTransfer())
            ->setStorefrontAssistantConversationDeleteConditions(
                (new StorefrontAssistantConversationDeleteConditionsTransfer())
                    ->setCustomerReference($customerReference)
                    ->setConversationReferences($conversationReferences),
            );
    }

    public function createConversationMessageCollectionCriteria(
        string $customerReference,
        string $conversationReference
    ): StorefrontAssistantConversationMessageCollectionCriteriaTransfer {
        return (new StorefrontAssistantConversationMessageCollectionCriteriaTransfer())
            ->setStorefrontAssistantConversationMessageConditions(
                (new StorefrontAssistantConversationMessageConditionsTransfer())
                    ->setCustomerReference($customerReference)
                    ->setConversationReference($conversationReference),
            );
    }

    /**
     * @return array<int, string|null>
     */
    public function getConversationReferences(
        StorefrontAssistantConversationCollectionTransfer $storefrontAssistantConversationCollectionTransfer
    ): array {
        $conversationReferences = [];

        foreach ($storefrontAssistantConversationCollectionTransfer->getStorefrontAssistantConversations() as $storefrontAssistantConversationTransfer) {
            $conversationReferences[] = $storefrontAssistantConversationTransfer->getConversationReference();
        }

        return $conversationReferences;
    }

    public function getStorefrontAssistantUserMessage(): string
    {
        return static::USER_MESSAGE;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    public function getConversationMessageRolesAndContents(
        StorefrontAssistantConversationMessageCollectionTransfer $storefrontAssistantConversationMessageCollectionTransfer
    ): array {
        $conversationMessageRolesAndContents = [];

        foreach ($storefrontAssistantConversationMessageCollectionTransfer->getMessages() as $storefrontAssistantConversationMessageTransfer) {
            $conversationMessageRolesAndContents[] = [
                $storefrontAssistantConversationMessageTransfer->getRole(),
                $storefrontAssistantConversationMessageTransfer->getContent(),
            ];
        }

        return $conversationMessageRolesAndContents;
    }

    /**
     * @return array<string, mixed>
     */
    public function createRawAttachment(string $filename, string $mediaType, string $content): array
    {
        return [
            'name' => $filename,
            'mediaType' => $mediaType,
            'content' => base64_encode($content),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createPngRawAttachment(string $filename = self::ATTACHMENT_FILENAME_PNG): array
    {
        return $this->createRawAttachment($filename, static::MEDIA_TYPE_PNG, static::SIGNATURE_PNG . static::ATTACHMENT_PAYLOAD);
    }

    /**
     * @return array<string, mixed>
     */
    public function createOversizedPngRawAttachment(): array
    {
        return $this->createRawAttachment(
            static::ATTACHMENT_FILENAME_PNG,
            static::MEDIA_TYPE_PNG,
            str_pad(static::SIGNATURE_PNG, $this->getModuleConfig()->getStorefrontAssistantMaxAttachmentSizeBytes() + 1, 'a'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createPngRawAttachmentWithGifContent(): array
    {
        return $this->createRawAttachment(static::ATTACHMENT_FILENAME_PNG, static::MEDIA_TYPE_PNG, static::SIGNATURE_GIF . static::ATTACHMENT_PAYLOAD);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function createTooManyPngRawAttachments(): array
    {
        return array_fill(
            0,
            $this->getModuleConfig()->getStorefrontAssistantMaxAttachmentCount() + 1,
            $this->createPngRawAttachment(),
        );
    }

    public function createOverlongAttachmentFilename(): string
    {
        return str_repeat('a', $this->getMaxAttachmentFilenameLength() + 1) . static::ATTACHMENT_FILENAME_EXTENSION_PNG;
    }

    public function getMaxAttachmentFilenameLength(): int
    {
        return $this->getModuleConfig()->getStorefrontAssistantMaxAttachmentFilenameLength();
    }

    public function getMaxConversationReferenceLength(): int
    {
        return $this->getModuleConfig()->getStorefrontAssistantMaxConversationReferenceLength();
    }

    public function haveGlossaryReturningKeys(): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_GLOSSARY_STORAGE,
            Stub::makeEmpty(GlossaryStorageClientInterface::class, [
                'translate' => static fn (string $id): string => $id,
            ]),
        );
    }

    public function createOversizedStorefrontAssistantMessage(): string
    {
        return str_repeat(
            'a',
            $this->getModuleConfig()->getStorefrontAssistantMaxMessageLengthCharacters() + 1,
        );
    }

    public function generateUniqueConversationReference(): string
    {
        return sprintf('%s%s', static::CONVERSATION_REFERENCE_PREFIX, bin2hex(random_bytes(16)));
    }

    public function generateUniqueCustomerReference(): string
    {
        return sprintf('%s%s', static::CUSTOMER_REFERENCE_PREFIX, uniqid('', true));
    }

    public function captureStreamedOutput(callable $callable): string
    {
        ob_start();
        ob_start();

        try {
            $callable();
        } finally {
            $streamedOutput = (string)ob_get_clean() . (string)ob_get_clean();
        }

        return $streamedOutput;
    }

    /**
     * @return array<int, string>
     */
    public function extractStreamedPartTypes(string $streamedParts): array
    {
        return $this->extractEventTypes($streamedParts);
    }

    /**
     * @return array<int, string>
     */
    public function extractStreamedToolCallIds(string $streamedParts): array
    {
        $toolCallIds = [];

        foreach (explode("\n", $streamedParts) as $streamedEvent) {
            if (!str_starts_with($streamedEvent, static::SSE_DATA_PREFIX)) {
                continue;
            }

            $payload = json_decode(substr($streamedEvent, strlen(static::SSE_DATA_PREFIX)), true);

            if (!is_array($payload) || !isset($payload[StreamEventKey::TOOL_CALL_ID])) {
                continue;
            }

            $toolCallIds[] = (string)$payload[StreamEventKey::TOOL_CALL_ID];
        }

        return $toolCallIds;
    }

    /**
     * @return array<int, string>
     */
    protected function extractEventTypes(string $streamedEvents): array
    {
        $eventTypes = [];

        foreach (explode("\n", $streamedEvents) as $streamedEvent) {
            if (!str_starts_with($streamedEvent, static::SSE_DATA_PREFIX)) {
                continue;
            }

            $payload = json_decode(substr($streamedEvent, strlen(static::SSE_DATA_PREFIX)), true);

            if (!is_array($payload) || !isset($payload[StreamEventKey::TYPE])) {
                continue;
            }

            $eventTypes[] = (string)$payload[StreamEventKey::TYPE];
        }

        return $eventTypes;
    }

    /**
     * @param array<int, int> $categoryNodeIds
     *
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithCategoryFacet(array $categoryNodeIds): array
    {
        $facetValues = [];

        foreach ($categoryNodeIds as $categoryNodeId) {
            $facetValues[] = [
                static::FACET_VALUE_KEY_VALUE => $categoryNodeId,
                static::FACET_VALUE_KEY_DOC_COUNT => static::FACET_VALUE_DOC_COUNT,
            ];
        }

        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => [],
            static::RESULT_FORMATTER_KEY_FACETS => [
                [
                    static::FACET_KEY_NAME => static::FACET_NAME_CATEGORY,
                    static::FACET_KEY_VALUES => $facetValues,
                ],
            ],
        ];
    }

    public function getIdCategoryNodeFromStorage(): int
    {
        $this->setUpCurrentStore();

        $localeName = $this->getLocator()->locale()->client()->getCurrentLocale();
        $storeName = $this->getLocator()->store()->client()->getCurrentStore()->getNameOrFail();

        $categoryNodeStorageTransfers = $this->getLocator()->categoryStorage()->client()->getCategories(
            $localeName,
            $storeName,
        );

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            if ($categoryNodeStorageTransfer->getNodeId() && $categoryNodeStorageTransfer->getName() !== null) {
                return $categoryNodeStorageTransfer->getNodeId();
            }
        }

        $this->markTestSkipped(sprintf(
            'No category node is readable from storage for store "%s" and locale "%s". Run publish & synchronize for category nodes to populate it.',
            $storeName,
            $localeName,
        ));
    }

    public function ensureNestedCategoryTreeIsPublished(): void
    {
        $this->setUpCurrentStore();

        $localeName = $this->getLocator()->locale()->client()->getCurrentLocale();
        $storeName = $this->getLocator()->store()->client()->getCurrentStore()->getNameOrFail();

        $categoryNodeStorageTransfers = $this->getLocator()->categoryStorage()->client()->getCategories(
            $localeName,
            $storeName,
        );

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            if ($categoryNodeStorageTransfer->getChildren()->count() > 0) {
                return;
            }
        }

        $this->markTestSkipped(sprintf(
            'No nested category tree is readable from storage for store "%s" and locale "%s". Run publish & synchronize for category nodes to populate it.',
            $storeName,
            $localeName,
        ));
    }

    public function getCategoryNodeNameFromStorage(int $idCategoryNode): string
    {
        $this->setUpCurrentStore();

        return (string)$this->getLocator()->categoryStorage()->client()->getCategoryNodeById(
            $idCategoryNode,
            $this->getLocator()->locale()->client()->getCurrentLocale(),
            $this->getLocator()->store()->client()->getCurrentStore()->getNameOrFail(),
        )->getName();
    }

    public function createCategoryTreeFormatter(): CategoryTreeFormatterInterface
    {
        $this->setUpCurrentStore();

        return $this->getFactory()->createCategoryTreeFormatter();
    }

    public function composeProductDiscoverySystemPrompt(
        ?StorefrontAssistantPageContextTransfer $storefrontAssistantPageContextTransfer = null,
        ?string $inlineCategoryTree = null
    ): string {
        $this->setUpCurrentStore();

        return $this->getFactory()->createPageContextSystemPromptComposer()->composeSystemPrompt(
            static::SYSTEM_PROMPT,
            (new StorefrontAssistantChatRequestTransfer())
                ->setStorefrontAssistantPageContext($storefrontAssistantPageContextTransfer),
            $inlineCategoryTree,
        );
    }

    public function createCategoryPageContext(string $categoryName): StorefrontAssistantPageContextTransfer
    {
        return (new StorefrontAssistantPageContextTransfer())
            ->setPageType(StorefrontAssistantPageType::Category->value)
            ->setCategoryName($categoryName);
    }

    public function getCategoryNameFromStorage(int $idCategoryNode): string
    {
        return $this->getCategoryNodeNameFromStorage($idCategoryNode);
    }

    public function createRangeFacet(string $name, string $parameterName, int $min, int $max): RangeSearchResultTransfer
    {
        return (new RangeSearchResultTransfer())
            ->setName($name)
            ->setMin($min)
            ->setMax($max)
            ->setConfig((new FacetConfigTransfer())->setName($name)->setParameterName($parameterName));
    }

    /**
     * @param array<int, mixed> $facets
     *
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithFacets(array $facets): array
    {
        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => [],
            static::RESULT_FORMATTER_KEY_FACETS => $facets,
        ];
    }

    public function formatAmountInCurrentCurrency(int $amount): string
    {
        $moneyClient = $this->getLocator()->money()->client();

        return $moneyClient->formatWithSymbol(
            $moneyClient->fromInteger($amount, $this->getLocator()->currency()->client()->getCurrent()->getCode()),
        );
    }

    public function getProductLabelDictionaryItemFromStorage(): ProductLabelDictionaryItemTransfer
    {
        $this->setUpCurrentStore();

        $localeName = $this->getLocator()->locale()->client()->getCurrentLocale();
        $storeName = $this->getLocator()->store()->client()->getCurrentStore()->getNameOrFail();
        $productLabelStorageClient = $this->getLocator()->productLabelStorage()->client();

        foreach (SpyProductLabelQuery::create()->filterByIsActive(true)->find() as $productLabelEntity) {
            $productLabelDictionaryItemTransfer = $productLabelStorageClient->findLabelByName(
                (string)$productLabelEntity->getName(),
                $localeName,
                $storeName,
            );

            if (
                $productLabelDictionaryItemTransfer !== null
                && $productLabelDictionaryItemTransfer->getName()
                && strtolower((string)$productLabelDictionaryItemTransfer->getKey()) !== strtolower($productLabelDictionaryItemTransfer->getName())
            ) {
                return $productLabelDictionaryItemTransfer;
            }
        }

        $this->markTestSkipped(sprintf(
            'No product label whose localized name differs from its key is readable from storage for store "%s" and locale "%s". Run publish & synchronize for product labels to populate it.',
            $storeName,
            $localeName,
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $searchResults
     * @param array<int, array<string, mixed>> $capturedCalls
     */
    public function setUpCatalogSearchResultsInCallOrder(array $searchResults, array &$capturedCalls): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_CATALOG,
            Stub::makeEmpty(CatalogClientInterface::class, [
                'catalogSearch' => function (
                    string $searchString,
                    array $requestParameters
                ) use (
                    $searchResults,
                    &$capturedCalls,
                ): array {
                    $capturedCalls[] = [
                        static::CAPTURED_CALL_KEY_SEARCH_STRING => $searchString,
                        static::CAPTURED_CALL_KEY_REQUEST_PARAMETERS => $requestParameters,
                    ];

                    return $searchResults[count($capturedCalls) - 1] ?? $searchResults[array_key_last($searchResults)] ?? [];
                },
            ]),
        );
    }

    /**
     * @param array<int, mixed> $facets
     *
     * @return array<string, mixed>
     */
    public function createZeroResultCatalogSearchResultWithFacets(array $facets, ?string $spellingSuggestion = null): array
    {
        $searchResult = $this->createCatalogSearchResultWithTotal(0) + [static::RESULT_FORMATTER_KEY_FACETS => $facets];

        if ($spellingSuggestion !== null) {
            $searchResult[static::RESULT_FORMATTER_KEY_SPELLING_SUGGESTION] = $spellingSuggestion;
        }

        return $searchResult;
    }

    /**
     * @param array<int, int> $productAbstractIds
     * @param array<int, string> $reasons
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDisplayedProductCards(array $productAbstractIds, array $reasons = []): array
    {
        $toolResult = (string)json_encode(
            (new DisplayProductsToolPlugin())->execute([
                static::PARAMETER_ID_PRODUCT_ABSTRACTS => $productAbstractIds,
                DisplayProductReader::PARAMETER_REASONS => $reasons,
            ]),
        );
        $enrichedToolResult = json_decode($this->getFactory()->createToolResultProductEnricher()->enrich($toolResult), true);

        return $enrichedToolResult[static::RESULT_KEY_PRODUCTS] ?? [];
    }

    /**
     * @param callable(array<mixed>): bool $isMatching
     *
     * @return array{0: int, 1: array<mixed>}
     */
    public function getProductAbstractStorageDataMatching(callable $isMatching, string $description): array
    {
        $this->setUpCurrentStore();
        $productStorageClient = $this->getLocator()->productStorage()->client();
        $this->setUpProductStorageClientReadingPublishedData();
        $localeName = $this->getLocator()->locale()->client()->getCurrentLocale();
        $storeName = $this->getProductAbstractStorageStoreName();

        $productAbstractIds = SpyProductAbstractQuery::create()
            ->orderByIdProductAbstract(Criteria::ASC)
            ->limit(static::PRODUCT_ABSTRACT_LOOKUP_LIMIT)
            ->select(['IdProductAbstract'])
            ->find()
            ->getData();

        $productStorageData = $productStorageClient->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
            array_map('intval', $productAbstractIds),
            $localeName,
            $storeName,
        );

        foreach ($productStorageData as $idProductAbstract => $productData) {
            if (is_array($productData) && $isMatching($productData)) {
                return [(int)$idProductAbstract, $productData];
            }
        }

        $this->markTestSkipped(sprintf(
            'No product abstract %s is readable from storage for store "%s" and locale "%s". Run publish & synchronize to populate it.',
            $description,
            $storeName,
            $localeName,
        ));
    }

    /**
     * @param array<string, mixed> $searchResultData
     *
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithRow(int $idProductAbstract, array $searchResultData = []): array
    {
        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => [
                [static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract] + $searchResultData,
            ],
            static::RESULT_FORMATTER_KEY_PAGINATION => [
                static::PAGINATION_KEY_NUM_FOUND => 1,
            ],
        ];
    }

    public function setUpProductStorageClientReadingPublishedData(int &$bulkReadCount = 0): void
    {
        $productStorageClient = $this->getLocator()->productStorage()->client();
        $productAbstractStorageStoreName = $this->getProductAbstractStorageStoreName();

        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_STORAGE,
            Stub::makeEmpty(ProductStorageClientInterface::class, [
                'getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore' => function (
                    array $productAbstractIds,
                    string $localeName
                ) use (
                    $productStorageClient,
                    $productAbstractStorageStoreName,
                    &$bulkReadCount,
                ): array {
                    $bulkReadCount++;

                    return $productStorageClient->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
                        $productAbstractIds,
                        $localeName,
                        $productAbstractStorageStoreName,
                    );
                },
            ]),
        );
    }

    /**
     * @param array<string, array<string, mixed>> $productAbstractStorageDataBySku
     * @param array<string, array<string, mixed>> $productConcreteStorageDataBySku
     * @param array<int, array<string, mixed>> $productAbstractStorageDataById
     * @param list<int> $restrictedProductAbstractIds
     */
    public function haveProductStorageClientWithProducts(
        array $productAbstractStorageDataBySku,
        array $productConcreteStorageDataBySku = [],
        array $productAbstractStorageDataById = [],
        array $restrictedProductAbstractIds = [],
        int &$readCount = 0
    ): void {
        foreach ($productAbstractStorageDataBySku as $productAbstractStorageData) {
            $productAbstractStorageDataById[(int)$productAbstractStorageData[static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT]] ??= $productAbstractStorageData;
        }

        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_STORAGE,
            Stub::makeEmpty(ProductStorageClientInterface::class, [
                'findProductAbstractStorageDataByMapping' => function (string $mappingType, string $identifier) use ($productAbstractStorageDataBySku, &$readCount): ?array {
                    $readCount++;

                    return $mappingType === static::PRODUCT_STORAGE_MAPPING_TYPE_SKU ? ($productAbstractStorageDataBySku[$identifier] ?? null) : null;
                },
                'findProductConcreteStorageDataByMapping' => function (string $mappingType, string $identifier) use ($productConcreteStorageDataBySku, &$readCount): ?array {
                    $readCount++;

                    return $mappingType === static::PRODUCT_STORAGE_MAPPING_TYPE_SKU ? ($productConcreteStorageDataBySku[$identifier] ?? null) : null;
                },
                'findProductAbstractStorageData' => function (int $idProductAbstract) use ($productAbstractStorageDataById, $restrictedProductAbstractIds, &$readCount): ?array {
                    $readCount++;

                    return in_array($idProductAbstract, $restrictedProductAbstractIds, true) ? null : ($productAbstractStorageDataById[$idProductAbstract] ?? null);
                },
                'isProductAbstractRestricted' => function (int $idProductAbstract) use ($restrictedProductAbstractIds): bool {
                    return in_array($idProductAbstract, $restrictedProductAbstractIds, true);
                },
            ]),
        );
    }

    /**
     * @param array<int, list<string>> $labelNamesByIdProductAbstract
     */
    public function haveProductLabelStorageClientWithLabels(array $labelNamesByIdProductAbstract): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_LABEL_STORAGE,
            Stub::makeEmpty(ProductLabelStorageClientInterface::class, [
                'findLabelsByIdProductAbstract' => function (int $idProductAbstract) use ($labelNamesByIdProductAbstract): array {
                    return array_map(
                        static fn (string $labelName): ProductLabelDictionaryItemTransfer => (new ProductLabelDictionaryItemTransfer())->setName($labelName),
                        $labelNamesByIdProductAbstract[$idProductAbstract] ?? [],
                    );
                },
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createProductAbstractStorageData(int $idProductAbstract, string $sku, string $name): array
    {
        return [
            static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract,
            'sku' => $sku,
            'name' => $name,
        ];
    }

    public function createProductPageContext(string $productName, string $productSku): StorefrontAssistantPageContextTransfer
    {
        return (new StorefrontAssistantPageContextTransfer())
            ->setPageType(StorefrontAssistantPageType::Product->value)
            ->setProductName($productName)
            ->setProductSku($productSku);
    }

    /**
     * @param list<string> $filters
     */
    public function createSearchPageContext(string $searchQuery, array $filters): StorefrontAssistantPageContextTransfer
    {
        return (new StorefrontAssistantPageContextTransfer())
            ->setPageType(StorefrontAssistantPageType::Search->value)
            ->setSearchQuery($searchQuery)
            ->setFilters($filters);
    }

    public function expandStorefrontAssistantPageContext(
        StorefrontAssistantPageContextTransfer $storefrontAssistantPageContextTransfer
    ): StorefrontAssistantPageContextTransfer {
        $this->setUpCurrentStore();

        return $this->getFactory()->createPageContextProductResolver()->expandPageContext(
            $storefrontAssistantPageContextTransfer,
            $this->getLocator()->locale()->client()->getCurrentLocale(),
        );
    }

    public function executeProductDiscoveryAgentAndGetSystemPrompt(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): string {
        return (string)$this->executeProductDiscoveryAgentAndGetPromptRequest($storefrontAssistantChatRequestTransfer)->getSystemPrompt();
    }

    public function executeProductDiscoveryAgentAndGetPromptRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): PromptRequestTransfer {
        $this->setUpCurrentStore();

        $capturedPromptRequestTransfers = [];
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION,
            Stub::makeEmpty(AiFoundationClientInterface::class, [
                'streamPrompt' => function (PromptRequestTransfer $promptRequestTransfer) use (&$capturedPromptRequestTransfers): PromptResponseTransfer {
                    $capturedPromptRequestTransfers[] = $promptRequestTransfer;

                    return (new PromptResponseTransfer())->setIsSuccessful(true);
                },
            ]),
        );

        /** @var \SprykerFeature\Client\AiCommerce\AiCommerceConfig $aiCommerceConfig */
        $aiCommerceConfig = $this->getModuleConfig();

        $productDiscoveryAgentPlugin = new ProductDiscoveryAgentPlugin();
        $productDiscoveryAgentPlugin->setFactory($this->getFactory());
        $productDiscoveryAgentPlugin->setConfig($aiCommerceConfig);
        $productDiscoveryAgentPlugin->executeAgent($storefrontAssistantChatRequestTransfer);

        return $capturedPromptRequestTransfers[0] ?? new PromptRequestTransfer();
    }

    /**
     * @param list<\Generated\Shared\Transfer\CategoryNodeStorageTransfer> $categoryNodeStorageTransfers
     */
    public function haveCategoryStorageClientWithCategoryTree(array $categoryNodeStorageTransfers): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_CATEGORY_STORAGE,
            Stub::makeEmpty(CategoryStorageClientInterface::class, [
                'getCategories' => static fn (): ArrayObject => new ArrayObject($categoryNodeStorageTransfers),
            ]),
        );
    }

    /**
     * @param list<\Generated\Shared\Transfer\CategoryNodeStorageTransfer> $childCategoryNodeStorageTransfers
     */
    public function createCategoryNodeStorage(
        int $idCategoryNode,
        string $categoryName,
        array $childCategoryNodeStorageTransfers = []
    ): CategoryNodeStorageTransfer {
        return (new CategoryNodeStorageTransfer())
            ->setNodeId($idCategoryNode)
            ->setName($categoryName)
            ->setIsActive(true)
            ->setChildren(new ArrayObject($childCategoryNodeStorageTransfers));
    }

    /**
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithProductsAndTotal(int $productCount, int $totalResults): array
    {
        $searchResult = $this->createCatalogSearchResultWithProducts($productCount);
        $searchResult[static::RESULT_FORMATTER_KEY_PAGINATION][static::PAGINATION_KEY_NUM_FOUND] = $totalResults;

        return $searchResult;
    }

    /**
     * @param array<string, mixed> $catalogSearchArguments
     * @param array<int, int> $shownProductAbstractIds
     *
     * @return array<string, mixed>
     */
    public function executeCatalogSearchInConversationThatShowed(array $catalogSearchArguments, array $shownProductAbstractIds): array
    {
        $catalogSearchResult = [];
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION,
            Stub::makeEmpty(AiFoundationClientInterface::class, [
                'getConversationHistoryCollection' => fn (ConversationHistoryCriteriaTransfer $conversationHistoryCriteriaTransfer): ConversationHistoryCollectionTransfer => $this->createConversationHistoryCollectionThatShowed(
                    $conversationHistoryCriteriaTransfer,
                    $shownProductAbstractIds,
                ),
                'streamPrompt' => function () use ($catalogSearchArguments, &$catalogSearchResult): PromptResponseTransfer {
                    $catalogSearchToolPlugin = new CatalogSearchToolPlugin();
                    $catalogSearchToolPlugin->setFactory($this->getFactory());
                    $catalogSearchResult = $catalogSearchToolPlugin->execute($catalogSearchArguments);

                    return (new PromptResponseTransfer())->setIsSuccessful(true);
                },
            ]),
        );
        $this->setDependency(AiCommerceDependencyProvider::PLUGINS_STOREFRONT_ASSISTANT_AGENT, [new ProductDiscoveryAgentPlugin()]);
        $this->enableStorefrontAssistant();
        $this->setUpCurrentStore();

        $this->executeStorefrontAssistantChatAndGetEventTypes(
            $this->createStorefrontAssistantChatRequest($this->generateUniqueCustomerReference(), $this->generateUniqueConversationReference()),
        );

        return $catalogSearchResult;
    }

    /**
     * @param array<int, int> $shownProductAbstractIds
     */
    protected function createConversationHistoryCollectionThatShowed(
        ConversationHistoryCriteriaTransfer $conversationHistoryCriteriaTransfer,
        array $shownProductAbstractIds
    ): ConversationHistoryCollectionTransfer {
        $displayProductsResult = [
            static::RESULT_KEY_PRODUCTS => array_map(
                static fn (int $idProductAbstract): array => [static::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
                $shownProductAbstractIds,
            ),
            static::RESULT_KEY_DISPLAYED_COUNT => count($shownProductAbstractIds),
        ];
        $originalMessages = [
            [static::KEY_ROLE => static::ROLE_USER, static::KEY_CONTENT => static::USER_MESSAGE],
            [
                static::KEY_TYPE => static::MESSAGE_TYPE_TOOL_CALL_RESULT,
                static::KEY_TOOLS => [[
                    static::KEY_NAME => DisplayProductsToolPlugin::TOOL_NAME,
                    static::KEY_RESULT => (string)json_encode($displayProductsResult),
                ]],
            ],
        ];
        $conversationHistoryCollectionTransfer = new ConversationHistoryCollectionTransfer();

        foreach ($conversationHistoryCriteriaTransfer->getConversationHistoryConditions()?->getConversationReferences() ?? [] as $conversationReference) {
            $conversationHistoryCollectionTransfer->addConversationHistory(
                (new ConversationHistoryTransfer())
                    ->setConversationReference($conversationReference)
                    ->setOriginalMessages((string)json_encode($originalMessages)),
            );
        }

        return $conversationHistoryCollectionTransfer;
    }

    /**
     * @param array<int, array<string, mixed>> $productAbstractStorageDataById
     * @param array<string, int> $idProductAbstractByAbstractSku
     * @param array<string, int> $idProductAbstractByConcreteSku
     * @param array<int, array<string, mixed>> $productConcreteStorageDataById
     */
    public function haveProductStorageClientReturning(
        array $productAbstractStorageDataById,
        array $idProductAbstractByAbstractSku = [],
        array $idProductAbstractByConcreteSku = [],
        array $productConcreteStorageDataById = []
    ): void {
        $this->setUpCurrentStore();
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_STORAGE,
            Stub::makeEmpty(ProductStorageClientInterface::class, [
                'getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore' => function (array $productAbstractIds) use ($productAbstractStorageDataById): array {
                    return array_intersect_key($productAbstractStorageDataById, array_flip($productAbstractIds));
                },
                'findProductAbstractStorageDataByMapping' => function (string $mappingType, string $identifier) use ($productAbstractStorageDataById, $idProductAbstractByAbstractSku): ?array {
                    if ($mappingType !== static::PRODUCT_STORAGE_MAPPING_TYPE_SKU) {
                        return null;
                    }

                    return $productAbstractStorageDataById[$idProductAbstractByAbstractSku[$identifier] ?? 0] ?? null;
                },
                'findProductConcreteStorageDataByMapping' => function (string $mappingType, string $identifier) use ($idProductAbstractByConcreteSku): ?array {
                    return $mappingType === static::PRODUCT_STORAGE_MAPPING_TYPE_SKU && isset($idProductAbstractByConcreteSku[$identifier])
                        ? [static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => $idProductAbstractByConcreteSku[$identifier], 'sku' => $identifier]
                        : null;
                },
                'getBulkProductConcreteStorageData' => function (array $productConcreteIds) use ($productConcreteStorageDataById): array {
                    return array_intersect_key($productConcreteStorageDataById, array_flip($productConcreteIds));
                },
                'isProductAbstractRestricted' => false,
            ]),
        );
    }

    /**
     * @param array<string, string> $attributes
     * @param array<string, list<string>> $superAttributes
     * @param array<string, int> $productConcreteIdsBySku
     * @param array<int, array<string, string>> $attributeVariantMap
     *
     * @return array<string, mixed>
     */
    public function createProductAbstractStorageDataWithVariants(
        int $idProductAbstract,
        string $sku,
        array $attributes,
        array $superAttributes = [],
        array $productConcreteIdsBySku = [],
        array $attributeVariantMap = []
    ): array {
        return $this->createProductAbstractStorageData($idProductAbstract, $sku, sprintf('Product %s', $sku)) + [
            'attributes' => $attributes,
            'attribute_map' => [
                'super_attributes' => $superAttributes,
                'product_concrete_ids' => $productConcreteIdsBySku,
                'attribute_variant_map' => $attributeVariantMap,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function executeProductDetailsTool(array $arguments): array
    {
        return (new ProductDetailsToolPlugin())->execute($arguments);
    }

    /**
     * @param array<string, string> $discontinuedNotesBySku
     * @param array<string, list<int>> $alternativeProductConcreteIdsBySku
     */
    public function haveProductStockStorageClientsReturning(
        ?ProductAbstractAvailabilityTransfer $productAbstractAvailabilityTransfer,
        array $discontinuedNotesBySku = [],
        array $alternativeProductConcreteIdsBySku = []
    ): void {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_AVAILABILITY_STORAGE,
            Stub::makeEmpty(AvailabilityStorageClientInterface::class, [
                'findProductAbstractAvailability' => $productAbstractAvailabilityTransfer,
            ]),
        );
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_DISCONTINUED_STORAGE,
            Stub::makeEmpty(ProductDiscontinuedStorageClientInterface::class, [
                'findProductDiscontinuedStorage' => function (string $concreteSku) use ($discontinuedNotesBySku): ?ProductDiscontinuedStorageTransfer {
                    return array_key_exists($concreteSku, $discontinuedNotesBySku)
                        ? (new ProductDiscontinuedStorageTransfer())->setSku($concreteSku)->setNote($discontinuedNotesBySku[$concreteSku])
                        : null;
                },
            ]),
        );
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_ALTERNATIVE_STORAGE,
            Stub::makeEmpty(ProductAlternativeStorageClientInterface::class, [
                'getConcreteAlternativeProductCollection' => function (
                    ConcreteAlternativeProductCriteriaTransfer $concreteAlternativeProductCriteriaTransfer
                ) use ($alternativeProductConcreteIdsBySku): ConcreteAlternativeProductCollectionTransfer {
                    $this->concreteAlternativeProductCriteriaTransfers[] = $concreteAlternativeProductCriteriaTransfer;

                    return $this->createConcreteAlternativeProductCollection(
                        $concreteAlternativeProductCriteriaTransfer->getConcreteAlternativeProductConditionsOrFail(),
                        $alternativeProductConcreteIdsBySku,
                    );
                },
            ]),
        );
    }

    /**
     * @return list<\Generated\Shared\Transfer\ConcreteAlternativeProductCriteriaTransfer>
     */
    public function getConcreteAlternativeProductCriteriaTransfers(): array
    {
        return $this->concreteAlternativeProductCriteriaTransfers;
    }

    /**
     * @param array<string, int> $quantityBySku
     * @param list<string> $neverOutOfStockSkus
     */
    public function createProductAbstractAvailability(
        string $abstractSku,
        array $quantityBySku,
        array $neverOutOfStockSkus = []
    ): ProductAbstractAvailabilityTransfer {
        $productAbstractAvailabilityTransfer = (new ProductAbstractAvailabilityTransfer())->setSku($abstractSku);

        foreach ($quantityBySku as $concreteSku => $quantity) {
            $productAbstractAvailabilityTransfer->addProductConcreteAvailability(
                (new ProductConcreteAvailabilityTransfer())
                    ->setSku((string)$concreteSku)
                    ->setAvailability($quantity)
                    ->setIsNeverOutOfStock(in_array((string)$concreteSku, $neverOutOfStockSkus, true)),
            );
        }

        return $productAbstractAvailabilityTransfer;
    }

    public function haveProductOptionStorageClientReturning(?ProductAbstractOptionStorageTransfer $productAbstractOptionStorageTransfer): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_OPTION_STORAGE,
            Stub::makeEmpty(ProductOptionStorageClientInterface::class, [
                'getBulkProductOptions' => function (array $productAbstractIds) use ($productAbstractOptionStorageTransfer): array {
                    if ($productAbstractOptionStorageTransfer === null || !in_array($productAbstractOptionStorageTransfer->getIdProductAbstract(), $productAbstractIds, true)) {
                        return [];
                    }

                    return [$productAbstractOptionStorageTransfer->getIdProductAbstract() => $productAbstractOptionStorageTransfer];
                },
            ]),
        );
    }

    /**
     * @param array<string, string> $translationsByKey
     */
    public function haveGlossaryTranslating(array $translationsByKey): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_GLOSSARY_STORAGE,
            Stub::makeEmpty(GlossaryStorageClientInterface::class, [
                'translate' => static fn (string $id): string => $translationsByKey[$id] ?? $id,
                'translateBulk' => static function (array $keyNames) use ($translationsByKey): array {
                    $translations = [];

                    foreach ($keyNames as $keyName) {
                        $translations[$keyName] = $translationsByKey[$keyName] ?? $keyName;
                    }

                    return $translations;
                },
            ]),
        );
    }

    /**
     * @param array<string, list<array{sku: string, value: string, price: int|null, id?: int}>> $optionValuesByGroupName
     */
    public function createProductAbstractOptionStorage(int $idProductAbstract, array $optionValuesByGroupName): ProductAbstractOptionStorageTransfer
    {
        $productAbstractOptionStorageTransfer = (new ProductAbstractOptionStorageTransfer())->setIdProductAbstract($idProductAbstract);

        foreach ($optionValuesByGroupName as $groupName => $optionValues) {
            $productOptionGroupStorageTransfer = (new ProductOptionGroupStorageTransfer())->setName($groupName);

            foreach ($optionValues as $optionValue) {
                $productOptionGroupStorageTransfer->addProductOptionValue(
                    (new ProductOptionValueStorageTransfer())
                        ->setIdProductOptionValue($optionValue['id'] ?? null)
                        ->setSku($optionValue['sku'])
                        ->setValue($optionValue['value'])
                        ->setPrice($optionValue['price']),
                );
            }

            $productAbstractOptionStorageTransfer->addProductOptionGroup($productOptionGroupStorageTransfer);
        }

        return $productAbstractOptionStorageTransfer;
    }

    /**
     * @return array{0: int, 1: \Generated\Shared\Transfer\ProductAbstractOptionStorageTransfer}
     */
    public function getProductAbstractWithOptionsFromStorage(): array
    {
        [$idProductAbstract] = $this->getProductAbstractStorageDataMatching(
            fn (array $productData): bool => $this->getLocator()->productOptionStorage()->client()->getBulkProductOptions([(int)$productData[static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT]]) !== [],
            'with product options',
        );

        return [$idProductAbstract, $this->getLocator()->productOptionStorage()->client()->getBulkProductOptions([$idProductAbstract])[$idProductAbstract]];
    }

    /**
     * @param list<\Generated\Shared\Transfer\ProductOfferStorageTransfer> $productOfferStorageTransfers
     */
    public function haveProductOfferStorageClientReturning(array $productOfferStorageTransfers): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_OFFER_STORAGE,
            Stub::makeEmpty(ProductOfferStorageClientInterface::class, [
                'getProductOfferStoragesBySkus' => (new ProductOfferStorageCollectionTransfer())->setProductOffers(new ArrayObject($productOfferStorageTransfers)),
                'findProductConcreteDefaultProductOffer' => null,
            ]),
        );
    }

    public function createProductOfferStorage(
        string $productOfferReference,
        string $productConcreteSku,
        string $merchantName,
        ?int $price,
        ?float $stockQuantity,
        ?bool $isNeverOutOfStock = null
    ): ProductOfferStorageTransfer {
        return (new ProductOfferStorageTransfer())
            ->setProductOfferReference($productOfferReference)
            ->setProductConcreteSku($productConcreteSku)
            ->setMerchantReference(sprintf('MER-%s', $productOfferReference))
            ->setMerchantStorage((new MerchantStorageTransfer())->setName($merchantName))
            ->setPrice($price !== null ? (new CurrentProductPriceTransfer())->setPrice($price) : null)
            ->setStockQuantity($stockQuantity)
            ->setIsNeverOutOfStock($isNeverOutOfStock);
    }

    /**
     * @param array<int, int> $priceByIdProductConcrete
     */
    public function haveMerchantProductSoldBy(?string $merchantName, ?int $price, array $priceByIdProductConcrete = []): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_MERCHANT_STORAGE,
            Stub::makeEmpty(MerchantStorageClientInterface::class, [
                'findOne' => $merchantName !== null ? (new MerchantStorageTransfer())->setName($merchantName) : null,
            ]),
        );
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE,
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getResolvedCurrentProductPriceTransfer' => static fn (PriceProductFilterTransfer $priceProductFilterTransfer): CurrentProductPriceTransfer => (new CurrentProductPriceTransfer())
                    ->setPrice($priceByIdProductConcrete[(int)$priceProductFilterTransfer->getIdProduct()] ?? $price),
            ]),
        );
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CurrentProductPriceTransfer> $currentProductPriceByQuantity
     */
    public function havePriceProductStorageClientReturningByQuantity(array $currentProductPriceByQuantity): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE,
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getResolvedCurrentProductPriceTransfer' => static fn (PriceProductFilterTransfer $priceProductFilterTransfer): CurrentProductPriceTransfer => $currentProductPriceByQuantity[(int)$priceProductFilterTransfer->getQuantity()]
                    ?? new CurrentProductPriceTransfer(),
            ]),
        );
    }

    public function havePricePermission(bool $canSeePrice): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PERMISSION,
            Stub::makeEmpty(PermissionClientInterface::class, [
                'can' => $canSeePrice,
            ]),
        );
    }

    /**
     * @param list<int> $relatedProductAbstractIds
     * @param list<int> $upSellingProductAbstractIds
     */
    public function haveProductRelationStorageClientReturning(array $relatedProductAbstractIds, array $upSellingProductAbstractIds = []): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_RELATION_STORAGE,
            Stub::makeEmpty(ProductRelationStorageClientInterface::class, [
                'findRelatedAbstractProductIds' => $relatedProductAbstractIds,
                'findUpSellingAbstractProductIds' => function (QuoteTransfer $quoteTransfer) use ($upSellingProductAbstractIds): array {
                    return $quoteTransfer->getStore() !== null && $quoteTransfer->getItems()->count() === 1 ? $upSellingProductAbstractIds : [];
                },
            ]),
        );
    }

    /**
     * @param array<string, list<int>> $alternativeProductAbstractIdsByConcreteSku
     */
    public function haveProductAlternativeStorageClientReturning(array $alternativeProductAbstractIdsByConcreteSku): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_ALTERNATIVE_STORAGE,
            Stub::makeEmpty(ProductAlternativeStorageClientInterface::class, [
                'getConcreteAlternativeProductCollection' => function (
                    ConcreteAlternativeProductCriteriaTransfer $concreteAlternativeProductCriteriaTransfer
                ) use ($alternativeProductAbstractIdsByConcreteSku): ConcreteAlternativeProductCollectionTransfer {
                    return $this->createConcreteAlternativeProductCollection(
                        $concreteAlternativeProductCriteriaTransfer->getConcreteAlternativeProductConditionsOrFail(),
                        $alternativeProductAbstractIdsByConcreteSku,
                    );
                },
            ]),
        );
    }

    /**
     * @param array<string, list<int>> $alternativeProductAbstractIdsByConcreteSku
     */
    protected function createConcreteAlternativeProductCollection(
        ConcreteAlternativeProductConditionsTransfer $concreteAlternativeProductConditionsTransfer,
        array $alternativeProductAbstractIdsByConcreteSku
    ): ConcreteAlternativeProductCollectionTransfer {
        $concreteAlternativeProductCollectionTransfer = new ConcreteAlternativeProductCollectionTransfer();

        foreach ($concreteAlternativeProductConditionsTransfer->getSkus() as $concreteSku) {
            $concreteAlternativeProductTransfer = (new ConcreteAlternativeProductTransfer())->setSku($concreteSku);

            foreach ($alternativeProductAbstractIdsByConcreteSku[$concreteSku] ?? [] as $idProductAbstract) {
                $concreteAlternativeProductTransfer->addAlternativeProduct((new ProductViewTransfer())->setIdProductAbstract($idProductAbstract));
            }

            $concreteAlternativeProductCollectionTransfer->addConcreteAlternativeProduct($concreteAlternativeProductTransfer);
        }

        return $concreteAlternativeProductCollectionTransfer;
    }

    public function haveProductCategoryStorageClientReturning(int $idProductAbstract, int $categoryNodeId, string $categoryName): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_CATEGORY_STORAGE,
            Stub::makeEmpty(ProductCategoryStorageClientInterface::class, [
                'findBulkProductAbstractCategory' => [
                    (new ProductAbstractCategoryStorageTransfer())
                        ->setIdProductAbstract($idProductAbstract)
                        ->addCategory((new ProductCategoryStorageTransfer())->setCategoryNodeId($categoryNodeId)->setName($categoryName)),
                ],
            ]),
        );
    }

    /**
     * @param array<int, list<int>> $categoryNodeIdsByIdProductAbstract
     */
    public function haveProductCategoryStorageClientReturningCategoryNodeIds(array $categoryNodeIdsByIdProductAbstract): void
    {
        $productAbstractCategoryStorageTransfers = [];

        foreach ($categoryNodeIdsByIdProductAbstract as $idProductAbstract => $categoryNodeIds) {
            $productAbstractCategoryStorageTransfer = (new ProductAbstractCategoryStorageTransfer())->setIdProductAbstract($idProductAbstract);

            foreach ($categoryNodeIds as $categoryNodeId) {
                $productAbstractCategoryStorageTransfer->addCategory(
                    (new ProductCategoryStorageTransfer())->setCategoryNodeId($categoryNodeId)->setName(sprintf('Category %d', $categoryNodeId)),
                );
            }

            $productAbstractCategoryStorageTransfers[] = $productAbstractCategoryStorageTransfer;
        }

        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_CATEGORY_STORAGE,
            Stub::makeEmpty(ProductCategoryStorageClientInterface::class, [
                'findBulkProductAbstractCategory' => $productAbstractCategoryStorageTransfers,
            ]),
        );
    }

    /**
     * @param list<int> $productAbstractIds
     * @param array<string, int> $productConcreteIdsBySku
     */
    public function haveProductsInProductStorage(array $productAbstractIds, array $productConcreteIdsBySku = []): void
    {
        $productAbstractStorageDataById = [];

        foreach ($productAbstractIds as $idProductAbstract) {
            $productAbstractStorageDataById[$idProductAbstract] = $this->createProductAbstractStorageDataWithVariants(
                $idProductAbstract,
                sprintf('relation-%d', $idProductAbstract),
                [],
                [],
                $productConcreteIdsBySku,
            );
        }

        $this->haveProductStorageClientReturning($productAbstractStorageDataById);
    }

    /**
     * @param list<int> $productAbstractIds
     *
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithRows(array $productAbstractIds): array
    {
        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => array_map(
                static fn (int $idProductAbstract): array => [static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
                $productAbstractIds,
            ),
            static::RESULT_FORMATTER_KEY_PAGINATION => [
                static::PAGINATION_KEY_NUM_FOUND => count($productAbstractIds),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function executeProductRelationsTool(array $arguments): array
    {
        return (new ProductRelationsToolPlugin())->execute($arguments);
    }

    /**
     * @param list<\Generated\Shared\Transfer\CmsPageStorageTransfer> $cmsPageStorageTransfers
     */
    public function haveCmsStorageClientReturning(array $cmsPageStorageTransfers): void
    {
        $this->setUpCurrentStore();
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_CMS_STORAGE,
            Stub::makeEmpty(CmsStorageClientInterface::class, [
                'getCmsPageStorageByIds' => function (array $cmsPageIds) use ($cmsPageStorageTransfers): array {
                    return array_values(array_filter(
                        $cmsPageStorageTransfers,
                        static fn (CmsPageStorageTransfer $cmsPageStorageTransfer): bool => in_array($cmsPageStorageTransfer->getIdCmsPage(), $cmsPageIds, true),
                    ));
                },
            ]),
        );
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function executeReadShopPageTool(array $arguments): array
    {
        return (new ReadShopPageToolPlugin())->execute($arguments);
    }

    /**
     * @param array<int, array{0: float, 1: int}> $ratingAndReviewCountByIdProductAbstract
     */
    public function haveProductReviewStorageClientReturning(array $ratingAndReviewCountByIdProductAbstract): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_REVIEW_STORAGE,
            Stub::makeEmpty(ProductReviewStorageClientInterface::class, [
                'findProductAbstractReview' => function (int $idProductAbstract) use ($ratingAndReviewCountByIdProductAbstract): ?ProductReviewStorageTransfer {
                    if (!isset($ratingAndReviewCountByIdProductAbstract[$idProductAbstract])) {
                        return null;
                    }

                    [$averageRating, $reviewCount] = $ratingAndReviewCountByIdProductAbstract[$idProductAbstract];

                    return (new ProductReviewStorageTransfer())->setAverageRating($averageRating)->setReviewCount($reviewCount);
                },
            ]),
        );
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function executeCompareProductsTool(array $arguments, bool $isNewTurn = true): array
    {
        if ($isNewTurn) {
            $this->getFactory()->createComparisonRegistry()->clear();
        }

        return (new CompareProductsToolPlugin())->execute($arguments);
    }

    /**
     * @param list<\Generated\Shared\Transfer\ProductSetDataStorageTransfer> $productSetDataStorageTransfers
     */
    public function haveProductSetPageSearchClientReturning(array $productSetDataStorageTransfers): void
    {
        $this->setUpCurrentStore();
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_SET_PAGE_SEARCH,
            Stub::makeEmpty(ProductSetPageSearchClientInterface::class, [
                'getProductSetList' => [
            'productSets' => array_map(
                static fn (ProductSetDataStorageTransfer $productSetDataStorageTransfer): ProductSetDataStorageTransfer => (new ProductSetDataStorageTransfer())
                        ->setIdProductSet($productSetDataStorageTransfer->getIdProductSet())
                        ->setName($productSetDataStorageTransfer->getName()),
                $productSetDataStorageTransfers,
            )],
            ]),
        );
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_SET_STORAGE,
            Stub::makeEmpty(ProductSetStorageClientInterface::class, [
                'getProductSetByIdProductSet' => function (int $idProductSet) use ($productSetDataStorageTransfers): ?ProductSetDataStorageTransfer {
                    foreach ($productSetDataStorageTransfers as $productSetDataStorageTransfer) {
                        if ($productSetDataStorageTransfer->getIdProductSet() === $idProductSet) {
                            return $productSetDataStorageTransfer;
                        }
                    }

                    return null;
                },
            ]),
        );
    }

    /**
     * @param list<int> $productAbstractIds
     */
    public function createProductSetDataStorage(int $idProductSet, string $name, string $url, array $productAbstractIds): ProductSetDataStorageTransfer
    {
        return (new ProductSetDataStorageTransfer())
            ->setIdProductSet($idProductSet)
            ->setName($name)
            ->setUrl($url)
            ->setProductAbstractIds($productAbstractIds);
    }

    /**
     * @return array<string, mixed>
     */
    public function executeProductSetsTool(): array
    {
        return (new ProductSetsToolPlugin())->execute();
    }

    /**
     * @param array<int, array<int, int>> $bundledQuantitiesByIdProductConcrete
     */
    public function haveProductBundleStorageClientReturning(array $bundledQuantitiesByIdProductConcrete): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRODUCT_BUNDLE_STORAGE,
            Stub::makeEmpty(ProductBundleStorageClientInterface::class, [
                'getProductBundles' => function (ProductBundleStorageCriteriaTransfer $productBundleStorageCriteriaTransfer) use ($bundledQuantitiesByIdProductConcrete): array {
                    $productBundleStorageTransfers = [];

                    foreach ($productBundleStorageCriteriaTransfer->getProductConcreteIds() as $idProductConcrete) {
                        if (isset($bundledQuantitiesByIdProductConcrete[$idProductConcrete])) {
                            $productBundleStorageTransfers[$idProductConcrete] = $this->createProductBundleStorage($bundledQuantitiesByIdProductConcrete[$idProductConcrete]);
                        }
                    }

                    return $productBundleStorageTransfers;
                },
            ]),
        );
    }

    /**
     * @param array<int, int> $quantitiesByIdProductConcrete
     */
    protected function createProductBundleStorage(array $quantitiesByIdProductConcrete): ProductBundleStorageTransfer
    {
        $productBundleStorageTransfer = new ProductBundleStorageTransfer();

        foreach ($quantitiesByIdProductConcrete as $idProductConcrete => $quantity) {
            $productBundleStorageTransfer->addBundledProduct(
                (new ProductForProductBundleStorageTransfer())
                    ->setIdProductConcrete($idProductConcrete)
                    ->setSku(sprintf('bundled-%d', $idProductConcrete))
                    ->setQuantity($quantity),
            );
        }

        return $productBundleStorageTransfer;
    }

    /**
     * @param list<int> $productAbstractIds
     * @param array<string, array<string, int>> $matchCountsByFacetName
     *
     * @return array<string, mixed>
     */
    public function createCatalogSearchResultWithRowsAndFacetCounts(
        array $productAbstractIds,
        int $totalResults,
        array $matchCountsByFacetName
    ): array {
        $facets = [];

        foreach ($matchCountsByFacetName as $facetName => $matchCounts) {
            $facetValues = [];

            foreach ($matchCounts as $value => $matchCount) {
                $facetValues[] = [static::FACET_VALUE_KEY_VALUE => (string)$value, static::FACET_VALUE_KEY_DOC_COUNT => $matchCount];
            }

            $facets[] = [static::FACET_KEY_NAME => $facetName, static::FACET_KEY_VALUES => $facetValues];
        }

        return [
            static::RESULT_FORMATTER_KEY_FACETS => $facets,
            static::RESULT_FORMATTER_KEY_PAGINATION => [static::PAGINATION_KEY_NUM_FOUND => $totalResults],
        ] + $this->createCatalogSearchResultWithRows($productAbstractIds);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function executeCatalogSearchToolInNewTurn(array $arguments): array
    {
        $this->setUpCurrentStore();
        $this->getFactory()->createCatalogSearchResultRegistry()->clear();

        return (new CatalogSearchToolPlugin())->execute($arguments);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCatalogSearchResultsOfTurn(): array
    {
        return $this->getFactory()->createCatalogSearchResultRegistry()->getSearchResults();
    }

    /**
     * @param list<int> $productAbstractIds
     *
     * @return array<string, mixed>
     */
    public function streamDisplayedProductsToolOutput(array $productAbstractIds): array
    {
        $this->enableStorefrontAssistant();
        $toolResult = (string)json_encode([
            static::RESULT_KEY_PRODUCTS => array_map(
                static fn (int $idProductAbstract): array => [static::KEY_ID_PRODUCT_ABSTRACT => $idProductAbstract],
                $productAbstractIds,
            ),
        ]);

        $streamedParts = $this->captureStreamedOutput(function () use ($toolResult): void {
            $this->createStorefrontAssistantSsePostToolCallPlugin()->postToolCall(
                (new AiToolCallTransfer())
                    ->setToolName(DisplayProductsToolPlugin::TOOL_NAME)
                    ->setToolArguments([])
                    ->setToolResult($toolResult)
                    ->setPromptRequest((new PromptRequestTransfer())->setAiConfigurationName(static::AI_CONFIGURATION_NAME_STOREFRONT_ASSISTANT)),
            );
        });

        foreach (explode("\n", $streamedParts) as $streamedEvent) {
            $payload = str_starts_with($streamedEvent, static::SSE_DATA_PREFIX)
                ? json_decode(substr($streamedEvent, strlen(static::SSE_DATA_PREFIX)), true)
                : null;
            $result = is_array($payload) ? ($payload[StreamEventKey::OUTPUT][static::KEY_RESULT] ?? null) : null;

            if (is_string($result)) {
                return (array)json_decode($result, true);
            }
        }

        return [];
    }

    public function composeProductDiscoverySystemPromptForMessage(string $message): string
    {
        $this->setUpCurrentStore();

        return $this->getFactory()->createPageContextSystemPromptComposer()->composeSystemPrompt(
            static::SYSTEM_PROMPT,
            (new StorefrontAssistantChatRequestTransfer())->setMessage($message),
        );
    }

    /**
     * @param array<int, string> $urlsByIdCategoryNode
     */
    public function haveCategoryStorageClientWithCategoryNodeUrls(array $urlsByIdCategoryNode): void
    {
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_CATEGORY_STORAGE,
            Stub::makeEmpty(CategoryStorageClientInterface::class, [
                'getCategoryNodeByIds' => static function (array $categoryNodeIds) use ($urlsByIdCategoryNode): array {
                    $categoryNodeStorageTransfers = [];

                    foreach ($categoryNodeIds as $idCategoryNode) {
                        $categoryNodeStorageTransfers[$idCategoryNode] = (new CategoryNodeStorageTransfer())
                            ->setNodeId($idCategoryNode)
                            ->setUrl($urlsByIdCategoryNode[$idCategoryNode] ?? null);
                    }

                    return $categoryNodeStorageTransfers;
                },
            ]),
        );
    }

    /**
     * @return array<int, int>
     */
    public function getPricedProductAbstractsResolvableByExpanders(int $count): array
    {
        $this->setUpProductStorageClientReadingPublishedData();
        $idProductAbstract = $this->getIdProductAbstractResolvableByExpanders();

        $productAbstractIds = SpyProductAbstractQuery::create()
            ->filterByIdProductAbstract($idProductAbstract, Criteria::GREATER_EQUAL)
            ->orderByIdProductAbstract(Criteria::ASC)
            ->limit(static::PRODUCT_ABSTRACT_LOOKUP_LIMIT)
            ->select(['IdProductAbstract'])
            ->find()
            ->getData();

        $pricesByIdProductAbstract = [];

        foreach (array_chunk(array_map('intval', $productAbstractIds), DisplayProductReader::MAX_PRODUCTS) as $productAbstractIdChunk) {
            foreach ($this->getDisplayedProductCards($productAbstractIdChunk) as $card) {
                $price = $card[static::PRODUCT_KEY_PRICE] ?? null;

                if (!is_int($price) || $price <= 0 || in_array($price, $pricesByIdProductAbstract, true)) {
                    continue;
                }

                $pricesByIdProductAbstract[(int)$card[static::KEY_ID_PRODUCT_ABSTRACT]] = $price;

                if (count($pricesByIdProductAbstract) === $count) {
                    return $pricesByIdProductAbstract;
                }
            }
        }

        $this->markTestSkipped(sprintf('Fewer than %d differently priced products are resolvable from storage.', $count));
    }

    /**
     * @param array<int, int> $productAbstractIds
     * @param array<int, string> $reasons
     *
     * @return array<string, mixed>
     */
    public function executeDisplaySetupTool(array $productAbstractIds, int $budget, array $reasons = []): array
    {
        return (new DisplaySetupToolPlugin())->execute([
            static::PARAMETER_ID_PRODUCT_ABSTRACTS => $productAbstractIds,
            SetupProposalBuilder::PARAMETER_BUDGET => $budget,
            SetupProposalBuilder::PARAMETER_REASONS => $reasons,
        ]);
    }

    /**
     * @param array<string, mixed> $toolResult
     *
     * @return array<int, array<string, mixed>>
     */
    public function enrichToolResultProducts(array $toolResult): array
    {
        $enrichedToolResult = json_decode(
            $this->getFactory()->createToolResultProductEnricher()->enrich((string)json_encode($toolResult)),
            true,
        );

        return $enrichedToolResult[static::RESULT_KEY_PRODUCTS] ?? [];
    }

    /**
     * @param array<int, int> $priceByIdProductAbstract
     */
    public function haveSameNameProductsPricedAt(string $name, array $priceByIdProductAbstract): void
    {
        $productAbstractStorageDataById = [];

        foreach (array_keys($priceByIdProductAbstract) as $idProductAbstract) {
            $sku = sprintf('outlier-%d', $idProductAbstract);
            $productAbstractStorageDataById[$idProductAbstract] = array_merge(
                $this->createProductAbstractStorageDataWithVariants($idProductAbstract, $sku, [], [], [sprintf('%s-1', $sku) => $idProductAbstract]),
                [static::KEY_NAME => $name],
            );
        }

        $this->haveProductStorageClientReturning($productAbstractStorageDataById);
        $this->setDependency(
            AiCommerceDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE,
            Stub::makeEmpty(PriceProductStorageClientInterface::class, [
                'getResolvedCurrentProductPriceTransfer' => static fn (PriceProductFilterTransfer $priceProductFilterTransfer): CurrentProductPriceTransfer => (new CurrentProductPriceTransfer())
                    ->setPrice($priceByIdProductAbstract[(int)$priceProductFilterTransfer->getIdProductAbstract()] ?? null),
            ]),
        );
    }
}
