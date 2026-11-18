<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver;

use SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface;

interface KnowledgeDocumentSourcePluginResolverInterface
{
    /**
     * @return list<string>
     */
    public function getSubscribedEventNames(): array;

    /**
     * @return list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface>
     */
    public function getSourcePluginsByEventName(string $eventName): array;

    /**
     * @return list<\SprykerFeature\Zed\AiCommerce\Dependency\KnowledgeDocument\KnowledgeDocumentSourcePluginInterface>
     */
    public function getSourcePlugins(): array;

    public function findSourcePlugin(string $sourceType): ?KnowledgeDocumentSourcePluginInterface;
}
