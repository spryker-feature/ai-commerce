<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Validator;

class StorefrontAssistantChatRequestValidationResult
{
    /**
     * @param list<\Generated\Shared\Transfer\ErrorTransfer> $errors
     * @param list<\Generated\Shared\Transfer\AttachmentTransfer> $attachments
     */
    public function __construct(
        protected array $errors = [],
        protected array $attachments = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return list<\Generated\Shared\Transfer\ErrorTransfer>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return list<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
}
