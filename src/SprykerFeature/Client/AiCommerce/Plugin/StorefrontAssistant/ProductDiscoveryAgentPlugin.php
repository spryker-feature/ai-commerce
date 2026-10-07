<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant;

use Generated\Shared\Transfer\PromptResponseTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\Kernel\BundleConfigResolverAwareTrait;
use SprykerFeature\Client\AiCommerce\Dependency\StorefrontAssistant\StorefrontAssistantAgentPluginInterface;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceConfig getConfig()
 */
class ProductDiscoveryAgentPlugin extends AbstractPlugin implements StorefrontAssistantAgentPluginInterface
{
    use BundleConfigResolverAwareTrait;

    protected const string AGENT_NAME = 'product_discovery';

    protected const string AGENT_DESCRIPTION = 'Finds real catalog products, categories and related products for the customer.';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::AGENT_NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return static::AGENT_DESCRIPTION;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isApplicable(StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getEnabledConfigurationKey(): string
    {
        return $this->getConfig()->getProductDiscoveryAgentEnabledKey();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function executeAgent(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): PromptResponseTransfer {
        $promptRequestTransfer = $this->getFactory()
            ->createProductDiscoveryPromptRequestBuilder()
            ->buildPromptRequest($storefrontAssistantChatRequestTransfer);

        return $this->getFactory()
            ->getAiFoundationClient()
            ->streamPrompt($promptRequestTransfer);
    }
}
