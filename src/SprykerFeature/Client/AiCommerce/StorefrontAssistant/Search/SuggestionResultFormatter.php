<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Search;

class SuggestionResultFormatter implements SuggestionResultFormatterInterface
{
    public const string TYPE_PRODUCT_ABSTRACT = 'product_abstract';

    public const string TYPE_CATEGORY = 'category';

    public const string TYPE_CMS_PAGE = 'cms_page';

    public const string TYPE_PRODUCT_SET = 'product_set';

    protected const string SOURCE_KEY_NAME = 'name';

    protected const string SOURCE_KEY_URL = 'url';

    protected const string SOURCE_KEY_ABSTRACT_NAME = 'abstract_name';

    protected const string SOURCE_KEY_ABSTRACT_SKU = 'abstract_sku';

    protected const string SOURCE_KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string SOURCE_KEY_ID_CATEGORY = 'id_category';

    protected const string SOURCE_KEY_ID_CMS_PAGE = 'id_cms_page';

    protected const string SOURCE_KEY_ID_PRODUCT_SET = 'id_product_set';

    protected const string SOURCE_KEY_META_TITLE = 'meta_title';

    protected const string RESULT_KEY_NAME = 'name';

    protected const string RESULT_KEY_URL = 'url';

    protected const string RESULT_KEY_SKU = 'sku';

    protected const string RESULT_KEY_ID = 'id';

    protected const string PROJECTION_KEY_ID = 'id';

    protected const string PROJECTION_KEY_NAMES = 'names';

    protected const string PROJECTION_KEY_EXTRA = 'extra';

    /**
     * @var array<string, array<string, mixed>>
     */
    protected const array PROJECTION_BY_TYPE = [
        self::TYPE_PRODUCT_ABSTRACT => [
            self::PROJECTION_KEY_ID => self::SOURCE_KEY_ID_PRODUCT_ABSTRACT,
            self::PROJECTION_KEY_NAMES => [self::SOURCE_KEY_ABSTRACT_NAME],
            self::PROJECTION_KEY_EXTRA => [self::RESULT_KEY_SKU => self::SOURCE_KEY_ABSTRACT_SKU],
        ],
        self::TYPE_CATEGORY => [
            self::PROJECTION_KEY_ID => self::SOURCE_KEY_ID_CATEGORY,
            self::PROJECTION_KEY_NAMES => [self::SOURCE_KEY_NAME],
            self::PROJECTION_KEY_EXTRA => [],
        ],
        self::TYPE_CMS_PAGE => [
            self::PROJECTION_KEY_ID => self::SOURCE_KEY_ID_CMS_PAGE,
            self::PROJECTION_KEY_NAMES => [self::SOURCE_KEY_NAME],
            self::PROJECTION_KEY_EXTRA => [],
        ],
        self::TYPE_PRODUCT_SET => [
            self::PROJECTION_KEY_ID => self::SOURCE_KEY_ID_PRODUCT_SET,
            self::PROJECTION_KEY_NAMES => [self::SOURCE_KEY_META_TITLE, self::SOURCE_KEY_NAME],
            self::PROJECTION_KEY_EXTRA => [],
        ],
    ];

    /**
     * @param array<string, mixed> $suggestionsByType
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function formatSuggestionsByType(array $suggestionsByType, int $limitPerType): array
    {
        $formattedSuggestions = [];

        foreach (static::PROJECTION_BY_TYPE as $type => $projection) {
            $rows = $suggestionsByType[$type] ?? null;

            if (!is_array($rows)) {
                continue;
            }

            if ($rows === []) {
                continue;
            }

            $formattedRows = $this->formatRows($rows, $projection, $limitPerType);

            if ($formattedRows !== []) {
                $formattedSuggestions[$type] = $formattedRows;
            }
        }

        return $formattedSuggestions;
    }

    /**
     * @param array<int|string, mixed> $rows
     * @param array<string, mixed> $projection
     *
     * @return array<int, array<string, mixed>>
     */
    protected function formatRows(array $rows, array $projection, int $limitPerType): array
    {
        $formattedRows = [];

        foreach (array_slice(array_values($rows), 0, $limitPerType) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $formattedRow = $this->formatRow($row, $projection);

            if ($formattedRow !== []) {
                $formattedRows[] = $formattedRow;
            }
        }

        return $formattedRows;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $projection
     *
     * @return array<string, mixed>
     */
    protected function formatRow(array $row, array $projection): array
    {
        $name = $this->resolveName($row, $projection[static::PROJECTION_KEY_NAMES]);

        if ($name === null) {
            return [];
        }

        $formattedRow = [
            static::RESULT_KEY_ID => $this->resolveInt($row, $projection[static::PROJECTION_KEY_ID]),
            static::RESULT_KEY_NAME => $name,
            static::RESULT_KEY_URL => $this->resolveString($row, static::SOURCE_KEY_URL),
        ];

        foreach ($projection[static::PROJECTION_KEY_EXTRA] as $resultKey => $sourceKey) {
            $formattedRow[$resultKey] = $this->resolveString($row, $sourceKey);
        }

        return array_filter($formattedRow, static fn ($value): bool => $value !== null);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $sourceKeys
     */
    protected function resolveName(array $row, array $sourceKeys): ?string
    {
        foreach ($sourceKeys as $sourceKey) {
            $name = $this->resolveString($row, $sourceKey);

            if ($name !== null) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function resolveString(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function resolveInt(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;

        return is_numeric($value) ? (int)$value : null;
    }
}
