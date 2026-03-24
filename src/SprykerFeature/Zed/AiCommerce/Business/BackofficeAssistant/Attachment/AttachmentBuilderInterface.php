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
     * @param array<int, array<string, string>> $rawAttachments
     *
     * @return array<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    public function buildAttachmentTransfers(array $rawAttachments): array;
}
