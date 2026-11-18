<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer;

use ArrayObject;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Sender\KnowledgeDocumentIndexMessageSenderInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface;

class KnowledgeDocumentIndexFailureWriter implements KnowledgeDocumentIndexFailureWriterInterface
{
    use LoggerTrait;

    protected const string STATUS_PENDING = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PENDING;

    protected const string STATUS_FAILED = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED;

    protected const int ERROR_MESSAGE_MAX_LENGTH = 1024;

    protected const string ERROR_MESSAGE_SEPARATOR = '; ';

    protected const string LOG_MESSAGE_INDEX_FAILURE = 'Knowledge document indexing failed for source type "%s", source identifier "%s": %s';

    protected const string LOG_CONTEXT_SOURCE_TYPE = 'sourceType';

    protected const string LOG_CONTEXT_SOURCE_IDENTIFIER = 'sourceIdentifier';

    protected const string LOG_CONTEXT_ATTEMPT_COUNT = 'attemptCount';

    protected const string LOG_CONTEXT_IS_TEMPORARY_FAILURE = 'isTemporaryFailure';

    public function __construct(
        protected KnowledgeDocumentIndexMessageSenderInterface $knowledgeDocumentIndexMessageSender,
        protected AiCommerceEntityManagerInterface $aiCommerceEntityManager,
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function writeIndexFailure(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        KnowledgeDocumentIndexResponseTransfer $knowledgeDocumentIndexResponseTransfer,
    ): void {
        $errorMessage = $this->getErrorMessage($knowledgeDocumentIndexResponseTransfer->getErrors());
        $attemptCount = (int)$knowledgeDocumentTransfer->getAttemptCount() + 1;
        $isRetried = $knowledgeDocumentIndexResponseTransfer->getIsTemporaryFailure()
            && $attemptCount < $this->aiCommerceConfig->getKnowledgeDocumentIndexMaxAttempts();

        $this->getLogger()->error(
            sprintf(static::LOG_MESSAGE_INDEX_FAILURE, $knowledgeDocumentTransfer->getSourceTypeOrFail(), $knowledgeDocumentTransfer->getSourceIdentifierOrFail(), $errorMessage),
            [
                static::LOG_CONTEXT_SOURCE_TYPE => $knowledgeDocumentTransfer->getSourceTypeOrFail(),
                static::LOG_CONTEXT_SOURCE_IDENTIFIER => $knowledgeDocumentTransfer->getSourceIdentifierOrFail(),
                static::LOG_CONTEXT_ATTEMPT_COUNT => $attemptCount,
                static::LOG_CONTEXT_IS_TEMPORARY_FAILURE => (bool)$knowledgeDocumentIndexResponseTransfer->getIsTemporaryFailure(),
            ],
        );

        $knowledgeDocumentTransfer
            ->setStatus($isRetried ? static::STATUS_PENDING : static::STATUS_FAILED)
            ->setAttemptCount($attemptCount)
            ->setErrorMessage($errorMessage)
            ->setContentHash($isRetried ? $knowledgeDocumentTransfer->getContentHash() : null);

        $this->aiCommerceEntityManager->saveKnowledgeDocument($knowledgeDocumentTransfer);

        if ($isRetried) {
            $this->knowledgeDocumentIndexMessageSender->sendIndexMessages([$knowledgeDocumentTransfer]);
        }
    }

    /**
     * @param \ArrayObject<array-key, \Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     */
    protected function getErrorMessage(ArrayObject $errorTransfers): string
    {
        $messages = [];

        foreach ($errorTransfers as $errorTransfer) {
            $messages[] = (string)$errorTransfer->getMessage();
        }

        return mb_substr(implode(static::ERROR_MESSAGE_SEPARATOR, $messages), 0, static::ERROR_MESSAGE_MAX_LENGTH);
    }
}
