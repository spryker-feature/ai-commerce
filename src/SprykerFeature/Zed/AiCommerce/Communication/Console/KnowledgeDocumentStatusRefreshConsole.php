<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class KnowledgeDocumentStatusRefreshConsole extends Console
{
    public const string COMMAND_NAME = 'knowledge-document:status:refresh';

    protected const string COMMAND_DESCRIPTION = 'Reads the knowledge store status of the knowledge documents that the store indexes later, and saves indexed or failed.';

    protected const string MESSAGE_RESULT = 'Checked %d document(s): %d indexed, %d failed.';

    protected function configure(): void
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $knowledgeDocumentStatusRefreshResponseTransfer = $this->getBusinessFactory()
            ->createKnowledgeDocumentStatusRefresher()
            ->refreshStatuses();

        foreach ($knowledgeDocumentStatusRefreshResponseTransfer->getErrors() as $errorTransfer) {
            $this->error((string)$errorTransfer->getMessage());
        }

        $this->info(sprintf(
            static::MESSAGE_RESULT,
            (int)$knowledgeDocumentStatusRefreshResponseTransfer->getCheckedDocumentCount(),
            (int)$knowledgeDocumentStatusRefreshResponseTransfer->getIndexedDocumentCount(),
            (int)$knowledgeDocumentStatusRefreshResponseTransfer->getFailedDocumentCount(),
        ));

        return $knowledgeDocumentStatusRefreshResponseTransfer->getIsSuccessful() ? static::CODE_SUCCESS : static::CODE_ERROR;
    }
}
