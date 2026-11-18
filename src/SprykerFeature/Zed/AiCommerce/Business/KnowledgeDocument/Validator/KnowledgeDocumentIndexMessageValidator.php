<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Validator;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Mapper\KnowledgeDocumentIndexMessageMapper;

class KnowledgeDocumentIndexMessageValidator implements KnowledgeDocumentIndexMessageValidatorInterface
{
    public function __construct(protected UtilEncodingServiceInterface $utilEncodingService)
    {
    }

    public function isValid(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): bool
    {
        $body = $queueReceiveMessageTransfer->getQueueMessage()?->getBody();

        if ($body === null || $body === '') {
            return false;
        }

        $messageData = $this->utilEncodingService->decodeJson($body, true);

        if (!is_array($messageData)) {
            return false;
        }

        return $this->isNonEmptyString($messageData[KnowledgeDocumentIndexMessageMapper::MESSAGE_KEY_SOURCE_TYPE] ?? null)
            && $this->isNonEmptyString($messageData[KnowledgeDocumentIndexMessageMapper::MESSAGE_KEY_SOURCE_IDENTIFIER] ?? null);
    }

    protected function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }
}
