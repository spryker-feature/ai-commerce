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
     * @param array<string, mixed> $data
     */
    public function createDiscount(array $data): string;

    /**
     * @param array<string, mixed> $data
     */
    public function updateDiscount(array $data): string;
}
