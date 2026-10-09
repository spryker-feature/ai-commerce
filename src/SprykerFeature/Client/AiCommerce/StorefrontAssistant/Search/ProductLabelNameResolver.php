<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductLabelStorage\ProductLabelStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;

class ProductLabelNameResolver implements ProductLabelNameResolverInterface
{
    public function __construct(
        protected ProductLabelStorageClientInterface $productLabelStorageClient,
        protected LocaleClientInterface $localeClient,
        protected StoreClientInterface $storeClient
    ) {
    }

    /**
     * @param array<int, string> $labels
     *
     * @return array<int, string>
     */
    public function resolveLabelNames(array $labels): array
    {
        if ($labels === []) {
            return [];
        }

        $localeName = $this->localeClient->getCurrentLocale();
        $storeName = $this->storeClient->getCurrentStore()->getNameOrFail();
        $labelNames = [];

        foreach ($labels as $label) {
            $labelNames[] = $this->resolveLabelName($label, $localeName, $storeName);
        }

        return array_values(array_unique($labelNames));
    }

    protected function resolveLabelName(string $label, string $localeName, string $storeName): string
    {
        foreach ($this->getLabelKeyCandidates($label) as $labelKey) {
            $productLabelDictionaryItemTransfer = $this->productLabelStorageClient->findLabelByName(
                $labelKey,
                $localeName,
                $storeName,
            );

            if ($productLabelDictionaryItemTransfer !== null && $productLabelDictionaryItemTransfer->getName()) {
                return $productLabelDictionaryItemTransfer->getName();
            }
        }

        return $label;
    }

    /**
     * @return array<int, string>
     */
    protected function getLabelKeyCandidates(string $label): array
    {
        return array_values(array_unique([
            $label,
            mb_strtoupper($label),
            ucfirst(mb_strtolower($label)),
        ]));
    }
}
