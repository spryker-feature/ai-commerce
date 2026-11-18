<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Writer;

use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\KnowledgeDocumentSourcePluginResolverInterface;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Sender\KnowledgeDocumentIndexMessageSenderInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;

class KnowledgeDocumentEventWriter implements KnowledgeDocumentEventWriterInterface
{
    public function __construct(
        protected KnowledgeDocumentSourcePluginResolverInterface $knowledgeDocumentSourcePluginResolver,
        protected KnowledgeDocumentPendingWriterInterface $knowledgeDocumentPendingWriter,
        protected KnowledgeDocumentIndexMessageSenderInterface $knowledgeDocumentIndexMessageSender,
        protected KnowledgeStorePluginInterface $knowledgeStorePlugin,
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     */
    public function writeCollectionByEvents(array $eventEntityTransfers, string $eventName): void
    {
        if ($eventEntityTransfers === []) {
            return;
        }

        foreach ($this->knowledgeDocumentSourcePluginResolver->getSourcePluginsByEventName($eventName) as $knowledgeDocumentSourcePlugin) {
            if (!$this->knowledgeStorePlugin->isConfigured($knowledgeDocumentSourcePlugin->getSourceType())) {
                continue;
            }

            $sourceIdentifiers = $knowledgeDocumentSourcePlugin->extractSourceIdentifiers($eventEntityTransfers, $eventName);

            $knowledgeDocumentTransfers = $this->knowledgeDocumentPendingWriter->writePendingDocuments(
                $knowledgeDocumentSourcePlugin->getSourceType(),
                $sourceIdentifiers,
                isContentHashCleared: false,
            );

            $this->knowledgeDocumentIndexMessageSender->sendIndexMessages($knowledgeDocumentTransfers);
        }
    }
}
