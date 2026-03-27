<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement;

interface DiscountListReaderInterface
{
    /**
     * Specification:
     * - Returns a JSON-encoded list of discounts, optionally filtered by display name.
     * - Returns up to 50 discounts ordered by ID descending.
     */
    public function getDiscountList(?string $searchTerm): string;
}
