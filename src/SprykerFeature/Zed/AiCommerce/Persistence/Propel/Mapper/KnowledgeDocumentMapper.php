<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Orm\Zed\AiCommerce\Persistence\SpyKnowledgeDocument;

class KnowledgeDocumentMapper
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public function mapKnowledgeDocumentTransferToKnowledgeDocumentEntity(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        SpyKnowledgeDocument $knowledgeDocumentEntity,
    ): SpyKnowledgeDocument {
        return $knowledgeDocumentEntity->fromArray($knowledgeDocumentTransfer->modifiedToArray());
    }

    public function mapKnowledgeDocumentEntityToKnowledgeDocumentTransfer(
        SpyKnowledgeDocument $knowledgeDocumentEntity,
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
    ): KnowledgeDocumentTransfer {
        return $knowledgeDocumentTransfer
            ->fromArray($knowledgeDocumentEntity->toArray(), true)
            ->setIndexedAt($knowledgeDocumentEntity->getIndexedAt(static::DATE_TIME_FORMAT));
    }
}
