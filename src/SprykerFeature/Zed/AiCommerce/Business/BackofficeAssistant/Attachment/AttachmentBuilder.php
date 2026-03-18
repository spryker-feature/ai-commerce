<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Attachment;

use Generated\Shared\Transfer\AttachmentTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Shared\Log\LoggerTrait;

class AttachmentBuilder implements AttachmentBuilderInterface
{
    use LoggerTrait;

    protected const int MAX_FILE_SIZE_BYTES = 5242880;

    protected const int MAX_TOTAL_ATTACHMENTS_BYTES = 10485760;

    protected const int MAX_ATTACHMENT_COUNT = 5;

    /**
     * @var array<string>
     */
    protected const array ALLOWED_MEDIA_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'text/csv',
    ];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rawAttachments
     *
     * @return array<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    public function buildAttachmentTransfers(array $rawAttachments): array
    {
        if ($rawAttachments === []) {
            return [];
        }

        $attachments = [];
        $totalSize = 0;
        $count = 0;

        foreach ($rawAttachments as $rawAttachment) {
            if ($count >= static::MAX_ATTACHMENT_COUNT) {
                $this->getLogger()->warning('Maximum attachment count exceeded, skipping remaining files.');

                break;
            }

            $mediaType = $rawAttachment['mediaType'] ?? '';
            $content = $rawAttachment['content'] ?? '';

            if (!in_array($mediaType, static::ALLOWED_MEDIA_TYPES, true)) {
                $this->getLogger()->warning(sprintf('Rejected attachment with unsupported media type: %s', $mediaType));

                continue;
            }

            if (!$content) {
                continue;
            }

            $decodedContent = base64_decode($content, true);

            if ($decodedContent === false) {
                $this->getLogger()->warning('Rejected attachment with invalid base64 content.');

                continue;
            }

            $fileSize = strlen($decodedContent);

            if ($fileSize > static::MAX_FILE_SIZE_BYTES) {
                $this->getLogger()->warning(sprintf('Rejected attachment exceeding max file size: %d bytes.', $fileSize));

                continue;
            }

            $totalSize += $fileSize;

            if ($totalSize > static::MAX_TOTAL_ATTACHMENTS_BYTES) {
                $this->getLogger()->warning('Total attachments size exceeded, skipping remaining files.');

                break;
            }

            $attachments[] = (new AttachmentTransfer())
                ->setType($this->resolveAttachmentType($mediaType))
                ->setContent($content)
                ->setContentType(AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64)
                ->setMediaType($mediaType);

            $count++;
        }

        return $attachments;
    }

    protected function resolveAttachmentType(string $mediaType): string
    {
        if (str_starts_with($mediaType, 'image/')) {
            return AiFoundationConstants::ATTACHMENT_TYPE_IMAGE;
        }

        return AiFoundationConstants::ATTACHMENT_TYPE_DOCUMENT;
    }
}
