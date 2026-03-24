<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;

class BackofficeAssistantPromptRequestValidator implements BackofficeAssistantPromptRequestValidatorInterface
{
    protected const string MESSAGE_PROMPT_REQUIRED = 'backoffice_assistant.validation.prompt_required';

    protected const string MESSAGE_USER_UUID_REQUIRED = 'backoffice_assistant.validation.user_uuid_required';

    protected const string MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE = 'backoffice_assistant.validation.attachment_unsupported_media_type';

    protected const string MESSAGE_ATTACHMENT_FILE_TOO_LARGE = 'backoffice_assistant.validation.attachment_file_too_large';

    protected const string MESSAGE_ATTACHMENT_COUNT_EXCEEDED = 'backoffice_assistant.validation.attachment_count_exceeded';

    protected const string MESSAGE_ATTACHMENT_TOTAL_SIZE_EXCEEDED = 'backoffice_assistant.validation.attachment_total_size_exceeded';

    protected const string MESSAGE_ATTACHMENT_INVALID_CONTENT = 'backoffice_assistant.validation.attachment_invalid_content';

    public function __construct(protected AiCommerceConfig $config)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function validate(BackofficeAssistantPromptRequestTransfer $promptRequestTransfer): array
    {
        $errors = [];

        if (!$promptRequestTransfer->getPrompt()) {
            $errors[] = static::MESSAGE_PROMPT_REQUIRED;
        }

        if (!$promptRequestTransfer->getUserUuid()) {
            $errors[] = static::MESSAGE_USER_UUID_REQUIRED;
        }

        $attachmentErrors = $this->validateAttachments($promptRequestTransfer->getRawAttachments());

        return array_merge($errors, $attachmentErrors);
    }

    /**
     * @param array<int, array<string, string>> $rawAttachments
     *
     * @return array<string>
     */
    protected function validateAttachments(array $rawAttachments): array
    {
        if ($rawAttachments === []) {
            return [];
        }

        $errors = [];
        $maxCount = $this->config->getBackofficeAssistantAttachmentMaxCount();
        $maxFileSize = $this->config->getBackofficeAssistantAttachmentMaxFileSizeBytes();
        $maxTotalSize = $this->config->getBackofficeAssistantAttachmentMaxTotalSizeBytes();
        $allowedMediaTypes = $this->config->getBackofficeAssistantAttachmentAllowedMediaTypes();

        if (count($rawAttachments) > $maxCount) {
            $errors[] = static::MESSAGE_ATTACHMENT_COUNT_EXCEEDED;
        }

        $totalSize = 0;

        foreach ($rawAttachments as $rawAttachment) {
            $mediaType = $rawAttachment['mediaType'] ?? '';
            $content = $rawAttachment['content'] ?? '';

            if (!in_array($mediaType, $allowedMediaTypes, true)) {
                $errors[] = static::MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE;

                continue;
            }

            if (!$content) {
                continue;
            }

            $decodedContent = base64_decode($content, true);

            if ($decodedContent === false) {
                $errors[] = static::MESSAGE_ATTACHMENT_INVALID_CONTENT;

                continue;
            }

            $fileSize = strlen($decodedContent);

            if ($fileSize > $maxFileSize) {
                $errors[] = static::MESSAGE_ATTACHMENT_FILE_TOO_LARGE;

                continue;
            }

            $totalSize += $fileSize;
        }

        if ($totalSize > $maxTotalSize) {
            $errors[] = static::MESSAGE_ATTACHMENT_TOTAL_SIZE_EXCEEDED;
        }

        return $errors;
    }
}
