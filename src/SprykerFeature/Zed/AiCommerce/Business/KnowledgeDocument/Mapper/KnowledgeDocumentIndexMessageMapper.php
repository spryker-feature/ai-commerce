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
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;

class KnowledgeDocumentIndexMessageMapper implements KnowledgeDocumentIndexMessageMapperInterface
{
    public const string MESSAGE_KEY_SOURCE_TYPE = 'sourceType';

    public const string MESSAGE_KEY_SOURCE_IDENTIFIER = 'sourceIdentifier';

    public function __construct(protected UtilEncodingServiceInterface $utilEncodingService)
    {
    }

    public function mapKnowledgeDocumentTransferToQueueSendMessageTransfer(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        QueueSendMessageTransfer $queueSendMessageTransfer,
    ): QueueSendMessageTransfer {
        $body = $this->utilEncodingService->encodeJson([
            static::MESSAGE_KEY_SOURCE_TYPE => $knowledgeDocumentTransfer->getSourceTypeOrFail(),
            static::MESSAGE_KEY_SOURCE_IDENTIFIER => $knowledgeDocumentTransfer->getSourceIdentifierOrFail(),
        ]);

        return $queueSendMessageTransfer->setBody((string)$body);
    }

    public function mapQueueReceiveMessageTransferToKnowledgeDocumentTransfer(
        QueueReceiveMessageTransfer $queueReceiveMessageTransfer,
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
    ): KnowledgeDocumentTransfer {
        /** @var array<string, string> $messageData */
        $messageData = $this->utilEncodingService->decodeJson((string)$queueReceiveMessageTransfer->getQueueMessageOrFail()->getBody(), true);

        return $knowledgeDocumentTransfer
            ->setSourceType($messageData[static::MESSAGE_KEY_SOURCE_TYPE])
            ->setSourceIdentifier($messageData[static::MESSAGE_KEY_SOURCE_IDENTIFIER]);
    }
}
