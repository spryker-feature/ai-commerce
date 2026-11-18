<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Mapper;

use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;

interface KnowledgeDocumentIndexMessageMapperInterface
{
    public function mapKnowledgeDocumentTransferToQueueSendMessageTransfer(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        QueueSendMessageTransfer $queueSendMessageTransfer,
    ): QueueSendMessageTransfer;

    public function mapQueueReceiveMessageTransferToKnowledgeDocumentTransfer(
        QueueReceiveMessageTransfer $queueReceiveMessageTransfer,
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
    ): KnowledgeDocumentTransfer;
}
