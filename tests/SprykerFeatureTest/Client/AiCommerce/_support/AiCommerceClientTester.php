<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\FacetConfigTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
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
use Orm\Zed\Store\Persistence\SpyStoreQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use ReflectionProperty;
use RuntimeException;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductImageStorage\ProductImageStorageClientInterface;
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
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\ProductDiscoveryAgentPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category\CategoryTreeFormatterInterface;
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
 *
 * @SuppressWarnings(PHPMD)
 */
class AiCommerceClientTester extends Actor
{
    use _generated\AiCommerceClientTesterActions;

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

    protected const string PRODUCT_KEY_COST_PRICE = 'cost_price';

    protected const string PARAMETER_ID_PRODUCT_ABSTRACTS = 'idProductAbstracts';

    protected const string RESULT_KEY_DISPLAYED_COUNT = 'displayedCount';

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
        $mappedFacetValues = [];

        foreach ($facetValues as $facetValue) {
            $mappedFacetValues[] = [
                static::FACET_VALUE_KEY_VALUE => $facetValue,
                static::FACET_VALUE_KEY_DOC_COUNT => static::FACET_VALUE_DOC_COUNT,
            ];
        }

        return [
            static::RESULT_FORMATTER_KEY_PRODUCTS => [],
            static::RESULT_FORMATTER_KEY_FACETS => [
                [
                    static::FACET_KEY_NAME => $facetName,
                    static::FACET_KEY_VALUES => $mappedFacetValues,
                ],
            ],
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
        array &$capturedRequestParameters
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
                ): array {
                    $capturedRequestParameters = $requestParameters;

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
     * @return array<int, string>
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
        ?StorefrontAssistantPageContextTransfer $storefrontAssistantPageContextTransfer = null
    ): string {
        $this->setUpCurrentStore();

        return $this->getFactory()->createPageContextSystemPromptComposer()->composeSystemPrompt(
            static::SYSTEM_PROMPT,
            (new StorefrontAssistantChatRequestTransfer())
                ->setStorefrontAssistantPageContext($storefrontAssistantPageContextTransfer),
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
}
