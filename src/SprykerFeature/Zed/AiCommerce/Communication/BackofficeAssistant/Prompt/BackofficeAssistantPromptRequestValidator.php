<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Prompt;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Zed\AiCommerce\Communication\Attachment\AttachmentValidatorInterface;

class BackofficeAssistantPromptRequestValidator implements BackofficeAssistantPromptRequestValidatorInterface
{
    protected const string MESSAGE_PROMPT_REQUIRED = 'backoffice_assistant.validation.prompt_required';

    protected const string MESSAGE_USER_ID_REQUIRED = 'backoffice_assistant.validation.user_id_required';

    public function __construct(protected readonly AttachmentValidatorInterface $attachmentValidator)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function validate(BackofficeAssistantPromptRequestTransfer $promptRequestTransfer): array
    {
        $errors = [];

        if (!$promptRequestTransfer->getPrompt()) {
            $errors[] = $this->createErrorTransfer(static::MESSAGE_PROMPT_REQUIRED);
        }

        if (!$promptRequestTransfer->getIdUser()) {
            $errors[] = $this->createErrorTransfer(static::MESSAGE_USER_ID_REQUIRED);
        }

        return array_merge($errors, $this->attachmentValidator->validateRawAttachments($promptRequestTransfer->getRawAttachments()));
    }

    protected function createErrorTransfer(string $message): ErrorTransfer
    {
        return (new ErrorTransfer())->setMessage($message);
    }
}
