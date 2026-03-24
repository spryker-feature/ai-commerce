<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Attachment;

use Generated\Shared\Transfer\AttachmentTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;

class AttachmentBuilder implements AttachmentBuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildAttachmentTransfers(array $rawAttachments): array
    {
        $attachments = [];

        foreach ($rawAttachments as $rawAttachment) {
            $mediaType = $rawAttachment['mediaType'] ?? '';
            $content = $rawAttachment['content'] ?? '';

            if (!$content || !$mediaType) {
                continue;
            }

            $attachments[] = (new AttachmentTransfer())
                ->setType($this->resolveAttachmentType($mediaType))
                ->setContent($content)
                ->setContentType(AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64)
                ->setMediaType($mediaType);
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
