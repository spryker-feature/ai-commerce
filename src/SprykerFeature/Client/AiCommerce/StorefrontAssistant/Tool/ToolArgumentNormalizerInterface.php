<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Tool;

interface ToolArgumentNormalizerInterface
{
    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<int|string, mixed>
     */
    public function normalizeArguments(array $arguments): array;
}
