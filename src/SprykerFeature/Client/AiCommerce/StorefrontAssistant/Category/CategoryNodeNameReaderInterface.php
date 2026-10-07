<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category;

interface CategoryNodeNameReaderInterface
{
    /**
     * @param list<int> $idsCategoryNode
     *
     * @return array<int, string>
     */
    public function getCategoryNodeNamesByIds(array $idsCategoryNode): array;
}
