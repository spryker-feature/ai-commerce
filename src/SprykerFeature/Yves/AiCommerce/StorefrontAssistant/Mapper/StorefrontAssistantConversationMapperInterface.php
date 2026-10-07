<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionTransfer;

interface StorefrontAssistantConversationMapperInterface
{
    /**
     * @return array<string, mixed>
     */
    public function mapStorefrontAssistantConversationCollectionToArray(
        StorefrontAssistantConversationCollectionTransfer $storefrontAssistantConversationCollectionTransfer
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function mapStorefrontAssistantConversationMessageCollectionToArray(
        StorefrontAssistantConversationMessageCollectionTransfer $storefrontAssistantConversationMessageCollectionTransfer
    ): array;
}
