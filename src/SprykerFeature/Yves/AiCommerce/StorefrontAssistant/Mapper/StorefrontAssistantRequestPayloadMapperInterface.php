<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\StorefrontAssistantConversationMessageCollectionCriteriaTransfer;

interface StorefrontAssistantRequestPayloadMapperInterface
{
    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToStorefrontAssistantChatRequestTransfer(
        array $payload,
        string $customerReference,
        string $localeName
    ): StorefrontAssistantChatRequestTransfer;

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToConversationCollectionCriteriaTransfer(
        array $payload,
        string $customerReference
    ): StorefrontAssistantConversationCollectionCriteriaTransfer;

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToConversationCollectionDeleteCriteriaTransfer(
        array $payload,
        string $customerReference
    ): StorefrontAssistantConversationCollectionDeleteCriteriaTransfer;

    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToConversationMessageCollectionCriteriaTransfer(
        array $payload,
        string $customerReference
    ): StorefrontAssistantConversationMessageCollectionCriteriaTransfer;
}
