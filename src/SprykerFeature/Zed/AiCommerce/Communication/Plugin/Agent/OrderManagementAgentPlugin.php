<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class OrderManagementAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    protected const string NAME = 'Order Management';

    public function getName(): string
    {
        return static::NAME;
    }

    public function getDescription(): string
    {
        return 'Handles questions about order OMS states, transitions, manual events, and process definitions. Examples: "Why is order DE--123 stuck?", "What events does order DE--123 expect?", "Is order DE--123 cancellable?"';
    }

    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer {
        return $this->getFacade()->executeOrderManagementAgent($backofficeAssistantPromptRequest);
    }
}
