<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;

interface StorefrontAssistantRequestMapperInterface
{
    /**
     * @param array<mixed> $payload
     */
    public function mapPayloadToStorefrontAssistantChatRequest(
        array $payload,
        string $customerReference
    ): StorefrontAssistantChatRequestTransfer;

    /**
     * @param array<string> $toolNames
     */
    public function mapStorefrontAssistantChatRequestToPromptRequest(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer,
        string $aiConfigurationName,
        string $scopedConversationReference,
        array $toolNames = [],
        ?string $systemPrompt = null
    ): PromptRequestTransfer;
}
