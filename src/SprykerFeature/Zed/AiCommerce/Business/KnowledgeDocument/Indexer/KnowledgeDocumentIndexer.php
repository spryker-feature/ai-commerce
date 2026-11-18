<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Indexer;

use Exception;
use Generated\Shared\Transfer\KnowledgeDocumentConditionsTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentCriteriaTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Deleter\KnowledgeDocumentDeleterInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Mapper\KnowledgeDocumentIndexMessageMapperInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader\KnowledgeDocumentSourceReaderInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\KnowledgeDocumentSourcePluginResolverInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Validator\KnowledgeDocumentIndexMessageValidatorInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer\KnowledgeDocumentIndexFailureWriterInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;
use SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface;

class KnowledgeDocumentIndexer implements KnowledgeDocumentIndexerInterface
{
    use LoggerTrait;

    public const string STATUS_PENDING = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PENDING;

    public const string STATUS_PROCESSING = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_PROCESSING;

    public const string STATUS_INDEXED = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_INDEXED;

    public const string STATUS_FAILED = SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_STATUS_FAILED;

    /**
     * @uses \Spryker\Shared\Event\EventConfig::EVENT_ROUTING_KEY_ERROR
     */
    protected const string QUEUE_ROUTING_KEY_ERROR = 'error';

    protected const string LOG_MESSAGE_INVALID_MESSAGE = 'Knowledge document index message is invalid: %s';

    protected const string LOG_MESSAGE_KNOWLEDGE_STORE_NOT_CONFIGURED = 'The knowledge store is not configured for the source type "%s". The knowledge document index message is acknowledged without indexing.';

    protected const string LOG_CONTEXT_MESSAGE_BODY = 'messageBody';

    protected const string LOG_CONTEXT_EXCEPTION = 'exception';

    protected const string ERROR_MESSAGE_MALFORMED_MESSAGE = 'The message body has no source type or source identifier.';

    protected const string ERROR_MESSAGE_UNKNOWN_SOURCE_TYPE = 'No document source plugin is registered for the source type "%s".';

    protected const string ERROR_MESSAGE_UNEXPECTED_EXCEPTION = 'The indexing of the message failed with an exception: %s';

    public function __construct(
        protected KnowledgeDocumentIndexMessageValidatorInterface $knowledgeDocumentIndexMessageValidator,
        protected KnowledgeDocumentIndexMessageMapperInterface $knowledgeDocumentIndexMessageMapper,
        protected KnowledgeDocumentSourcePluginResolverInterface $knowledgeDocumentSourcePluginResolver,
        protected KnowledgeDocumentSourceReaderInterface $knowledgeDocumentSourceReader,
        protected KnowledgeDocumentContentIndexerInterface $knowledgeDocumentContentIndexer,
        protected KnowledgeDocumentDeleterInterface $knowledgeDocumentDeleter,
        protected KnowledgeDocumentIndexFailureWriterInterface $knowledgeDocumentIndexFailureWriter,
        protected AiCommerceRepositoryInterface $aiCommerceRepository,
        protected KnowledgeStorePluginInterface $knowledgeStorePlugin,
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return list<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function indexDocuments(array $queueReceiveMessageTransfers): array
    {
        $processedDocumentKeys = [];

        foreach ($queueReceiveMessageTransfers as $queueReceiveMessageTransfer) {
            if (!$this->knowledgeDocumentIndexMessageValidator->isValid($queueReceiveMessageTransfer)) {
                $this->markMessageAsInvalid($queueReceiveMessageTransfer, static::ERROR_MESSAGE_MALFORMED_MESSAGE);

                continue;
            }

            $knowledgeDocumentTransfer = $this->knowledgeDocumentIndexMessageMapper->mapQueueReceiveMessageTransferToKnowledgeDocumentTransfer(
                $queueReceiveMessageTransfer,
                new KnowledgeDocumentTransfer(),
            );

            $documentKey = $this->getDocumentKey($knowledgeDocumentTransfer->getSourceTypeOrFail(), $knowledgeDocumentTransfer->getSourceIdentifierOrFail());

            if (isset($processedDocumentKeys[$documentKey])) {
                $queueReceiveMessageTransfer->setAcknowledge(true);

                continue;
            }

            $processedDocumentKeys[$documentKey] = true;
            $this->indexDocumentMessageInIsolation($queueReceiveMessageTransfer, $knowledgeDocumentTransfer);
        }

        return $queueReceiveMessageTransfers;
    }

    protected function indexDocumentMessageInIsolation(
        QueueReceiveMessageTransfer $queueReceiveMessageTransfer,
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
    ): void {
        try {
            $this->indexDocumentMessage($queueReceiveMessageTransfer, $knowledgeDocumentTransfer);
        } catch (Exception $exception) {
            $this->markMessageAsInvalid(
                $queueReceiveMessageTransfer,
                sprintf(static::ERROR_MESSAGE_UNEXPECTED_EXCEPTION, $exception->getMessage()),
                $exception,
            );
        }
    }

    protected function indexDocumentMessage(
        QueueReceiveMessageTransfer $queueReceiveMessageTransfer,
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
    ): void {
        $knowledgeDocumentSourcePlugin = $this->knowledgeDocumentSourcePluginResolver->findSourcePlugin(
            $knowledgeDocumentTransfer->getSourceTypeOrFail(),
        );

        if ($knowledgeDocumentSourcePlugin === null) {
            $this->markMessageAsInvalid(
                $queueReceiveMessageTransfer,
                sprintf(static::ERROR_MESSAGE_UNKNOWN_SOURCE_TYPE, $knowledgeDocumentTransfer->getSourceTypeOrFail()),
            );

            return;
        }

        if (!$this->knowledgeStorePlugin->isConfigured($knowledgeDocumentTransfer->getSourceTypeOrFail())) {
            $this->skipMessageOfUnconfiguredKnowledgeStore($queueReceiveMessageTransfer, $knowledgeDocumentTransfer->getSourceTypeOrFail());

            return;
        }

        $knowledgeDocumentTransfer = $this->findKnowledgeDocument($knowledgeDocumentTransfer)
            ?? $knowledgeDocumentTransfer->setStatus(static::STATUS_PENDING)->setAttemptCount(0);

        $knowledgeDocumentIndexResponseTransfer = $this->indexDocument($knowledgeDocumentTransfer, $knowledgeDocumentSourcePlugin);
        $queueReceiveMessageTransfer->setAcknowledge(true);

        if ($knowledgeDocumentIndexResponseTransfer->getIsSuccessful()) {
            return;
        }

        $this->knowledgeDocumentIndexFailureWriter->writeIndexFailure($knowledgeDocumentTransfer, $knowledgeDocumentIndexResponseTransfer);
    }

    protected function indexDocument(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer,
        KnowledgeDocumentSourcePluginInterface $knowledgeDocumentSourcePlugin,
    ): KnowledgeDocumentIndexResponseTransfer {
        $knowledgeDocumentSourceTransfer = $this->knowledgeDocumentSourceReader->findDocumentSource($knowledgeDocumentTransfer, $knowledgeDocumentSourcePlugin);

        if ($knowledgeDocumentSourceTransfer === null) {
            return $this->knowledgeDocumentDeleter->deleteDocument($knowledgeDocumentTransfer);
        }

        return $this->knowledgeDocumentContentIndexer->indexDocumentContent(
            $knowledgeDocumentTransfer,
            $knowledgeDocumentSourceTransfer,
            $knowledgeDocumentSourcePlugin,
        );
    }

    protected function skipMessageOfUnconfiguredKnowledgeStore(
        QueueReceiveMessageTransfer $queueReceiveMessageTransfer,
        string $sourceType,
    ): void {
        $this->getLogger()->warning(
            sprintf(static::LOG_MESSAGE_KNOWLEDGE_STORE_NOT_CONFIGURED, $sourceType),
            [static::LOG_CONTEXT_MESSAGE_BODY => $queueReceiveMessageTransfer->getQueueMessage()?->getBody()],
        );

        $queueReceiveMessageTransfer->setAcknowledge(true);
    }

    protected function markMessageAsInvalid(
        QueueReceiveMessageTransfer $queueReceiveMessageTransfer,
        string $errorMessage,
        ?Exception $exception = null,
    ): void {
        $this->getLogger()->error(
            sprintf(static::LOG_MESSAGE_INVALID_MESSAGE, $errorMessage),
            [
                static::LOG_CONTEXT_MESSAGE_BODY => $queueReceiveMessageTransfer->getQueueMessage()?->getBody(),
                static::LOG_CONTEXT_EXCEPTION => $exception,
            ],
        );

        $queueReceiveMessageTransfer
            ->setAcknowledge(false)
            ->setReject(true)
            ->setHasError(true)
            ->setRoutingKey(static::QUEUE_ROUTING_KEY_ERROR);
    }

    protected function findKnowledgeDocument(
        KnowledgeDocumentTransfer $knowledgeDocumentTransfer
    ): ?KnowledgeDocumentTransfer {
        $knowledgeDocumentCriteriaTransfer = (new KnowledgeDocumentCriteriaTransfer())
            ->setKnowledgeDocumentConditions(
                (new KnowledgeDocumentConditionsTransfer())
                    ->addSourceType($knowledgeDocumentTransfer->getSourceTypeOrFail())
                    ->addSourceIdentifier($knowledgeDocumentTransfer->getSourceIdentifierOrFail()),
            );

        $knowledgeDocumentTransfers = $this->aiCommerceRepository
            ->getKnowledgeDocumentCollection($knowledgeDocumentCriteriaTransfer)
            ->getKnowledgeDocuments();

        return $knowledgeDocumentTransfers->count() > 0 ? $knowledgeDocumentTransfers->offsetGet(0) : null;
    }

    protected function getDocumentKey(string $sourceType, string $sourceIdentifier): string
    {
        return sprintf(SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT, $sourceType, $sourceIdentifier);
    }
}
