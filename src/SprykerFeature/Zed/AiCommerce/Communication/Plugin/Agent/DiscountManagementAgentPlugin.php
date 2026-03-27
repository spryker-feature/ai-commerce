<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Generated\Shared\Transfer\DiscountManagementAgentResponseTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class DiscountManagementAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    use LoggerTrait;

    protected const string NAME = 'Discount Management';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Handles discount creation, updates, listing, and activation/deactivation. Examples: "Create a 10% discount on all items valid until end of year", "Show me all active discounts", "Deactivate discount #5", "What are the details of discount 12?"';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer {
        $promptRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName(AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT)
            ->setConversationReference($backofficeAssistantPromptRequest->getConversationReference())
            ->setStructuredMessage(new DiscountManagementAgentResponseTransfer())
            ->addToolSetName(AiCommerceConstants::TOOL_SET_DISCOUNT_MANAGEMENT)
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($backofficeAssistantPromptRequest->getPrompt())
                    ->setAttachments($backofficeAssistantPromptRequest->getAttachments()),
            );

        $promptResponse = $this->getFactory()->getAiFoundationFacade()->prompt($promptRequest);

        $backofficeAssistantPromptResponse = new BackofficeAssistantPromptResponseTransfer();

        if (!$promptResponse->getIsSuccessful()) {
            $this->getLogger()->error(sprintf(
                'DiscountManagementAgent prompt response is not successful: %s',
                implode(', ', array_map(static fn ($error) => $error->getMessage(), $promptResponse->getErrors()->getArrayCopy())),
            ));

            return $backofficeAssistantPromptResponse;
        }

        /** @var \Generated\Shared\Transfer\DiscountManagementAgentResponseTransfer $discountManagementAgentResponse */
        $discountManagementAgentResponse = $promptResponse->getStructuredMessage();

        $backofficeAssistantPromptResponse->setAgent($discountManagementAgentResponse->getAgent());
        $backofficeAssistantPromptResponse->setMessage($discountManagementAgentResponse->getMessage());
        $backofficeAssistantPromptResponse->setReasoningMessage($discountManagementAgentResponse->getReasoningMessage());

        return $backofficeAssistantPromptResponse;
    }
}
