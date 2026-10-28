<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat;

interface BackofficeAssistantChatPayloadValidatorInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function isPromptPayloadValid(array $payload): bool;

    /**
     * @param array<string, mixed> $payload
     */
    public function isConversationDeletePayloadValid(array $payload): bool;
}
