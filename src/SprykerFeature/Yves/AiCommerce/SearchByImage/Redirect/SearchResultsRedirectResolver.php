<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SearchResultsRedirectResolver implements RedirectResolverInterface
{
    /**
     * @uses \SprykerShop\Yves\CatalogPage\Plugin\Router\CatalogPageRouteProviderPlugin::ROUTE_NAME_SEARCH
     */
    protected const string ROUTE_SEARCH = 'search';

    /**
     * @see \SprykerShop\Yves\CatalogPage\Controller\CatalogController::executeFulltextSearchAction
     */
    protected const string QUERY_PARAMETER_SEARCH_TERM = 'q';

    public function __construct(protected UrlGeneratorInterface $router)
    {
    }

    public function resolve(string $searchTerm): string
    {
        return $this->router->generate(static::ROUTE_SEARCH, [static::QUERY_PARAMETER_SEARCH_TERM => $searchTerm]);
    }
}
