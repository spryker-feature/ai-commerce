<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce;

use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AiCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_AI_FOUNDATION = 'FACADE_AI_FOUNDATION';

    public const string FACADE_GLOSSARY = 'FACADE_GLOSSARY';

    public const string FACADE_OMS = 'FACADE_OMS';

    public const string FACADE_SALES = 'FACADE_SALES';

    public const string FACADE_USER = 'FACADE_USER';

    public const string PLUGINS_BACKOFFICE_ASSISTANT_AGENT = 'PLUGINS_BACKOFFICE_ASSISTANT_AGENT';

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
        $container = $this->addBackofficeAssistantAgentPlugins($container);

        return $container;
    }

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container = $this->addAiFoundationFacade($container);
        $container = $this->addGlossaryFacade($container);
        $container = $this->addOmsFacade($container);
        $container = $this->addSalesFacade($container);
        $container = $this->addBackofficeAssistantAgentPlugins($container);

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
        $container = $this->addSalesOrderPropelQuery($container);

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
}
