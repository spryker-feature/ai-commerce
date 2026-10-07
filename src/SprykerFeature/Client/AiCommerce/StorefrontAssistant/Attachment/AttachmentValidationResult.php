<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Attachment;

class AttachmentValidationResult
{
    /**
     * @param list<\Generated\Shared\Transfer\AttachmentTransfer> $attachments
     * @param array<string, mixed> $rejectionMessageParameters
     */
    public function __construct(
        protected bool $isValid,
        protected array $attachments = [],
        protected ?string $rejectionMessage = null,
        protected array $rejectionMessageParameters = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return list<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getRejectionMessage(): ?string
    {
        return $this->rejectionMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRejectionMessageParameters(): array
    {
        return $this->rejectionMessageParameters;
    }

    /**
     * @param list<\Generated\Shared\Transfer\AttachmentTransfer> $attachments
     */
    public static function valid(array $attachments): self
    {
        return new self(true, $attachments, null);
    }

    /**
     * @param array<string, mixed> $rejectionMessageParameters
     */
    public static function rejected(string $rejectionMessage, array $rejectionMessageParameters = []): self
    {
        return new self(false, [], $rejectionMessage, $rejectionMessageParameters);
    }
}
