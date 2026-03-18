<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Attachment;

interface AttachmentBuilderInterface
{
    /**
     * Specification:
     * - Validates raw attachment data (size, count, media type).
     * - Decodes base64 content and builds AttachmentTransfer objects.
     * - Skips invalid attachments and logs warnings.
     * - Returns array of valid AttachmentTransfer objects.
     *
     * @param array<int, array<string, string>> $rawAttachments
     *
     * @return array<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    public function buildAttachmentTransfers(array $rawAttachments): array;
}
