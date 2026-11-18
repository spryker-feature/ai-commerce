<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeStore\Setup;

use Generated\Shared\Transfer\KnowledgeStoreSetupRequestTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupResponseTransfer;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\KnowledgeDocumentSourcePluginResolverInterface;
use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeStore\KnowledgeStorePluginInterface;

class KnowledgeStoreSetup implements KnowledgeStoreSetupInterface
{
    public function __construct(
        protected KnowledgeStorePluginInterface $knowledgeStorePlugin,
        protected KnowledgeDocumentSourcePluginResolverInterface $knowledgeDocumentSourcePluginResolver
    ) {
    }

    public function setupKnowledgeStore(KnowledgeStoreSetupRequestTransfer $knowledgeStoreSetupRequestTransfer): KnowledgeStoreSetupResponseTransfer
    {
        if ($knowledgeStoreSetupRequestTransfer->getSourceTypes() === []) {
            $knowledgeStoreSetupRequestTransfer->setSourceTypes($this->getRegisteredSourceTypes());
        }

        return $this->knowledgeStorePlugin->setupStore($knowledgeStoreSetupRequestTransfer);
    }

    /**
     * @return list<string>
     */
    protected function getRegisteredSourceTypes(): array
    {
        $sourceTypes = [];

        foreach ($this->knowledgeDocumentSourcePluginResolver->getSourcePlugins() as $knowledgeDocumentSourcePlugin) {
            $sourceTypes[] = $knowledgeDocumentSourcePlugin->getSourceType();
        }

        return array_values(array_unique($sourceTypes));
    }
}
