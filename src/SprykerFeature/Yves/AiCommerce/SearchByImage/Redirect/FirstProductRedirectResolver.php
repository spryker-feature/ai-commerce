<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect;

use Spryker\Client\Catalog\CatalogClientInterface;

class FirstProductRedirectResolver implements RedirectResolverInterface
{
    protected const string SUGGESTION_BY_TYPE_KEY = 'suggestionByType';

    protected const string SUGGESTION_TYPE_PRODUCT_ABSTRACT = 'product_abstract';

    protected const string SUGGESTION_PRODUCT_URL_KEY = 'url';

    public function __construct(
        protected CatalogClientInterface $catalogClient,
        protected RedirectResolverInterface $fallbackResolver,
    ) {
    }

    public function resolve(string $searchTerm): string
    {
        $suggestions = $this->catalogClient->catalogSuggestSearch($searchTerm);

        $productUrl = $suggestions[static::SUGGESTION_BY_TYPE_KEY][static::SUGGESTION_TYPE_PRODUCT_ABSTRACT][0][static::SUGGESTION_PRODUCT_URL_KEY] ?? null;

        if ($productUrl === null) {
            return $this->fallbackResolver->resolve($searchTerm);
        }

        return $productUrl;
    }
}
