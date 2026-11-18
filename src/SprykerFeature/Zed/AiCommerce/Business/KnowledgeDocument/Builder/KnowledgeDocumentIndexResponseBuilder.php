<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Builder;

use ArrayObject;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCollectionResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;

class KnowledgeDocumentIndexResponseBuilder implements KnowledgeDocumentIndexResponseBuilderInterface
{
    public function createSuccessResponse(): KnowledgeDocumentIndexResponseTransfer
    {
        return (new KnowledgeDocumentIndexResponseTransfer())->setIsSuccessful(true);
    }

    /**
     * @param \ArrayObject<array-key, \Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     */
    public function createFailureResponse(bool $isTemporaryFailure, ArrayObject $errorTransfers): KnowledgeDocumentIndexResponseTransfer
    {
        return (new KnowledgeDocumentIndexResponseTransfer())
            ->setIsSuccessful(false)
            ->setIsTemporaryFailure($isTemporaryFailure)
            ->setErrors($errorTransfers);
    }

    public function createErrorResponse(string $message): KnowledgeDocumentIndexResponseTransfer
    {
        return $this->createFailureResponse(false, new ArrayObject([(new ErrorTransfer())->setMessage($message)]));
    }

    public function createCollectionFailureResponse(
        KnowledgeDocumentCollectionResponseTransfer $knowledgeDocumentCollectionResponseTransfer
    ): KnowledgeDocumentIndexResponseTransfer {
        return $this->createFailureResponse(
            $knowledgeDocumentCollectionResponseTransfer->getIsTemporaryFailure() !== false,
            $knowledgeDocumentCollectionResponseTransfer->getErrors(),
        );
    }
}
