<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

class AiCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);

        return $container;
    }
}
