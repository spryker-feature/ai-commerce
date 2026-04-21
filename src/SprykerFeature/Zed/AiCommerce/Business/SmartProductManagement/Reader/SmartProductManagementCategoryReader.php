<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Reader;

use Spryker\Zed\Category\Business\CategoryFacadeInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;

class SmartProductManagementCategoryReader implements SmartProductManagementCategoryReaderInterface
{
    public function __construct(
        protected readonly CategoryFacadeInterface $categoryFacade,
        protected readonly LocaleFacadeInterface $localeFacade,
    ) {
    }

    public function getCategoryIdsIndexedByName(): array
    {
        $categoryCollectionTransfer = $this->categoryFacade
            ->getCategoryOptionCollection($this->localeFacade->getCurrentLocale());

        $categories = [];
        foreach ($categoryCollectionTransfer->getCategories() as $categoryTransfer) {
            $categories[$categoryTransfer->getNameOrFail()] = $categoryTransfer->getIdCategoryOrFail();
        }

        return $categories;
    }
}
