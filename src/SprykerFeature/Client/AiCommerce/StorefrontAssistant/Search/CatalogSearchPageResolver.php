<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

class CatalogSearchPageResolver implements CatalogSearchPageResolverInterface
{
    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::VALID_ITEMS_PER_PAGE_OPTIONS
     */
    protected const int ITEMS_PER_PAGE = 12;

    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::VALID_ITEMS_PER_PAGE_OPTIONS
     *
     * @var list<int>
     */
    protected const array VALID_ITEMS_PER_PAGE_OPTIONS = [12, 24, 36];

    public function resolveSearchPage(int $page, int $limit): int
    {
        $itemsPerPage = $this->resolveItemsPerPage($page, $limit);

        return intdiv(($page - 1) * $limit, $itemsPerPage) + 1;
    }

    public function resolveItemsPerPage(int $page, int $limit): int
    {
        $firstResult = ($page - 1) * $limit;
        $lastResult = $firstResult + $limit - 1;

        foreach (static::VALID_ITEMS_PER_PAGE_OPTIONS as $itemsPerPage) {
            if (intdiv($firstResult, $itemsPerPage) === intdiv($lastResult, $itemsPerPage)) {
                return $itemsPerPage;
            }
        }

        return static::ITEMS_PER_PAGE;
    }

    public function resolveOffsetWithinSearchPage(int $page, int $limit): int
    {
        return (($page - 1) * $limit) % $this->resolveItemsPerPage($page, $limit);
    }
}
