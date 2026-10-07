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
use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Money\MoneyClientInterface;
use Spryker\Client\Price\PriceClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductImageStorage\ProductImageStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductSearchConfigStorage\Plugin\Config\ProductSearchConfigExpanderPlugin;
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
        return [
            new ProductSearchConfigExpanderPlugin(),
        ];
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
