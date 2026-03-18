<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Plugin\Agent;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class GeneralPurposeAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    protected const string NAME = 'General Purpose Agent';

    protected const string MESSAGE_NO_RESPONSE = 'No response received.';

    public function getName(): string
    {
        return static::NAME;
    }

    public function getDescription(): string
    {
        return 'Handles questions only about Spryker Backoffice navigation. Examples: "Where can I manage user roles?", "How do I access the CMS pages?"';
    }

    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer {
        return $this->getFacade()->executeGeneralPurposeAgent($backofficeAssistantPromptRequest);
    }
}
