<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader;

use Generated\Shared\Transfer\KnowledgeDocumentSourceConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;

class KnowledgeDocumentSourceReader implements KnowledgeDocumentSourceReaderInterface
{
    public function findDocumentSource(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin,
    ): ?KnowledgeDocumentSourceTransfer {
        $knowledgeDocumentSourceCriteriaTransfer = (new KnowledgeDocumentSourceCriteriaTransfer())
            ->setKnowledgeDocumentSourceConditions(
                (new KnowledgeDocumentSourceConditionsTransfer())
                    ->addSourceIdentifier($knowledgeDocumentTransfer->getSourceIdentifierOrFail()),
            );

        $knowledgeDocumentSourceTransfers = $knowledgeDocumentSourcePlugin
            ->getDocumentSourceCollection($knowledgeDocumentSourceCriteriaTransfer)
            ->getKnowledgeDocumentSources();

        foreach ($knowledgeDocumentSourceTransfers as $knowledgeDocumentSourceTransfer) {
            if ($knowledgeDocumentSourceTransfer->getSourceIdentifier() === $knowledgeDocumentTransfer->getSourceIdentifier()) {
                return $knowledgeDocumentSourceTransfer;
            }
        }

        return null;
    }
}
