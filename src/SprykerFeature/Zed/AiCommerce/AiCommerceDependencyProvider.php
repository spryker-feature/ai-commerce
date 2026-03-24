<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AiCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_AI_FOUNDATION = 'FACADE_AI_FOUNDATION';

    public const string FACADE_GLOSSARY = 'FACADE_GLOSSARY';

    public const string FACADE_USER = 'FACADE_USER';

    public const string SERVICE_AI_COMMERCE = 'SERVICE_AI_COMMERCE';

    public const string PLUGINS_BACKOFFICE_ASSISTANT_AGENT = 'PLUGINS_BACKOFFICE_ASSISTANT_AGENT';

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

        $container = $this->addAiCommerceService($container);
        $container = $this->addAiFoundationFacade($container);
        $container = $this->addGlossaryFacade($container);
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

    protected function addAiCommerceService(Container $container): Container
    {
        $container->set(static::SERVICE_AI_COMMERCE, function (Container $container) {
            return $container->getLocator()->aiCommerce()->service();
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

    protected function addBackofficeAssistantAgentPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_BACKOFFICE_ASSISTANT_AGENT, function (Container $container): array {
            return $this->getBackofficeAssistantAgentPlugins();
        });

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
