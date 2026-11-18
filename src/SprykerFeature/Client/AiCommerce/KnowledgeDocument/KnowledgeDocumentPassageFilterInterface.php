<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;

interface KnowledgeDocumentPassageFilterInterface
{
    /**
     * @param array<string> $documentKeys
     */
    public function filterKnowledgePassages(
        KnowledgePassageCollectionTransfer $knowledgePassageCollectionTransfer,
        array $documentKeys,
        int $limit
    ): KnowledgePassageCollectionTransfer;
}
