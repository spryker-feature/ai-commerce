<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Matcher;

use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class ProductNameMatcher implements ProductNameMatcherInterface
{
    public function __construct(protected AiCommerceConfig $config)
    {
    }

    public function isMatchingName(string $productName, string $queryName): bool
    {
        $sourceWords = $this->splitIntoWords($productName);
        $targetWords = $this->splitIntoWords($queryName);

        if (!$targetWords) {
            return false;
        }

        $uniqueTargetWords = array_unique($targetWords);
        $matchCount = count(array_intersect($uniqueTargetWords, $sourceWords));
        $similarityPercent = ($matchCount / count($uniqueTargetWords)) * 100;

        return $similarityPercent >= $this->config->getQuickOrderImageToCartTextSimilarityThresholdPercent();
    }

    /**
     * @return array<string>
     */
    protected function splitIntoWords(string $text): array
    {
        return preg_split('/\s+/u', strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
