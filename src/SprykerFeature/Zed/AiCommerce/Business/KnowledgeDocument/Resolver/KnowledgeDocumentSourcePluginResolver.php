<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver;

use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;

class KnowledgeDocumentSourcePluginResolver implements KnowledgeDocumentSourcePluginResolverInterface
{
    /**
     * @param list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface> $knowledgeDocumentSourcePlugins
     */
    public function __construct(protected array $knowledgeDocumentSourcePlugins)
    {
    }

    /**
     * @return list<string>
     */
    public function getSubscribedEventNames(): array
    {
        $eventNames = [];

        foreach ($this->knowledgeDocumentSourcePlugins as $knowledgeDocumentSourcePlugin) {
            $eventNames = array_merge($eventNames, $knowledgeDocumentSourcePlugin->getSubscribedEvents());
        }

        return array_values(array_unique($eventNames));
    }

    /**
     * @return list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface>
     */
    public function getSourcePluginsByEventName(string $eventName): array
    {
        $knowledgeDocumentSourcePlugins = [];

        foreach ($this->knowledgeDocumentSourcePlugins as $knowledgeDocumentSourcePlugin) {
            if (!in_array($eventName, $knowledgeDocumentSourcePlugin->getSubscribedEvents(), true)) {
                continue;
            }

            $knowledgeDocumentSourcePlugins[] = $knowledgeDocumentSourcePlugin;
        }

        return $knowledgeDocumentSourcePlugins;
    }

    /**
     * @return list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface>
     */
    public function getSourcePlugins(): array
    {
        return $this->knowledgeDocumentSourcePlugins;
    }

    public function findSourcePlugin(string $sourceType): ?KnowledgeDocumentSourcePluginInterface
    {
        foreach ($this->knowledgeDocumentSourcePlugins as $knowledgeDocumentSourcePlugin) {
            if ($knowledgeDocumentSourcePlugin->getSourceType() === $sourceType) {
                return $knowledgeDocumentSourcePlugin;
            }
        }

        return null;
    }
}
