<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Product;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantStoreUrlMapperInterface;

class StoreUrlProductExpander implements ProductExpanderInterface
{
    public function __construct(protected StorefrontAssistantStoreUrlMapperInterface $storefrontAssistantStoreUrlMapper)
    {
    }

    public function expand(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer,
        string $localeName,
        string $storeName
    ): StorefrontAssistantProductCollectionTransfer {
        foreach ($storefrontAssistantProductCollectionTransfer->getStorefrontAssistantProducts() as $storefrontAssistantProductTransfer) {
            $url = (string)$storefrontAssistantProductTransfer->getUrl();

            if ($url === '') {
                continue;
            }

            $storefrontAssistantProductTransfer->setUrl($this->storefrontAssistantStoreUrlMapper->mapUrlToStoreUrl($url));
        }

        return $storefrontAssistantProductCollectionTransfer;
    }
}
