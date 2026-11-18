<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;

interface KnowledgeDocumentSourcePluginInterface
{
    /**
     * Specification:
     * - Returns the unique source type, for example `product_attachment`.
     *
     * @api
     */
    public function getSourceType(): string;

    /**
     * Specification:
     * - Returns the event names that create, change or delete documents of this source.
     *
     * @api
     *
     * @return list<string>
     */
    public function getSubscribedEvents(): array;

    /**
     * Specification:
     * - Returns the source identifiers affected by the events.
     *
     * @api
     *
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return list<string>
     */
    public function extractSourceIdentifiers(array $eventEntityTransfers, string $eventName): array;

    /**
     * Specification:
     * - Returns the existing sources for `KnowledgeDocumentSourceConditions.sourceIdentifiers`.
     * - Returns one page of all sources, by `KnowledgeDocumentSourceCriteria.pagination`, when no source identifiers are set.
     * - A requested identifier that is missing in the result means that the source was deleted.
     *
     * @api
     */
    public function getDocumentSourceCollection(
        KnowledgeDocumentSourceCriteriaTransfer $knowledgeDocumentSourceCriteriaTransfer
    ): KnowledgeDocumentSourceCollectionTransfer;

    /**
     * Specification:
     * - Reads the raw content and the MIME type of the source.
     * - Returns `isSuccessful = false` with errors on failure. Sets `isTemporaryFailure = true` when a retry can succeed.
     *
     * @api
     */
    public function readDocumentContent(
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer
    ): KnowledgeDocumentContentTransfer;
}
