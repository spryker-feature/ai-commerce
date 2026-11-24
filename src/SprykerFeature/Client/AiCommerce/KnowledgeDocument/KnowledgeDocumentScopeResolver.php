<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use ArrayObject;
use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentScopeTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class KnowledgeDocumentScopeResolver implements KnowledgeDocumentScopeResolverInterface
{
    /**
     * @param list<\SprykerFeature\Client\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentScopeResolverPluginInterface> $knowledgeDocumentScopeResolverPlugins
     */
    public function __construct(protected array $knowledgeDocumentScopeResolverPlugins)
    {
    }

    public function resolveDocumentScope(
        KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer
    ): KnowledgeDocumentScopeTransfer {
        $documentKeys = [];
        $messages = [];
        $knowledgeDocumentSourceTransfers = [];

        foreach ($this->knowledgeDocumentScopeResolverPlugins as $knowledgeDocumentScopeResolverPlugin) {
            if (!$knowledgeDocumentScopeResolverPlugin->isApplicable($knowledgeDocumentScopeRequestTransfer)) {
                continue;
            }

            $knowledgeDocumentScopeTransfer = $knowledgeDocumentScopeResolverPlugin->resolveDocumentScope($knowledgeDocumentScopeRequestTransfer);
            $documentKeys = [...$documentKeys, ...$knowledgeDocumentScopeTransfer->getDocumentKeys()];
            $messages = [...$messages, ...$knowledgeDocumentScopeTransfer->getMessages()];
            $knowledgeDocumentSourceTransfers = $this->addKnowledgeDocumentSources(
                $knowledgeDocumentSourceTransfers,
                $knowledgeDocumentScopeTransfer->getKnowledgeDocumentSources(),
            );
        }

        return (new KnowledgeDocumentScopeTransfer())
            ->setDocumentKeys(array_values(array_unique($documentKeys)))
            ->setMessages(array_values(array_unique($messages)))
            ->setKnowledgeDocumentSources(new ArrayObject(array_values($knowledgeDocumentSourceTransfers)));
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer> $knowledgeDocumentSourceTransfers
     * @param \ArrayObject<int, \Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer> $addedKnowledgeDocumentSourceTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer>
     */
    protected function addKnowledgeDocumentSources(
        array $knowledgeDocumentSourceTransfers,
        ArrayObject $addedKnowledgeDocumentSourceTransfers
    ): array {
        foreach ($addedKnowledgeDocumentSourceTransfers as $knowledgeDocumentSourceTransfer) {
            $knowledgeDocumentSourceTransfers[$this->getKnowledgeDocumentSourceKey($knowledgeDocumentSourceTransfer)] ??= $knowledgeDocumentSourceTransfer;
        }

        return $knowledgeDocumentSourceTransfers;
    }

    protected function getKnowledgeDocumentSourceKey(KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): string
    {
        return sprintf(
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_KEY_FORMAT,
            $knowledgeDocumentSourceTransfer->getSourceType(),
            $knowledgeDocumentSourceTransfer->getSourceIdentifier(),
        );
    }
}
