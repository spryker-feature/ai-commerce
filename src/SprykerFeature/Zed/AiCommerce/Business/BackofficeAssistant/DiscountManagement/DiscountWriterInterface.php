<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement;

interface DiscountWriterInterface
{
    /**
     * Specification:
     * - Creates a new discount from the provided data array.
     * - Returns JSON with 'success' (bool), 'idDiscount' (int, on success), and 'errors' (string[]).
     *
     * @param array<string, mixed> $data
     */
    public function createDiscount(array $data): string;

    /**
     * Specification:
     * - Updates an existing discount identified by $idDiscount with the provided fields.
     * - Returns early with error JSON if the discount does not exist.
     * - Returns JSON with 'success' (bool) and 'errors' (string[]).
     *
     * @param array<string, mixed> $data
     */
    public function updateDiscount(int $idDiscount, array $data): string;

    /**
     * Specification:
     * - Activates or deactivates a discount by ID.
     * - Returns JSON with 'success' (bool).
     */
    public function toggleDiscountVisibility(int $idDiscount, bool $isActive): string;
}
