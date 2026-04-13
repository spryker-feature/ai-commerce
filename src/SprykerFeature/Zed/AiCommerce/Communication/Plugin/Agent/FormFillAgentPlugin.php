<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Generated\Shared\Transfer\FormFillAgentResponseTransfer;
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
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class FormFillAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    use LoggerTrait;

    protected const string NAME = 'Form Fill';

    /**
     * @uses \SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\FormFillToolSetPlugin::TOOL_SET_FORM_FILL
     */
    protected const string TOOL_SET_FORM_FILL = 'form_fill_tools';

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
        return 'Fills backoffice forms based on natural language instructions. When the user provides a form structure in context and describes what values to enter, this agent analyses the request and fills the form fields accordingly. Use for any request that involves populating or pre-filling a form on the current page.';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isApplicable(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): bool {
        return $this->getConfig()->isFormFillAgentEnabled();
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
            ->setAiConfigurationName(AiCommerceConstants::AI_CONFIGURATION_FORM_FILL)
            ->setConversationReference($backofficeAssistantPromptRequest->getConversationReference())
            ->setStructuredMessage(new FormFillAgentResponseTransfer())
            ->addToolSetName(static::TOOL_SET_FORM_FILL)
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
                'FormFillAgent prompt response is not successful: %s',
                implode(', ', array_map(static fn ($error) => $error->getMessage(), $promptResponse->getErrors()->getArrayCopy())),
            ));

            return $backofficeAssistantPromptResponse;
        }

        /** @var \Generated\Shared\Transfer\FormFillAgentResponseTransfer $formFillAgentResponse */
        $formFillAgentResponse = $promptResponse->getStructuredMessage();

        $backofficeAssistantPromptResponse->setAgent($formFillAgentResponse->getAgent());
        $backofficeAssistantPromptResponse->setMessage($formFillAgentResponse->getMessage());
        $backofficeAssistantPromptResponse->setReasoningMessage($formFillAgentResponse->getReasoningMessage());

        return $backofficeAssistantPromptResponse;
    }
}
