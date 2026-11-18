<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\KnowledgeSearch;

use Generated\Shared\Transfer\KnowledgePassageCollectionTransfer;
use Generated\Shared\Transfer\KnowledgeSearchCriteriaTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use SprykerFeature\Client\AiCommerce\Dependency\KnowledgeSearch\KnowledgeSearchPluginInterface;

/**
 * - Searches an Amazon Bedrock managed knowledge base with a custom data source.
 * - The knowledge base parses, splits, embeds and reranks the documents itself. Use it with `BedrockKnowledgeBaseKnowledgeStorePlugin`, which sends the raw files.
 * - Reads the knowledge base ID and the data source ID from the configuration only.
 *
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 */
class BedrockKnowledgeBaseKnowledgeSearchPlugin extends AbstractPlugin implements KnowledgeSearchPluginInterface
{
    /**
     * {@inheritDoc}
     * - Returns `isSuccessful = false` without a request when neither document keys nor IDs are set.
     * - Sends one `Retrieve` request with a metadata filter: document keys and IDs, source types, locales (also `*`) and the document name.
     * - Requests at most 100 results. Reranks them with the configured reranking model type.
     * - `KnowledgePassage.content` is the passage text. `KnowledgePassage.chunkIndex` is not set.
     *
     * @api
     */
    public function searchDocuments(KnowledgeSearchCriteriaTransfer $knowledgeSearchCriteriaTransfer): KnowledgePassageCollectionTransfer
    {
        return $this->getFactory()
            ->createBedrockKnowledgeBasePassageReader()
            ->getKnowledgePassageCollection($knowledgeSearchCriteriaTransfer);
    }
}
