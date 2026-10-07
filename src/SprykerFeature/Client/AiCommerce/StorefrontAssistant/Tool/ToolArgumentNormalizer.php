<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool;

class ToolArgumentNormalizer implements ToolArgumentNormalizerInterface
{
    /**
     * AI providers pass named arguments; a direct `execute([...])` call wraps them as `[0 => [...]]`, which is unwrapped here.
     *
     * @param array<int|string, mixed> $arguments
     *
     * @return array<int|string, mixed>
     */
    public function normalizeArguments(array $arguments): array
    {
        if (array_key_exists(0, $arguments) && is_array($arguments[0])) {
            return $arguments[0];
        }

        return $arguments;
    }
}
