<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison;

interface ProductComparisonAttributeCollectorInterface
{
    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, string>
     */
    public function collectAttributes(array $productDetails): array;

    /**
     * @param list<array<string, string>> $attributesList
     *
     * @return list<string>
     */
    public function collectAttributeKeys(array $attributesList): array;
}
