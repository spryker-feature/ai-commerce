<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Sender;

use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Client\Queue\QueueClientInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Mapper\KnowledgeDocumentIndexMessageMapperInterface;

class KnowledgeDocumentIndexMessageSender implements KnowledgeDocumentIndexMessageSenderInterface
{
    public function __construct(
        protected QueueClientInterface $queueClient,
        protected KnowledgeDocumentIndexMessageMapperInterface $knowledgeDocumentIndexMessageMapper,
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     */
    public function sendIndexMessages(array $knowledgeDocumentTransfers): int
    {
        $queueSendMessageTransfers = [];

        foreach ($knowledgeDocumentTransfers as $knowledgeDocumentTransfer) {
            $documentKey = sprintf(
                SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT,
                $knowledgeDocumentTransfer->getSourceTypeOrFail(),
                $knowledgeDocumentTransfer->getSourceIdentifierOrFail(),
            );

            $queueSendMessageTransfers[$documentKey] = $this->knowledgeDocumentIndexMessageMapper
                ->mapKnowledgeDocumentTransferToQueueSendMessageTransfer(
                    $knowledgeDocumentTransfer,
                    new QueueSendMessageTransfer(),
                );
        }

        if ($queueSendMessageTransfers === []) {
            return 0;
        }

        $this->queueClient->sendMessages(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_QUEUE,
            array_values($queueSendMessageTransfers),
        );

        return count($queueSendMessageTransfers);
    }
}
