<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;

class SearchByImageRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    protected const string ROUTE_SEARCH_BY_IMAGE = 'search-by-image';

    protected const string PATTERN_SEARCH_BY_IMAGE = '/search-by-image';

    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addSearchByImageRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\AiCommerce\Controller\SearchByImageController::searchAction()
     */
    protected function addSearchByImageRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(static::PATTERN_SEARCH_BY_IMAGE, 'AiCommerce', 'SearchByImage', 'search');

        $routeCollection->add(static::ROUTE_SEARCH_BY_IMAGE, $route);

        return $routeCollection;
    }
}
