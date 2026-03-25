<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Agent;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Generated\Shared\Transfer\GeneralPurposeAgentResponseTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

class GeneralPurposeAgentExecutor implements GeneralPurposeAgentExecutorInterface
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
            ->setAiConfigurationName(AiCommerceConstants::AI_CONFIGURATION_GENERAL_PURPOSE)
            ->setConversationReference($promptRequestTransfer->getConversationReference())
            ->setStructuredMessage(new GeneralPurposeAgentResponseTransfer())
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($promptRequestTransfer->getPrompt())
                    ->setAttachments($promptRequestTransfer->getAttachments()),
            );

        $promptResponse = $this->aiFoundationFacade->prompt($promptRequest);

        $backofficeAssistantPromptResponse = new BackofficeAssistantPromptResponseTransfer();

        if (!$promptResponse->getIsSuccessful()) {
            $this->getLogger()->error(sprintf('GeneralPurposeAgent prompt response is not successful: %s', json_encode($promptResponse->getErrors()->getArrayCopy())));

            return $backofficeAssistantPromptResponse;
        }

        /** @var \Generated\Shared\Transfer\GeneralPurposeAgentResponseTransfer $generalPurposeAgentResponse */
        $generalPurposeAgentResponse = $promptResponse->getStructuredMessage();

        $backofficeAssistantPromptResponse->setAgent($generalPurposeAgentResponse->getAgent());
        $backofficeAssistantPromptResponse->setMessage($generalPurposeAgentResponse->getMessage());
        $backofficeAssistantPromptResponse->setReasoningMessage($generalPurposeAgentResponse->getReasoningMessage());

        return $backofficeAssistantPromptResponse;
    }
}
