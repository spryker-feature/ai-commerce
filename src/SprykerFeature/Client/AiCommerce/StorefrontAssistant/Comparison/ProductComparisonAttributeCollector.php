<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Comparison;

use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantProductMapper;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\ProductDetails\ProductAttributeDetailsExpander;

class ProductComparisonAttributeCollector implements ProductComparisonAttributeCollectorInterface
{
    protected const string SUPER_ATTRIBUTE_VALUE_SEPARATOR = ' / ';

    /**
     * @param array<string, mixed> $productDetails
     *
     * @return array<string, string>
     */
    public function collectAttributes(array $productDetails): array
    {
        $attributes = $productDetails[StorefrontAssistantProductMapper::KEY_ATTRIBUTES] ?? [];
        $attributes = is_array($attributes) ? array_map('strval', $attributes) : [];
        $superAttributes = $productDetails[ProductAttributeDetailsExpander::KEY_VARIANTS][ProductAttributeDetailsExpander::KEY_SUPER_ATTRIBUTES] ?? [];

        if (!is_array($superAttributes)) {
            return $attributes;
        }

        foreach ($superAttributes as $attributeKey => $attributeValues) {
            if (!isset($attributes[$attributeKey]) && is_array($attributeValues) && $attributeValues !== []) {
                $attributes[(string)$attributeKey] = implode(static::SUPER_ATTRIBUTE_VALUE_SEPARATOR, $attributeValues);
            }
        }

        return $attributes;
    }

    /**
     * @param list<array<string, string>> $attributesList
     *
     * @return list<string>
     */
    public function collectAttributeKeys(array $attributesList): array
    {
        $attributeKeys = [];

        foreach ($attributesList as $attributes) {
            foreach (array_keys($attributes) as $attributeKey) {
                $attributeKeys[$attributeKey] = $attributeKey;
            }
        }

        return array_values($attributeKeys);
    }
}
