<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

interface SuggestionResultFormatterInterface
{
    /**
     * @param array<string, mixed> $suggestionsByType
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function formatSuggestionsByType(array $suggestionsByType, int $limitPerType): array;
}
