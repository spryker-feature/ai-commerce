<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Attachment;

use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;

class AttachmentValidator implements AttachmentValidatorInterface
{
    protected const string MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE = 'backoffice_assistant.validation.attachment_unsupported_media_type';

    protected const string MESSAGE_ATTACHMENT_FILE_TOO_LARGE = 'backoffice_assistant.validation.attachment_file_too_large';

    protected const string MESSAGE_ATTACHMENT_COUNT_EXCEEDED = 'backoffice_assistant.validation.attachment_count_exceeded';

    protected const string MESSAGE_ATTACHMENT_TOTAL_SIZE_EXCEEDED = 'backoffice_assistant.validation.attachment_total_size_exceeded';

    protected const string MESSAGE_ATTACHMENT_INVALID_CONTENT = 'backoffice_assistant.validation.attachment_invalid_content';

    protected const string KEY_MEDIA_TYPE = 'mediaType';

    protected const string KEY_CONTENT = 'content';

    public function __construct(protected readonly AiCommerceConfig $aiCommerceConfig)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function validateRawAttachments(array $rawAttachments): array
    {
        if ($rawAttachments === []) {
            return [];
        }

        $errors = [];
        $allowedMediaTypes = $this->aiCommerceConfig->getBackofficeAssistantAttachmentAllowedMediaTypes();

        if (count($rawAttachments) > $this->aiCommerceConfig->getBackofficeAssistantAttachmentMaxCount()) {
            $errors[] = $this->createErrorTransfer(static::MESSAGE_ATTACHMENT_COUNT_EXCEEDED);
        }

        $totalSize = 0;

        foreach ($rawAttachments as $rawAttachment) {
            $errors = array_merge($errors, $this->validateSingleAttachment($rawAttachment, $allowedMediaTypes, $totalSize));
        }

        if ($totalSize > $this->aiCommerceConfig->getBackofficeAssistantAttachmentMaxTotalSizeBytes()) {
            $errors[] = $this->createErrorTransfer(static::MESSAGE_ATTACHMENT_TOTAL_SIZE_EXCEEDED);
        }

        return $errors;
    }

    /**
     * @param array<string, string> $rawAttachment
     * @param array<string> $allowedMediaTypes
     *
     * @return array<\Generated\Shared\Transfer\ErrorTransfer>
     */
    protected function validateSingleAttachment(array $rawAttachment, array $allowedMediaTypes, int &$totalSize): array
    {
        $mediaType = $rawAttachment[static::KEY_MEDIA_TYPE] ?? '';
        $content = $rawAttachment[static::KEY_CONTENT] ?? '';

        if (!in_array($mediaType, $allowedMediaTypes, true)) {
            return [$this->createErrorTransfer(static::MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE)];
        }

        if (!$content) {
            return [];
        }

        $decodedContent = base64_decode($content, true);

        if ($decodedContent === false) {
            return [$this->createErrorTransfer(static::MESSAGE_ATTACHMENT_INVALID_CONTENT)];
        }

        $fileSize = strlen($decodedContent);

        if ($fileSize > $this->aiCommerceConfig->getBackofficeAssistantAttachmentMaxFileSizeBytes()) {
            return [$this->createErrorTransfer(static::MESSAGE_ATTACHMENT_FILE_TOO_LARGE)];
        }

        $totalSize += $fileSize;

        return [];
    }

    protected function createErrorTransfer(string $message): ErrorTransfer
    {
        return (new ErrorTransfer())->setMessage($message);
    }
}
