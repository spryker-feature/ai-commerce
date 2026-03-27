<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\DiscountManagement;

interface DiscountDetailsReaderInterface
{
    /**
     * Specification:
     * - Returns a JSON-encoded full discount configuration for the given discount ID.
     * - Returns empty JSON object '{}' when the discount is not found.
     */
    public function getDiscountDetails(int $idDiscount): string;
}
