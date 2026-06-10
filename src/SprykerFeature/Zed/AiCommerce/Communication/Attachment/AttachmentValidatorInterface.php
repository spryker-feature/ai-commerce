<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\Attachment;

interface AttachmentValidatorInterface
{
    /**
     * Specification:
     * - Validates raw client attachments (each with a mediaType and base64 content) against the configured limits.
     * - Returns errors for unsupported media types, invalid base64 content, files exceeding the max file size, exceeding the max count, or exceeding the max total size.
     * - Returns an empty array when there are no attachments or all attachments are valid.
     *
     * @param array<int, array<string, string>> $rawAttachments
     *
     * @return array<\Generated\Shared\Transfer\ErrorTransfer>
     */
    public function validateRawAttachments(array $rawAttachments): array;
}
