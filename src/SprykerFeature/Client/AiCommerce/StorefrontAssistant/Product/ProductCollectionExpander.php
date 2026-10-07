<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\Store\StoreClientInterface;

class ProductCollectionExpander implements ProductCollectionExpanderInterface
{
    /**
     * @param array<\SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product\ProductExpanderInterface> $productExpanders
     * @param array<\SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantProductCollectionExpanderPluginInterface> $storefrontAssistantProductCollectionExpanderPlugins
     */
    public function __construct(
        protected array $productExpanders,
        protected array $storefrontAssistantProductCollectionExpanderPlugins,
        protected StoreClientInterface $storeClient,
        protected LocaleClientInterface $localeClient
    ) {
    }

    public function expand(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer
    ): StorefrontAssistantProductCollectionTransfer {
        if ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts()->count() === 0) {
            return $storefrontAssistantProductCollectionTransfer;
        }

        $localeName = $this->localeClient->getCurrentLocale();
        $storeName = (string)$this->storeClient->getCurrentStore()->getName();

        foreach ($this->productExpanders as $productExpander) {
            $storefrontAssistantProductCollectionTransfer = $productExpander->expand(
                $storefrontAssistantProductCollectionTransfer,
                $localeName,
                $storeName,
            );
        }

        foreach ($this->storefrontAssistantProductCollectionExpanderPlugins as $storefrontAssistantProductCollectionExpanderPlugin) {
            $storefrontAssistantProductCollectionTransfer = $storefrontAssistantProductCollectionExpanderPlugin->expand(
                $storefrontAssistantProductCollectionTransfer,
                $localeName,
                $storeName,
            );
        }

        return $storefrontAssistantProductCollectionTransfer;
    }
}
