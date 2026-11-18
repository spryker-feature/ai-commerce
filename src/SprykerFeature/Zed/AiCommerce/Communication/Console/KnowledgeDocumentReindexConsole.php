<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Console;

use Generated\Shared\Transfer\KnowledgeDocumentReindexRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentReindexResponseTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class KnowledgeDocumentReindexConsole extends Console
{
    public const string COMMAND_NAME = 'knowledge-document:reindex';

    public const string OPTION_SOURCE_TYPE = 'source-type';

    public const string OPTION_FORCE = 'force';

    protected const string COMMAND_DESCRIPTION = 'Queues knowledge documents for indexing in the knowledge store.';

    protected const string OPTION_SOURCE_TYPE_DESCRIPTION = 'Source type to re-index, for example product_attachment. Repeat the option for several source types. All source types when omitted.';

    protected const string OPTION_FORCE_DESCRIPTION = 'Clears the content hash so that every document is indexed again.';

    protected const string MESSAGE_QUEUED_DOCUMENTS = 'Queued %d knowledge document(s) for indexing.';

    protected const string MESSAGE_QUEUED_DELETED_SOURCE_DOCUMENTS = 'Queued %d knowledge document(s) of deleted sources for deletion.';

    protected const string MESSAGE_QUEUE = 'Queue: %s';

    protected const string MESSAGE_FORCED = 'Content hash cleared (--force): %s';

    protected const string MESSAGE_SOURCE_TYPE_QUEUED_DOCUMENTS = '- %s: %d document(s)';

    protected const string VALUE_YES = 'yes';

    protected const string VALUE_NO = 'no';

    protected function configure(): void
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION)
            ->addOption(static::OPTION_SOURCE_TYPE, null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, static::OPTION_SOURCE_TYPE_DESCRIPTION, [])
            ->addOption(static::OPTION_FORCE, null, InputOption::VALUE_NONE, static::OPTION_FORCE_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $sourceTypes */
        $sourceTypes = (array)$input->getOption(static::OPTION_SOURCE_TYPE);

        $knowledgeDocumentReindexResponseTransfer = $this->getBusinessFactory()
            ->createKnowledgeDocumentReindexer()
            ->reindex(
                (new KnowledgeDocumentReindexRequestTransfer())
                    ->setSourceTypes($sourceTypes)
                    ->setIsForced((bool)$input->getOption(static::OPTION_FORCE)),
            );

        foreach ($knowledgeDocumentReindexResponseTransfer->getErrors() as $errorTransfer) {
            $this->error((string)$errorTransfer->getMessage());
        }

        if (!$knowledgeDocumentReindexResponseTransfer->getIsSuccessful()) {
            return static::CODE_ERROR;
        }

        $this->printDetails($knowledgeDocumentReindexResponseTransfer, (bool)$input->getOption(static::OPTION_FORCE), $output);
        $this->info(sprintf(static::MESSAGE_QUEUED_DOCUMENTS, (int)$knowledgeDocumentReindexResponseTransfer->getQueuedDocumentCount()));
        $this->info(sprintf(static::MESSAGE_QUEUED_DELETED_SOURCE_DOCUMENTS, (int)$knowledgeDocumentReindexResponseTransfer->getQueuedDeletedSourceDocumentCount()));

        return static::CODE_SUCCESS;
    }

    protected function printDetails(
        KnowledgeDocumentReindexResponseTransfer $knowledgeDocumentReindexResponseTransfer,
        bool $isForced,
        OutputInterface $output,
    ): void {
        if (!$output->isVerbose()) {
            return;
        }

        $output->writeln(sprintf(static::MESSAGE_QUEUE, SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_QUEUE));
        $output->writeln(sprintf(static::MESSAGE_FORCED, $isForced ? static::VALUE_YES : static::VALUE_NO));

        foreach ($knowledgeDocumentReindexResponseTransfer->getQueuedDocumentCountsBySourceType() as $sourceType => $queuedDocumentCount) {
            $output->writeln(sprintf(static::MESSAGE_SOURCE_TYPE_QUEUED_DOCUMENTS, $sourceType, $queuedDocumentCount));
        }
    }
}
