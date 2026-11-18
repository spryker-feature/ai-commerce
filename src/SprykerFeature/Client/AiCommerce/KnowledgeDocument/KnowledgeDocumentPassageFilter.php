<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use ArrayObject;
use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;
use Generated\Shared\Transfer\KnowledgePassageTransfer;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;

class KnowledgeDocumentPassageFilter implements KnowledgeDocumentPassageFilterInterface
{
    protected const string PATTERN_WHITESPACE = '/\s+/u';

    protected const string PASSAGE_KEY_FORMAT = '%s|%s';

    public function __construct(protected AiCommerceConfig $aiCommerceConfig)
    {
    }

    /**
     * @param array<string> $documentKeys
     */
    public function filterKnowledgePassages(
        KnowledgePassageCollectionTransfer $knowledgePassageCollectionTransfer,
        array $documentKeys,
        int $limit
    ): KnowledgePassageCollectionTransfer {
        $minContentLength = $this->aiCommerceConfig->getKnowledgeDocumentMinContentLength();
        $allowedDocumentKeys = array_fill_keys($documentKeys, true);
        $knowledgePassageTransfers = [];
        $passageKeys = [];

        foreach ($knowledgePassageCollectionTransfer->getKnowledgePassages() as $knowledgePassageTransfer) {
            if (count($knowledgePassageTransfers) >= $limit) {
                break;
            }

            if (!isset($allowedDocumentKeys[(string)$knowledgePassageTransfer->getDocumentKey()])) {
                continue;
            }

            $normalizedContent = $this->normalizeContent((string)$knowledgePassageTransfer->getContent());
            $passageKey = $this->createPassageKey($knowledgePassageTransfer, $normalizedContent);

            if (mb_strlen($normalizedContent) < $minContentLength || isset($passageKeys[$passageKey])) {
                continue;
            }

            $passageKeys[$passageKey] = true;
            $knowledgePassageTransfers[] = $knowledgePassageTransfer;
        }

        return (clone $knowledgePassageCollectionTransfer)->setKnowledgePassages(new ArrayObject($knowledgePassageTransfers));
    }

    protected function normalizeContent(string $content): string
    {
        return mb_strtolower(trim((string)preg_replace(static::PATTERN_WHITESPACE, ' ', $content)));
    }

    protected function createPassageKey(KnowledgePassageTransfer $knowledgePassageTransfer, string $normalizedContent): string
    {
        return sprintf(static::PASSAGE_KEY_FORMAT, (string)$knowledgePassageTransfer->getDocumentKey(), $normalizedContent);
    }
}
