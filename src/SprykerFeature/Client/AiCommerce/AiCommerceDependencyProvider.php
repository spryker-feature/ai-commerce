<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;

class AiCommerceDependencyProvider extends AbstractDependencyProvider
{
    public const string CLIENT_AI_FOUNDATION = 'CLIENT_AI_FOUNDATION';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addAiFoundationClient($container);

        return $container;
    }

    protected function addAiFoundationClient(Container $container): Container
    {
        $container->set(static::CLIENT_AI_FOUNDATION, function (Container $container): AiFoundationClientInterface {
            return $container->getLocator()->aiFoundation()->client();
        });

        return $container;
    }
}
