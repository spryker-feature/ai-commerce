<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Builder;

use ArrayObject;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;

interface KnowledgeDocumentIndexResponseBuilderInterface
{
    public function createSuccessResponse(): KnowledgeDocumentIndexResponseTransfer;

    /**
     * @param \ArrayObject<array-key, \Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     */
    public function createFailureResponse(bool $isTemporaryFailure, ArrayObject $errorTransfers): KnowledgeDocumentIndexResponseTransfer;

    public function createErrorResponse(string $message): KnowledgeDocumentIndexResponseTransfer;

    public function createCollectionFailureResponse(
        KnowledgeDocumentCollectionResponseTransfer $knowledgeDocumentCollectionResponseTransfer
    ): KnowledgeDocumentIndexResponseTransfer;
}
