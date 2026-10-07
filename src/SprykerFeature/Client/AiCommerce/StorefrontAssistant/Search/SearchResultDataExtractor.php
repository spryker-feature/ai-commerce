<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

class SearchResultDataExtractor implements SearchResultDataExtractorInterface
{
    /**
     * @param mixed $searchResultEntry
     *
     * @return array<string, mixed>|null
     */
    public function extractData($searchResultEntry): ?array
    {
        if (is_array($searchResultEntry)) {
            return $searchResultEntry;
        }

        if (is_object($searchResultEntry) && method_exists($searchResultEntry, 'toArray')) {
            $data = $searchResultEntry->toArray();

            return is_array($data) ? $data : null;
        }

        return null;
    }
}
