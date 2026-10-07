<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent;

use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantAgentPluginInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Gate\StorefrontAssistantFeatureGateInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Translation\StorefrontAssistantTranslatorInterface;

class AgentSelector implements AgentSelectorInterface
{
    protected const string GLOSSARY_KEY_AGENT_LABEL = 'ai_commerce.storefront_assistant.agent.%s.label';

    protected const string GLOSSARY_KEY_AGENT_DESCRIPTION = 'ai_commerce.storefront_assistant.agent.%s.description';

    protected const string AGENT_KEY_NAME = 'name';

    protected const string AGENT_KEY_LABEL = 'label';

    protected const string AGENT_KEY_DESCRIPTION = 'description';

    protected const string AGENT_NAME_WORD_SEPARATORS = '_-';

    /**
     * @param array<\SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantAgentPluginInterface> $storefrontAssistantAgentPlugins
     */
    public function __construct(
        protected StorefrontAssistantFeatureGateInterface $storefrontAssistantFeatureGate,
        protected array $storefrontAssistantAgentPlugins,
        protected StorefrontAssistantTranslatorInterface $storefrontAssistantTranslator
    ) {
    }

    public function selectAgent(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): ?StorefrontAssistantAgentPluginInterface {
        $selectedAgentName = trim((string)$storefrontAssistantChatRequestTransfer->getSelectedAgent());

        foreach ($this->storefrontAssistantAgentPlugins as $storefrontAssistantAgentPlugin) {
            if (!$this->storefrontAssistantFeatureGate->isAgentEnabled($storefrontAssistantAgentPlugin->getEnabledConfigurationKey())) {
                continue;
            }

            if ($selectedAgentName !== '' && $storefrontAssistantAgentPlugin->getName() === $selectedAgentName) {
                return $storefrontAssistantAgentPlugin;
            }
        }

        foreach ($this->storefrontAssistantAgentPlugins as $storefrontAssistantAgentPlugin) {
            if (!$this->storefrontAssistantFeatureGate->isAgentEnabled($storefrontAssistantAgentPlugin->getEnabledConfigurationKey())) {
                continue;
            }

            if ($storefrontAssistantAgentPlugin->isApplicable($storefrontAssistantChatRequestTransfer)) {
                return $storefrontAssistantAgentPlugin;
            }
        }

        return null;
    }

    public function getEnabledAgents(): array
    {
        $enabledAgents = [];

        foreach ($this->storefrontAssistantAgentPlugins as $storefrontAssistantAgentPlugin) {
            if (!$this->storefrontAssistantFeatureGate->isAgentEnabled($storefrontAssistantAgentPlugin->getEnabledConfigurationKey())) {
                continue;
            }

            $agentName = $storefrontAssistantAgentPlugin->getName();

            $enabledAgents[] = [
                static::AGENT_KEY_NAME => $agentName,
                static::AGENT_KEY_LABEL => $this->translateAgentText(
                    sprintf(static::GLOSSARY_KEY_AGENT_LABEL, $agentName),
                    $this->humanizeAgentName($agentName),
                ),
                static::AGENT_KEY_DESCRIPTION => $this->translateAgentText(
                    sprintf(static::GLOSSARY_KEY_AGENT_DESCRIPTION, $agentName),
                    $storefrontAssistantAgentPlugin->getDescription(),
                ),
            ];
        }

        return $enabledAgents;
    }

    protected function translateAgentText(string $glossaryKey, string $fallback): string
    {
        $translation = trim($this->storefrontAssistantTranslator->translate($glossaryKey));

        if ($translation === '' || $translation === $glossaryKey) {
            return $fallback;
        }

        return $translation;
    }

    protected function humanizeAgentName(string $agentName): string
    {
        $humanizedAgentName = trim(str_replace(
            str_split(static::AGENT_NAME_WORD_SEPARATORS),
            ' ',
            $agentName,
        ));

        if ($humanizedAgentName === '') {
            return $agentName;
        }

        return ucwords($humanizedAgentName);
    }
}
