<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use Spryker\Client\ProductLabelStorage\ProductLabelStorageClientInterface;

class ProductLabelProductExpander implements ProductExpanderInterface
{
    public function __construct(protected ProductLabelStorageClientInterface $productLabelStorageClient)
    {
    }

    public function expand(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer,
        string $localeName,
        string $storeName
    ): StorefrontAssistantProductCollectionTransfer {
        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $labelIds = $storefrontAssistantProductTransfer->getLabelIds();

            if ($labelIds === []) {
                continue;
            }

            $storefrontAssistantProductTransfer->setLabels(
                $this->resolveLabelNames($labelIds, $localeName, $storeName),
            );
        }

        return $storefrontAssistantProductCollectionTransfer;
    }

    /**
     * @param array<int> $labelIds
     *
     * @return list<string>
     */
    protected function resolveLabelNames(array $labelIds, string $localeName, string $storeName): array
    {
        $labelNames = [];

        foreach ($this->productLabelStorageClient->findLabels($labelIds, $localeName, $storeName) as $productLabelDictionaryItemTransfer) {
            $labelName = trim((string)$productLabelDictionaryItemTransfer->getName());

            if ($labelName !== '') {
                $labelNames[] = $labelName;
            }
        }

        return array_values(array_unique($labelNames));
    }
}
