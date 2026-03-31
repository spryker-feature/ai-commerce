<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Kernel\AbstractFactory;
use SprykerFeature\Client\AiCommerce\SearchByImage\AiSearchByImageTermResolver;
use SprykerFeature\Client\AiCommerce\SearchByImage\AiSearchByImageTermResolverInterface;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 */
class AiCommerceFactory extends AbstractFactory
{
    public function createAiSearchByImageTermResolver(): AiSearchByImageTermResolverInterface
    {
        return new AiSearchByImageTermResolver(
            $this->getAiFoundationClient(),
            $this->getConfig(),
        );
    }

    public function getAiFoundationClient(): AiFoundationClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION);
    }
}
