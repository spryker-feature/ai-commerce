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
use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
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

class AiCommerceDependencyProvider extends AbstractDependencyProvider
{
    public const string CLIENT_AI_FOUNDATION = 'CLIENT_AI_FOUNDATION';

    public const string CLIENT_STORAGE = 'CLIENT_STORAGE';

    public const string CLIENT_CATALOG = 'CLIENT_CATALOG';

    public const string CLIENT_CATEGORY_STORAGE = 'CLIENT_CATEGORY_STORAGE';

    public const string CLIENT_PRODUCT_STORAGE = 'CLIENT_PRODUCT_STORAGE';

    public const string CLIENT_PRODUCT_IMAGE_STORAGE = 'CLIENT_PRODUCT_IMAGE_STORAGE';

    public const string CLIENT_PRICE_PRODUCT_STORAGE = 'CLIENT_PRICE_PRODUCT_STORAGE';

    public const string CLIENT_PRODUCT_OFFER_STORAGE = 'CLIENT_PRODUCT_OFFER_STORAGE';

    public const string CLIENT_MONEY = 'CLIENT_MONEY';

    public const string CLIENT_PRICE = 'CLIENT_PRICE';

    public const string CLIENT_CURRENCY = 'CLIENT_CURRENCY';

    public const string CLIENT_STORE = 'CLIENT_STORE';

    public const string CLIENT_LOCALE = 'CLIENT_LOCALE';

    public const string CLIENT_GLOSSARY_STORAGE = 'CLIENT_GLOSSARY_STORAGE';

    public const string CLIENT_PRODUCT_LABEL_STORAGE = 'CLIENT_PRODUCT_LABEL_STORAGE';

    public const string CLIENT_AVAILABILITY_STORAGE = 'CLIENT_AVAILABILITY_STORAGE';

    public const string CLIENT_PRODUCT_DISCONTINUED_STORAGE = 'CLIENT_PRODUCT_DISCONTINUED_STORAGE';

    public const string CLIENT_PRODUCT_ALTERNATIVE_STORAGE = 'CLIENT_PRODUCT_ALTERNATIVE_STORAGE';

    public const string CLIENT_PRODUCT_OPTION_STORAGE = 'CLIENT_PRODUCT_OPTION_STORAGE';

    public const string CLIENT_MERCHANT_STORAGE = 'CLIENT_MERCHANT_STORAGE';

    public const string CLIENT_PERMISSION = 'CLIENT_PERMISSION';

    public const string CLIENT_PRODUCT_RELATION_STORAGE = 'CLIENT_PRODUCT_RELATION_STORAGE';

    public const string CLIENT_PRODUCT_CATEGORY_STORAGE = 'CLIENT_PRODUCT_CATEGORY_STORAGE';

    public const string CLIENT_CMS_STORAGE = 'CLIENT_CMS_STORAGE';

    /**
     * @api
     */
    public const string CLIENT_PRODUCT_REVIEW_STORAGE = 'CLIENT_PRODUCT_REVIEW_STORAGE';

    /**
     * @api
     */
    public const string CLIENT_PRODUCT_SET_PAGE_SEARCH = 'CLIENT_PRODUCT_SET_PAGE_SEARCH';

    /**
     * @api
     */
    public const string CLIENT_PRODUCT_SET_STORAGE = 'CLIENT_PRODUCT_SET_STORAGE';

    /**
     * @api
     */
    public const string CLIENT_PRODUCT_BUNDLE_STORAGE = 'CLIENT_PRODUCT_BUNDLE_STORAGE';

    /**
     * @api
     */
    public const string PLUGINS_STOREFRONT_ASSISTANT_AGENT = 'PLUGINS_STOREFRONT_ASSISTANT_AGENT';

    /**
     * @api
     *
     * @uses \Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface
     */
    public const string PLUGINS_SEARCH_CONFIG_EXPANDER = 'PLUGINS_SEARCH_CONFIG_EXPANDER';

    /**
     * @api
     */
    public const string PLUGINS_STOREFRONT_ASSISTANT_PRODUCT_COLLECTION_EXPANDER = 'PLUGINS_STOREFRONT_ASSISTANT_PRODUCT_COLLECTION_EXPANDER';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addAiFoundationClient($container);
        $container = $this->addStorageClient($container);
        $container = $this->addCatalogClient($container);
        $container = $this->addCategoryStorageClient($container);
        $container = $this->addProductStorageClient($container);
        $container = $this->addStoreClient($container);
        $container = $this->addLocaleClient($container);
        $container = $this->addGlossaryStorageClient($container);
        $container = $this->addProductImageStorageClient($container);
        $container = $this->addPriceProductStorageClient($container);
        $container = $this->addProductOfferStorageClient($container);
        $container = $this->addMoneyClient($container);
        $container = $this->addPriceClient($container);
        $container = $this->addCurrencyClient($container);
        $container = $this->addProductLabelStorageClient($container);
        $container = $this->addAvailabilityStorageClient($container);
        $container = $this->addProductDiscontinuedStorageClient($container);
        $container = $this->addProductAlternativeStorageClient($container);
        $container = $this->addProductOptionStorageClient($container);
        $container = $this->addMerchantStorageClient($container);
        $container = $this->addPermissionClient($container);
        $container = $this->addProductRelationStorageClient($container);
        $container = $this->addProductCategoryStorageClient($container);
        $container = $this->addCmsStorageClient($container);
        $container = $this->addProductReviewStorageClient($container);
        $container = $this->addProductSetPageSearchClient($container);
        $container = $this->addProductSetStorageClient($container);
        $container = $this->addProductBundleStorageClient($container);
        $container = $this->addStorefrontAssistantAgentPlugins($container);
        $container = $this->addStorefrontAssistantProductCollectionExpanderPlugins($container);
        $container = $this->addSearchConfigExpanderPlugins($container);

        return $container;
    }

    /**
     * @return list<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface>
     */
    protected function getSearchConfigExpanderPlugins(): array
    {
        return [];
    }

    protected function addSearchConfigExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_SEARCH_CONFIG_EXPANDER, function (): array {
            return $this->getSearchConfigExpanderPlugins();
        });

        return $container;
    }

    protected function addProductStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_STORAGE, function (Container $container): ProductStorageClientInterface {
            return $container->getLocator()->productStorage()->client();
        });

        return $container;
    }

    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, function (Container $container): StoreClientInterface {
            return $container->getLocator()->store()->client();
        });

        return $container;
    }

    protected function addLocaleClient(Container $container): Container
    {
        $container->set(static::CLIENT_LOCALE, function (Container $container): LocaleClientInterface {
            return $container->getLocator()->locale()->client();
        });

        return $container;
    }

    protected function addGlossaryStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_GLOSSARY_STORAGE, function (Container $container): GlossaryStorageClientInterface {
            return $container->getLocator()->glossaryStorage()->client();
        });

        return $container;
    }

    protected function addCatalogClient(Container $container): Container
    {
        $container->set(static::CLIENT_CATALOG, function (Container $container): CatalogClientInterface {
            return $container->getLocator()->catalog()->client();
        });

        return $container;
    }

    protected function addCategoryStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_CATEGORY_STORAGE, function (Container $container): CategoryStorageClientInterface {
            return $container->getLocator()->categoryStorage()->client();
        });

        return $container;
    }

    protected function addProductImageStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_IMAGE_STORAGE, function (Container $container): ProductImageStorageClientInterface {
            return $container->getLocator()->productImageStorage()->client();
        });

        return $container;
    }

    protected function addPriceProductStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRICE_PRODUCT_STORAGE, function (Container $container): PriceProductStorageClientInterface {
            return $container->getLocator()->priceProductStorage()->client();
        });

        return $container;
    }

    protected function addProductOfferStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_OFFER_STORAGE, function (Container $container): ProductOfferStorageClientInterface {
            return $container->getLocator()->productOfferStorage()->client();
        });

        return $container;
    }

    protected function addMoneyClient(Container $container): Container
    {
        $container->set(static::CLIENT_MONEY, function (Container $container): MoneyClientInterface {
            return $container->getLocator()->money()->client();
        });

        return $container;
    }

    protected function addPriceClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRICE, function (Container $container): PriceClientInterface {
            return $container->getLocator()->price()->client();
        });

        return $container;
    }

    protected function addCurrencyClient(Container $container): Container
    {
        $container->set(static::CLIENT_CURRENCY, function (Container $container): CurrencyClientInterface {
            return $container->getLocator()->currency()->client();
        });

        return $container;
    }

    protected function addProductLabelStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_LABEL_STORAGE, function (Container $container): ProductLabelStorageClientInterface {
            return $container->getLocator()->productLabelStorage()->client();
        });

        return $container;
    }

    protected function addAvailabilityStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_AVAILABILITY_STORAGE, function (Container $container): AvailabilityStorageClientInterface {
            return $container->getLocator()->availabilityStorage()->client();
        });

        return $container;
    }

    protected function addProductDiscontinuedStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_DISCONTINUED_STORAGE, function (Container $container): ProductDiscontinuedStorageClientInterface {
            return $container->getLocator()->productDiscontinuedStorage()->client();
        });

        return $container;
    }

    protected function addProductAlternativeStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_ALTERNATIVE_STORAGE, function (Container $container): ProductAlternativeStorageClientInterface {
            return $container->getLocator()->productAlternativeStorage()->client();
        });

        return $container;
    }

    protected function addProductOptionStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_OPTION_STORAGE, function (Container $container): ProductOptionStorageClientInterface {
            return $container->getLocator()->productOptionStorage()->client();
        });

        return $container;
    }

    protected function addMerchantStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_MERCHANT_STORAGE, function (Container $container): MerchantStorageClientInterface {
            return $container->getLocator()->merchantStorage()->client();
        });

        return $container;
    }

    protected function addPermissionClient(Container $container): Container
    {
        $container->set(static::CLIENT_PERMISSION, function (Container $container): PermissionClientInterface {
            return $container->getLocator()->permission()->client();
        });

        return $container;
    }

    protected function addProductRelationStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_RELATION_STORAGE, function (Container $container): ProductRelationStorageClientInterface {
            return $container->getLocator()->productRelationStorage()->client();
        });

        return $container;
    }

    protected function addProductCategoryStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_CATEGORY_STORAGE, function (Container $container): ProductCategoryStorageClientInterface {
            return $container->getLocator()->productCategoryStorage()->client();
        });

        return $container;
    }

    protected function addCmsStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_CMS_STORAGE, function (Container $container): CmsStorageClientInterface {
            return $container->getLocator()->cmsStorage()->client();
        });

        return $container;
    }

    protected function addProductReviewStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_REVIEW_STORAGE, function (Container $container): ProductReviewStorageClientInterface {
            return $container->getLocator()->productReviewStorage()->client();
        });

        return $container;
    }

    protected function addProductSetPageSearchClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_SET_PAGE_SEARCH, function (Container $container): ProductSetPageSearchClientInterface {
            return $container->getLocator()->productSetPageSearch()->client();
        });

        return $container;
    }

    protected function addProductSetStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_SET_STORAGE, function (Container $container): ProductSetStorageClientInterface {
            return $container->getLocator()->productSetStorage()->client();
        });

        return $container;
    }

    protected function addProductBundleStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_BUNDLE_STORAGE, function (Container $container): ProductBundleStorageClientInterface {
            return $container->getLocator()->productBundleStorage()->client();
        });

        return $container;
    }

    protected function addStorefrontAssistantProductCollectionExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_STOREFRONT_ASSISTANT_PRODUCT_COLLECTION_EXPANDER, function (Container $container): array {
            return $this->getStorefrontAssistantProductCollectionExpanderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantProductCollectionExpanderPluginInterface>
     */
    protected function getStorefrontAssistantProductCollectionExpanderPlugins(): array
    {
        return [];
    }

    protected function addStorefrontAssistantAgentPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_STOREFRONT_ASSISTANT_AGENT, function (Container $container): array {
            return $this->getStorefrontAssistantAgentPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantAgentPluginInterface>
     */
    protected function getStorefrontAssistantAgentPlugins(): array
    {
        return [];
    }

    protected function addAiFoundationClient(Container $container): Container
    {
        $container->set(static::CLIENT_AI_FOUNDATION, function (Container $container): AiFoundationClientInterface {
            return $container->getLocator()->aiFoundation()->client();
        });

        return $container;
    }

    protected function addStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORAGE, function (Container $container): StorageClientInterface {
            return $container->getLocator()->storage()->client();
        });

        return $container;
    }
}
