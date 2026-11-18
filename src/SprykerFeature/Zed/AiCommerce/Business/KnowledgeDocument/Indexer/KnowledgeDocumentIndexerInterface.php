<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer;

interface KnowledgeDocumentIndexerInterface
{
    /**
     * @param list<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return list<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function indexDocuments(array $queueReceiveMessageTransfers): array;
}
