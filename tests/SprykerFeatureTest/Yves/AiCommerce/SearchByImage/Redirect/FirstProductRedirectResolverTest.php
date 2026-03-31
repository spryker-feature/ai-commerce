<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\SearchByImage\Redirect;

use Codeception\Test\Unit;
use Spryker\Client\Catalog\CatalogClientInterface;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\FirstProductRedirectResolver;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\SearchResultsRedirectResolver;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group SearchByImage
 * @group Redirect
 * @group FirstProductRedirectResolverTest
 */
class FirstProductRedirectResolverTest extends Unit
{
    protected const string LOCALE_NAME = 'en_US';

    protected const string SEARCH_TERM = 'running shoes';

    protected const string PRODUCT_URL = '/en/shoe-123';

    protected const string SEARCH_TERM_WITHOUT_RESULTS = 'rare item';

    protected const string EXPECTED_FALLBACK_URL_WITHOUT_RESULTS = '/search?q=rare+item';

    protected const string SEARCH_TERM_WITHOUT_PRODUCT_URL = 'shoe';

    protected const string EXPECTED_FALLBACK_URL_WITHOUT_PRODUCT_URL = '/search?q=shoe';

    protected const string PRODUCT_NAME_WITHOUT_URL = 'no url key';

    protected const string SUGGESTION_BY_TYPE_KEY = 'suggestionByType';

    protected const string SUGGESTION_TYPE_PRODUCT_ABSTRACT = 'product_abstract';

    protected const string SUGGESTION_PRODUCT_URL_KEY = 'url';

    protected const string SUGGESTION_PRODUCT_NAME_KEY = 'name';

    protected AiCommerceYvesTester $tester;

    public function testGivenSearchTermWhenCatalogReturnProductsThenFirstProductUrlIsReturned(): void
    {
        // Arrange
        $resolver = $this->createResolver([static::SUGGESTION_BY_TYPE_KEY => [static::SUGGESTION_TYPE_PRODUCT_ABSTRACT => [[static::SUGGESTION_PRODUCT_URL_KEY => static::PRODUCT_URL]]]]);

        // Act
        $url = $resolver->resolve(static::SEARCH_TERM);

        // Assert
        $this->assertSame(static::PRODUCT_URL, $url);
    }

    public function testGivenSearchTermWhenCatalogReturnsNoProductsThenFallbackSearchUrlIsReturned(): void
    {
        // Arrange
        $resolver = $this->createResolver([]);

        // Act
        $url = $resolver->resolve(static::SEARCH_TERM_WITHOUT_RESULTS);

        // Assert
        $this->assertSame(static::EXPECTED_FALLBACK_URL_WITHOUT_RESULTS, $url);
    }

    public function testGivenSearchTermWhenFirstProductHasNoUrlKeyThenFallbackSearchUrlIsReturned(): void
    {
        // Arrange
        $resolver = $this->createResolver([static::SUGGESTION_BY_TYPE_KEY => [static::SUGGESTION_TYPE_PRODUCT_ABSTRACT => [[static::SUGGESTION_PRODUCT_NAME_KEY => static::PRODUCT_NAME_WITHOUT_URL]]]]);

        // Act
        $url = $resolver->resolve(static::SEARCH_TERM_WITHOUT_PRODUCT_URL);

        // Assert
        $this->assertSame(static::EXPECTED_FALLBACK_URL_WITHOUT_PRODUCT_URL, $url);
    }

    /**
     * @param array<string, mixed> $catalogSuggestions
     */
    protected function createResolver(array $catalogSuggestions): FirstProductRedirectResolver
    {
        $catalogClientMock = $this->createMock(CatalogClientInterface::class);
        $catalogClientMock->method('catalogSuggestSearch')->willReturn($catalogSuggestions);

        $routerMock = $this->createMock(UrlGeneratorInterface::class);
        $routerMock->method('generate')->willReturnCallback(
            fn (string $route, array $params) => sprintf('/%s?%s', $route, http_build_query($params)),
        );

        return new FirstProductRedirectResolver($catalogClientMock, new SearchResultsRedirectResolver($routerMock));
    }
}
