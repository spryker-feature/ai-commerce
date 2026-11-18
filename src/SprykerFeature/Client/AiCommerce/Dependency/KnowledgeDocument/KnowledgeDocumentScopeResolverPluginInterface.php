<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Dependency\KnowledgeDocument;

use Generated\Shared\Transfer\KnowledgeDocumentScopeRequestTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentScopeTransfer;

interface KnowledgeDocumentScopeResolverPluginInterface
{
    /**
     * Specification:
     * - Returns true when `KnowledgeDocumentScopeRequest.scopeReferences` has values for a scope reference type that this plugin resolves.
     *
     * @api
     */
    public function isApplicable(KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer): bool;

    /**
     * Specification:
     * - Resolves the request to the document keys that the current customer may see in the current store and locale.
     * - A document key has the format `{sourceType}:{sourceIdentifier}`.
     * - Returns an empty scope with a customer safe message when nothing is visible.
     *
     * @api
     */
    public function resolveDocumentScope(
        KnowledgeDocumentScopeRequestTransfer $knowledgeDocumentScopeRequestTransfer
    ): KnowledgeDocumentScopeTransfer;
}
