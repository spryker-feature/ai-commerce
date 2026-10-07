<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Category;

use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Store\StoreClientInterface;

class CategoryNodeNameReader implements CategoryNodeNameReaderInterface
{
    public function __construct(
        protected CategoryStorageClientInterface $categoryStorageClient,
        protected LocaleClientInterface $localeClient,
        protected StoreClientInterface $storeClient
    ) {
    }

    /**
     * @param list<int> $idsCategoryNode
     *
     * @return array<int, string>
     */
    public function getCategoryNodeNamesByIds(array $idsCategoryNode): array
    {
        if ($idsCategoryNode === []) {
            return [];
        }

        $categoryNodeStorageTransfers = $this->categoryStorageClient->getCategoryNodeByIds(
            $idsCategoryNode,
            $this->localeClient->getCurrentLocale(),
            $this->storeClient->getCurrentStore()->getNameOrFail(),
        );

        $categoryNodeNames = [];

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            $idCategoryNode = $categoryNodeStorageTransfer->getNodeId();
            $name = $categoryNodeStorageTransfer->getName();

            if ($idCategoryNode !== null && $name !== null) {
                $categoryNodeNames[$idCategoryNode] = $name;
            }
        }

        return $categoryNodeNames;
    }
}
