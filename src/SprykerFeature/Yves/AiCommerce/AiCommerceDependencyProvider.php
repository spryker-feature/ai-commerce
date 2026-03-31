<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce;

use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Shared\Kernel\ContainerInterface;
use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

class AiCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_AI_FOUNDATION = 'CLIENT_AI_FOUNDATION';

    public const string CLIENT_CATALOG = 'CLIENT_CATALOG';

    public const string CLIENT_LOCALE = 'CLIENT_LOCALE';

    /**
     * @uses \Spryker\Yves\Messenger\Plugin\Application\FlashMessengerApplicationPlugin::SERVICE_FLASH_MESSENGER
     */
    public const string SERVICE_FLASH_MESSENGER = 'flash_messenger';

    /**
     * @uses \Spryker\Yves\Translator\Plugin\Application\TranslatorApplicationPlugin::SERVICE_TRANSLATOR
     */
    public const string SERVICE_TRANSLATOR = 'translator';

    /**
     * @see \Spryker\Shared\Application\ApplicationConstants::FORM_FACTORY
     */
    public const string FORM_FACTORY = 'FORM_FACTORY';

    /**
     * @see \Spryker\Yves\Router\Plugin\Application\RouterApplicationPlugin::SERVICE_ROUTER
     */
    public const string SERVICE_ROUTER = 'routers';

    public const string CLIENT_GLOSSARY_STORAGE = 'CLIENT_GLOSSARY_STORAGE';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addAiFoundationClient($container);
        $container = $this->addCatalogClient($container);
        $container = $this->addLocaleClient($container);
        $container = $this->addFlashMessengerService($container);
        $container = $this->addTranslatorService($container);
        $container = $this->addGlossaryStorageClient($container);
        $container = $this->addRouter($container);

        return $container;
    }

    protected function addAiFoundationClient(Container $container): Container
    {
        $container->set(static::CLIENT_AI_FOUNDATION, function (Container $container) {
            return $container->getLocator()->aiFoundation()->client();
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

    protected function addLocaleClient(Container $container): Container
    {
        $container->set(static::CLIENT_LOCALE, function (Container $container) {
            return $container->getLocator()->locale()->client();
        });

        return $container;
    }

    protected function addFlashMessengerService(Container $container): Container
    {
        $container->set(static::SERVICE_FLASH_MESSENGER, function (ContainerInterface $container) {
            return $container->getApplicationService(static::SERVICE_FLASH_MESSENGER);
        });

        return $container;
    }

    protected function addTranslatorService(Container $container): Container
    {
        $container->set(static::SERVICE_TRANSLATOR, function (ContainerInterface $container) {
            return $container->getApplicationService(static::SERVICE_TRANSLATOR);
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

    protected function addRouter(Container $container): Container
    {
        $container->set(static::SERVICE_ROUTER, function (Container $container): mixed {
            return $container->getApplicationService(static::SERVICE_ROUTER);
        });

        return $container;
    }
}
