<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer;

interface KnowledgeDocumentPendingWriterInterface
{
    /**
     * @param list<string> $sourceIdentifiers
     *
     * @return list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer>
     */
    public function writePendingDocuments(string $sourceType, array $sourceIdentifiers, bool $isContentHashCleared): array;
}
