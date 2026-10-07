<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Sanitizer;

use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;

class StorefrontAssistantPageContextSanitizer implements StorefrontAssistantPageContextSanitizerInterface
{
    public const string KEY_PAGE_TYPE = 'pageType';

    public const string KEY_PRODUCT_NAME = 'productName';

    public const string KEY_PRODUCT_SKU = 'productSku';

    public const string KEY_CATEGORY_NAME = 'categoryName';

    public const string KEY_SEARCH_QUERY = 'searchQuery';

    public const string KEY_FILTERS = 'filters';

    protected const int MAX_VALUE_LENGTH = 120;

    protected const int MAX_FILTER_COUNT = 10;

    /**
     * @return array<string, mixed>|null
     */
    public function sanitizePageContext(mixed $pageContext): ?array
    {
        if (!is_array($pageContext)) {
            return null;
        }

        $storefrontAssistantPageType = StorefrontAssistantPageType::tryFrom(
            $this->extractString($pageContext, static::KEY_PAGE_TYPE),
        );

        if ($storefrontAssistantPageType === null || !in_array($storefrontAssistantPageType, StorefrontAssistantPageType::reportableCases(), true)) {
            return null;
        }

        return [
            static::KEY_PAGE_TYPE => $storefrontAssistantPageType->value,
            static::KEY_PRODUCT_NAME => $this->extractString($pageContext, static::KEY_PRODUCT_NAME),
            static::KEY_PRODUCT_SKU => $this->extractString($pageContext, static::KEY_PRODUCT_SKU),
            static::KEY_CATEGORY_NAME => $this->extractString($pageContext, static::KEY_CATEGORY_NAME),
            static::KEY_SEARCH_QUERY => $this->extractString($pageContext, static::KEY_SEARCH_QUERY),
            static::KEY_FILTERS => $this->extractFilters($pageContext),
        ];
    }

    /**
     * @param array<mixed> $pageContext
     */
    protected function extractString(array $pageContext, string $key): string
    {
        $value = $pageContext[$key] ?? null;

        if (!is_scalar($value)) {
            return '';
        }

        return $this->truncate((string)$value);
    }

    /**
     * @param array<mixed> $pageContext
     *
     * @return list<string>
     */
    protected function extractFilters(array $pageContext): array
    {
        $filters = $pageContext[static::KEY_FILTERS] ?? null;

        if (!is_array($filters)) {
            return [];
        }

        $sanitizedFilters = [];

        foreach ($filters as $filter) {
            if (!is_scalar($filter)) {
                continue;
            }

            $sanitizedFilter = $this->truncate((string)$filter);

            if ($sanitizedFilter !== '' && !in_array($sanitizedFilter, $sanitizedFilters, true)) {
                $sanitizedFilters[] = $sanitizedFilter;
            }

            if (count($sanitizedFilters) === static::MAX_FILTER_COUNT) {
                break;
            }
        }

        return $sanitizedFilters;
    }

    protected function truncate(string $value): string
    {
        return mb_substr(trim($value), 0, static::MAX_VALUE_LENGTH);
    }
}
