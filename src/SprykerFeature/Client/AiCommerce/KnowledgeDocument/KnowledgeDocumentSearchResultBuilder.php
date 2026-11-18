<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgeDocumentSearchResultTransfer;
use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;
use Generated\Shared\Transfer\KnowledgePassageTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class KnowledgeDocumentSearchResultBuilder implements KnowledgeDocumentSearchResultBuilderInterface
{
    /**
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentSearchResultTransfer>
     */
    public function buildSearchResults(
        KnowledgePassageCollectionTransfer $knowledgePassageCollectionTransfer,
        int $maxCharacters
    ): array {
        $knowledgeDocumentSearchResultTransfers = [];
        $remainingCharacters = $maxCharacters;

        foreach ($knowledgePassageCollectionTransfer->getKnowledgePassages() as $knowledgePassageTransfer) {
            if ($remainingCharacters <= 0) {
                break;
            }

            $content = mb_substr((string)$knowledgePassageTransfer->getContent(), 0, $remainingCharacters);
            $remainingCharacters -= mb_strlen($content);
            $knowledgeDocumentSearchResultTransfers[] = $this->createSearchResult($knowledgePassageTransfer, $content);
        }

        return $knowledgeDocumentSearchResultTransfers;
    }

    protected function createSearchResult(
        KnowledgePassageTransfer $knowledgePassageTransfer,
        string $content
    ): KnowledgeDocumentSearchResultTransfer {
        return (new KnowledgeDocumentSearchResultTransfer())
            ->setSourceType($knowledgePassageTransfer->getSourceType())
            ->setSourceIdentifier($this->extractSourceIdentifier($knowledgePassageTransfer))
            ->setDocumentName($knowledgePassageTransfer->getDocumentName())
            ->setContent($content)
            ->setScore($knowledgePassageTransfer->getScore())
            ->setMetadata($knowledgePassageTransfer->getMetadata());
    }

    protected function extractSourceIdentifier(KnowledgePassageTransfer $knowledgePassageTransfer): string
    {
        $documentKey = (string)$knowledgePassageTransfer->getDocumentKey();
        $documentKeyPrefix = sprintf(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT, $knowledgePassageTransfer->getSourceType(), '');

        if (!str_starts_with($documentKey, $documentKeyPrefix)) {
            return $documentKey;
        }

        return substr($documentKey, strlen($documentKeyPrefix));
    }
}
