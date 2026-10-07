<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant;

use Generated\Shared\Transfer\StorefrontAssistantProductCollectionTransfer;

interface StorefrontAssistantProductCollectionExpanderPluginInterface
{
    /**
     * Specification:
     * - Expands `StorefrontAssistantProductCollectionTransfer` with additional data.
     * - Receives the whole collection and should batch its reads where a bulk API is available.
     * - Returns the collection unchanged when it has nothing to add.
     * - Runs after the built-in product expanders, in plugin stack order.
     * - Runs once per non-empty product list the assistant expands: catalog search results, displayed product cards,
     *   the shopper stream and conversation history replay.
     * - Use it to add card data such as badges or custom attributes.
     * - An exception aborts the current chat turn or history read, so implementations should degrade gracefully
     *   instead of throwing.
     *
     * @api
     */
    public function expand(
        StorefrontAssistantProductCollectionTransfer $storefrontAssistantProductCollectionTransfer,
        string $localeName,
        string $storeName
    ): StorefrontAssistantProductCollectionTransfer;
}
