<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentScopeTransfer;

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

        foreach ($this->knowledgeDocumentScopeResolverPlugins as $knowledgeDocumentScopeResolverPlugin) {
            if (!$knowledgeDocumentScopeResolverPlugin->isApplicable($knowledgeDocumentScopeRequestTransfer)) {
                continue;
            }

            $knowledgeDocumentScopeTransfer = $knowledgeDocumentScopeResolverPlugin->resolveDocumentScope($knowledgeDocumentScopeRequestTransfer);
            $documentKeys = [...$documentKeys, ...$knowledgeDocumentScopeTransfer->getDocumentKeys()];
            $messages = [...$messages, ...$knowledgeDocumentScopeTransfer->getMessages()];
        }

        return (new KnowledgeDocumentScopeTransfer())
            ->setDocumentKeys(array_values(array_unique($documentKeys)))
            ->setMessages(array_values(array_unique($messages)));
    }
}
