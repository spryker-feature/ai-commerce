<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement;

use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class DiscountListReader implements DiscountListReaderInterface
{
    protected const int DISCOUNT_LIST_LIMIT = 50;

    protected const array COLUMN_MAP = [
        'spy_discount.id_discount' => 'idDiscount',
        'spy_discount.display_name' => 'displayName',
        'spy_discount.discount_type' => 'discountType',
        'spy_discount.is_active' => 'isActive',
        'spy_discount.valid_from' => 'validFrom',
        'spy_discount.valid_to' => 'validTo',
        'spy_discount.amount' => 'amount',
        'spy_discount.calculator_plugin' => 'calculatorPlugin',
        'spy_discount.is_exclusive' => 'isExclusive',
        'spy_discount.priority' => 'priority',
    ];

    public function __construct(protected AiCommerceRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function getDiscountList(array $filters): string
    {
        $discounts = $this->repository->findDiscounts($filters, static::DISCOUNT_LIST_LIMIT);

        return (string)json_encode(array_map($this->normalizeRow(...), $discounts), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $column => $value) {
            $key = static::COLUMN_MAP[$column] ?? $column;
            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
