<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;

interface KnowledgeDocumentSearchResultBuilderInterface
{
    /**
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentSearchResultTransfer>
     */
    public function buildSearchResults(
        KnowledgePassageCollectionTransfer $knowledgePassageCollectionTransfer,
        int $maxCharacters
    ): array;
}
