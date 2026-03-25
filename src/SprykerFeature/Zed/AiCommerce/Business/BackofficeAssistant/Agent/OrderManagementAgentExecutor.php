<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Agent;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Generated\Shared\Transfer\OrderManagementAgentResponseTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

class OrderManagementAgentExecutor implements OrderManagementAgentExecutorInterface
{
    use LoggerTrait;

    public function __construct(
        protected AiFoundationFacadeInterface $aiFoundationFacade,
    ) {
    }

    public function execute(
        BackofficeAssistantPromptRequestTransfer $promptRequestTransfer,
    ): BackofficeAssistantPromptResponseTransfer {
        $promptRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName(AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT)
            ->setConversationReference($promptRequestTransfer->getConversationReference())
            ->setStructuredMessage(new OrderManagementAgentResponseTransfer())
            ->addToolSetName(AiCommerceConstants::TOOL_SET_ORDER_MANAGEMENT)
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($promptRequestTransfer->getPrompt())
                    ->setAttachments($promptRequestTransfer->getAttachments()),
            );

        $promptResponse = $this->aiFoundationFacade->prompt($promptRequest);

        $backofficeAssistantPromptResponse = new BackofficeAssistantPromptResponseTransfer();

        if (!$promptResponse->getIsSuccessful()) {
            $this->getLogger()->error(sprintf('OrderManagementAgent prompt response is not successful: %s', json_encode($promptResponse->getErrors()->getArrayCopy())));

            return $backofficeAssistantPromptResponse;
        }

        /** @var \Generated\Shared\Transfer\OrderManagementAgentResponseTransfer $orderManagementAgentResponse */
        $orderManagementAgentResponse = $promptResponse->getStructuredMessage();

        $backofficeAssistantPromptResponse->setAgent($orderManagementAgentResponse->getAgent());
        $backofficeAssistantPromptResponse->setMessage($orderManagementAgentResponse->getMessage());
        $backofficeAssistantPromptResponse->setReasoningMessage($orderManagementAgentResponse->getReasoningMessage());

        return $backofficeAssistantPromptResponse;
    }
}
