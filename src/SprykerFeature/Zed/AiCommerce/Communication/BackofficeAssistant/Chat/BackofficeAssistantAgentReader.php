<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;

class BackofficeAssistantAgentReader implements BackofficeAssistantAgentReaderInterface
{
    protected const string KEY_NAME = 'name';

    protected const string KEY_DESCRIPTION = 'description';

    /**
     * @param array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface> $backofficeAssistantAgentPlugins
     */
    public function __construct(protected array $backofficeAssistantAgentPlugins)
    {
    }

    public function getAvailableAgents(): array
    {
        $backofficeAssistantPromptRequestTransfer = new BackofficeAssistantPromptRequestTransfer();
        $availableAgents = [];

        foreach ($this->backofficeAssistantAgentPlugins as $backofficeAssistantAgentPlugin) {
            if (!$backofficeAssistantAgentPlugin->isApplicable($backofficeAssistantPromptRequestTransfer)) {
                continue;
            }

            $availableAgents[] = [
                static::KEY_NAME => $backofficeAssistantAgentPlugin->getName(),
                static::KEY_DESCRIPTION => $backofficeAssistantAgentPlugin->getDescription(),
            ];
        }

        return $availableAgents;
    }
}
