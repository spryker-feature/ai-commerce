<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

class ToolCallIdResolver implements ToolCallIdResolverInterface
{
    protected const string TOOL_CALL_ID_FORMAT = 'call_%s_%s';

    /**
     * @var int<1, max>
     */
    protected const int SYNTHETIC_SUFFIX_BYTES = 8;

    protected const int TOOL_NAME_HASH_LENGTH = 8;

    public function resolveToolCallId(string $toolName, ?string $providerToolCallId = null): string
    {
        if ($this->isProviderToolCallIdUsable($toolName, $providerToolCallId)) {
            return (string)$providerToolCallId;
        }

        return sprintf(
            static::TOOL_CALL_ID_FORMAT,
            $this->hashToolName($toolName),
            bin2hex(random_bytes(static::SYNTHETIC_SUFFIX_BYTES)),
        );
    }

    protected function isProviderToolCallIdUsable(string $toolName, ?string $providerToolCallId): bool
    {
        // NeuronAI's Gemini provider sends the function name as the tool-call id; other providers send unique ids.
        return $providerToolCallId !== null && $providerToolCallId !== '' && $providerToolCallId !== $toolName;
    }

    protected function hashToolName(string $toolName): string
    {
        return substr(md5($toolName), 0, static::TOOL_NAME_HASH_LENGTH);
    }
}
