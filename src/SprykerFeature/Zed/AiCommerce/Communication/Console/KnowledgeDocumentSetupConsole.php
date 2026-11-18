<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Console;

use Generated\Shared\Transfer\KnowledgeStoreSetupRequestTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class KnowledgeDocumentSetupConsole extends Console
{
    public const string COMMAND_NAME = 'knowledge-document:setup';

    public const string OPTION_RECREATE_INDEX = 'recreate-index';

    protected const string COMMAND_DESCRIPTION = 'Checks and configures the knowledge store of the registered knowledge store plugin, for example the Amazon Bedrock knowledge base.';

    protected const string OPTION_RECREATE_INDEX_DESCRIPTION = 'Deletes the stored documents when the knowledge store supports it. Run the knowledge document re-index with --force after it.';

    protected const string MESSAGE_KNOWLEDGE_STORE = 'Knowledge store: %s';

    protected const string MESSAGE_SKIPPED = 'Skipped.';

    protected const string MESSAGE_STEP = '- %s';

    protected function configure(): void
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION)
            ->addOption(static::OPTION_RECREATE_INDEX, null, InputOption::VALUE_NONE, static::OPTION_RECREATE_INDEX_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $knowledgeStoreSetupResponseTransfer = $this->getBusinessFactory()
            ->createKnowledgeStoreSetup()
            ->setupKnowledgeStore(
                (new KnowledgeStoreSetupRequestTransfer())
                    ->setIsIndexRecreated((bool)$input->getOption(static::OPTION_RECREATE_INDEX)),
            );

        $this->info(sprintf(static::MESSAGE_KNOWLEDGE_STORE, (string)$knowledgeStoreSetupResponseTransfer->getKnowledgeStoreName()));

        if ($output->isVerbose()) {
            foreach ($knowledgeStoreSetupResponseTransfer->getSteps() as $step) {
                $output->writeln(sprintf(static::MESSAGE_STEP, $step));
            }
        }

        foreach ($knowledgeStoreSetupResponseTransfer->getErrors() as $errorTransfer) {
            $this->error((string)$errorTransfer->getMessage());
        }

        if ($knowledgeStoreSetupResponseTransfer->getIsSkipped()) {
            $this->info(static::MESSAGE_SKIPPED);
        }

        foreach ($knowledgeStoreSetupResponseTransfer->getMessages() as $message) {
            $this->info($message);
        }

        return $knowledgeStoreSetupResponseTransfer->getIsSuccessful() ? static::CODE_SUCCESS : static::CODE_ERROR;
    }
}
