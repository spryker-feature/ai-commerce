<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Dependency\KnowledgeSearch;

use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeSearchCriteriaTransfer;

interface KnowledgeSearchPluginInterface
{
    /**
     * Specification:
     * - Searches the knowledge store that `KnowledgeStorePluginInterface` of the Zed layer writes to.
     * - Requires `KnowledgeSearchConditions.queryText` and `Pagination.limit`.
     * - Requires at least one `KnowledgeSearchConditions.documentKeys` or `KnowledgeSearchConditions.ids` value. Never searches the whole knowledge store.
     * - Applies all set conditions with AND.
     * - Returns at most `Pagination.limit` passages ordered by similarity score, descending, with `KnowledgePassage.score` set.
     * - Sets `KnowledgePassageCollection.isSuccessful = true` on success, also when no passage matches.
     * - Returns `isSuccessful = false` with errors when the criteria are invalid or the store fails.
     *
     * @api
     */
    public function searchDocuments(KnowledgeSearchCriteriaTransfer $knowledgeSearchCriteriaTransfer): KnowledgePassageCollectionTransfer;
}
