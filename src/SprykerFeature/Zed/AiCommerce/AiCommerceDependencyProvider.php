<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce;

use Orm\Zed\Discount\Persistence\SpyDiscountQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AiCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_AI_FOUNDATION = 'FACADE_AI_FOUNDATION';

    public const string FACADE_DISCOUNT = 'FACADE_DISCOUNT';

    public const string FACADE_GLOSSARY = 'FACADE_GLOSSARY';

    public const string FACADE_OMS = 'FACADE_OMS';

    public const string FACADE_SALES = 'FACADE_SALES';

    public const string FACADE_USER = 'FACADE_USER';

    public const string FACADE_QUOTE = 'FACADE_QUOTE';

    public const string FACADE_CART = 'FACADE_CART';

    public const string FACADE_CUSTOMER = 'FACADE_CUSTOMER';

    public const string FACADE_CHECKOUT = 'FACADE_CHECKOUT';

    public const string FACADE_SHIPMENT = 'FACADE_SHIPMENT';

    public const string FACADE_PAYMENT = 'FACADE_PAYMENT';

    public const string FACADE_CART_CODE = 'FACADE_CART_CODE';

    public const string FACADE_CART_NOTE = 'FACADE_CART_NOTE';

    public const string FACADE_MESSENGER = 'FACADE_MESSENGER';

    public const string PLUGINS_BACKOFFICE_ASSISTANT_AGENT = 'PLUGINS_BACKOFFICE_ASSISTANT_AGENT';

    public const string PROPEL_QUERY_DISCOUNT = 'PROPEL_QUERY_DISCOUNT';

    public const string PROPEL_QUERY_SALES_ORDER = 'PROPEL_QUERY_SALES_ORDER';

    /**
     * @uses \Spryker\Zed\Form\Communication\Plugin\Application\FormApplicationPlugin::SERVICE_FORM_CSRF_PROVIDER
     */
    public const string SERVICE_FORM_CSRF_PROVIDER = 'form.csrf_provider';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);

        $container = $this->addUserFacade($container);
        $container = $this->addCsrfProviderService($container);
        $container = $this->addGlossaryFacade($container);
        $container = $this->addAiFoundationFacade($container);
        $container = $this->addBackofficeAssistantAgentPlugins($container);

        return $container;
    }

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container = $this->addAiFoundationFacade($container);
        $container = $this->addDiscountFacade($container);
        $container = $this->addOmsFacade($container);
        $container = $this->addSalesFacade($container);
        $container = $this->addQuoteFacade($container);
        $container = $this->addCartFacade($container);
        $container = $this->addCustomerFacade($container);
        $container = $this->addCheckoutFacade($container);
        $container = $this->addShipmentFacade($container);
        $container = $this->addPaymentFacade($container);
        $container = $this->addCartCodeFacade($container);
        $container = $this->addCartNoteFacade($container);
        $container = $this->addMessengerFacade($container);

        return $container;
    }

    protected function addUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_USER, function (Container $container) {
            return $container->getLocator()->user()->facade();
        });

        return $container;
    }

    protected function addAiFoundationFacade(Container $container): Container
    {
        $container->set(static::FACADE_AI_FOUNDATION, function (Container $container) {
            return $container->getLocator()->aiFoundation()->facade();
        });

        return $container;
    }

    protected function addGlossaryFacade(Container $container): Container
    {
        $container->set(static::FACADE_GLOSSARY, function (Container $container) {
            return $container->getLocator()->glossary()->facade();
        });

        return $container;
    }

    protected function addOmsFacade(Container $container): Container
    {
        $container->set(static::FACADE_OMS, function (Container $container) {
            return $container->getLocator()->oms()->facade();
        });

        return $container;
    }

    protected function addSalesFacade(Container $container): Container
    {
        $container->set(static::FACADE_SALES, function (Container $container) {
            return $container->getLocator()->sales()->facade();
        });

        return $container;
    }

    protected function addBackofficeAssistantAgentPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_BACKOFFICE_ASSISTANT_AGENT, function (Container $container): array {
            return $this->getBackofficeAssistantAgentPlugins();
        });

        return $container;
    }

    public function providePersistenceLayerDependencies(Container $container): Container
    {
        $container = parent::providePersistenceLayerDependencies($container);
        $container = $this->addDiscountPropelQuery($container);
        $container = $this->addSalesOrderPropelQuery($container);

        return $container;
    }

    protected function addDiscountFacade(Container $container): Container
    {
        $container->set(static::FACADE_DISCOUNT, function (Container $container) {
            return $container->getLocator()->discount()->facade();
        });

        return $container;
    }

    protected function addDiscountPropelQuery(Container $container): Container
    {
        $container->set(static::PROPEL_QUERY_DISCOUNT, $container->factory(function (): SpyDiscountQuery {
            return SpyDiscountQuery::create();
        }));

        return $container;
    }

    protected function addSalesOrderPropelQuery(Container $container): Container
    {
        $container->set(static::PROPEL_QUERY_SALES_ORDER, $container->factory(function (): SpySalesOrderQuery {
            return SpySalesOrderQuery::create();
        }));

        return $container;
    }

    protected function addCsrfProviderService(Container $container): Container
    {
        $container->set(static::SERVICE_FORM_CSRF_PROVIDER, function (Container $container): CsrfTokenManagerInterface {
            return $container->getApplicationService(static::SERVICE_FORM_CSRF_PROVIDER);
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface>
     */
    protected function getBackofficeAssistantAgentPlugins(): array
    {
        return [];
    }

    protected function addQuoteFacade(Container $container): Container
    {
        $container->set(static::FACADE_QUOTE, function (Container $container) {
            return $container->getLocator()->quote()->facade();
        });

        return $container;
    }

    protected function addCartFacade(Container $container): Container
    {
        $container->set(static::FACADE_CART, function (Container $container) {
            return $container->getLocator()->cart()->facade();
        });

        return $container;
    }

    protected function addCustomerFacade(Container $container): Container
    {
        $container->set(static::FACADE_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->facade();
        });

        return $container;
    }

    protected function addCheckoutFacade(Container $container): Container
    {
        $container->set(static::FACADE_CHECKOUT, function (Container $container) {
            return $container->getLocator()->checkout()->facade();
        });

        return $container;
    }

    protected function addShipmentFacade(Container $container): Container
    {
        $container->set(static::FACADE_SHIPMENT, function (Container $container) {
            return $container->getLocator()->shipment()->facade();
        });

        return $container;
    }

    protected function addPaymentFacade(Container $container): Container
    {
        $container->set(static::FACADE_PAYMENT, function (Container $container) {
            return $container->getLocator()->payment()->facade();
        });

        return $container;
    }

    protected function addCartCodeFacade(Container $container): Container
    {
        $container->set(static::FACADE_CART_CODE, function (Container $container) {
            return $container->getLocator()->cartCode()->facade();
        });

        return $container;
    }

    protected function addCartNoteFacade(Container $container): Container
    {
        $container->set(static::FACADE_CART_NOTE, function (Container $container) {
            return $container->getLocator()->cartNote()->facade();
        });

        return $container;
    }

    protected function addMessengerFacade(Container $container): Container
    {
        $container->set(static::FACADE_MESSENGER, function (Container $container) {
            return $container->getLocator()->messenger()->facade();
        });

        return $container;
    }
}
