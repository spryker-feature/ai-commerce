<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface CatalogSearchPageResolverInterface
{
    public function resolveSearchPage(int $page, int $limit): int;

    public function resolveItemsPerPage(int $page, int $limit): int;

    public function resolveOffsetWithinSearchPage(int $page, int $limit): int;
}
