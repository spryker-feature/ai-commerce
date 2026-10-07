<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Attachment;

use Generated\Shared\Transfer\AttachmentTransfer;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Shared\AiCommerce\Attachment\AttachmentPayloadKey;

class AttachmentValidator implements AttachmentValidatorInterface
{
    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64
     */
    protected const string ATTACHMENT_CONTENT_TYPE_BASE64 = 'base64';

    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::ATTACHMENT_TYPE_IMAGE
     */
    protected const string ATTACHMENT_TYPE_IMAGE = 'image';

    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::ATTACHMENT_TYPE_DOCUMENT
     */
    protected const string ATTACHMENT_TYPE_DOCUMENT = 'document';

    protected const string IMAGE_MIME_PREFIX = 'image/';

    protected const string MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE = 'ai_commerce.storefront_assistant.attachment.unsupported_type';

    protected const string MESSAGE_ATTACHMENT_FILE_TOO_LARGE = 'ai_commerce.storefront_assistant.attachment.too_large';

    protected const string MESSAGE_ATTACHMENT_TOO_MANY = 'ai_commerce.storefront_assistant.attachment.too_many';

    protected const string GLOSSARY_PARAMETER_TYPES = '%types%';

    protected const string GLOSSARY_PARAMETER_MAX_BYTES = '%maxBytes%';

    protected const string GLOSSARY_PARAMETER_MAX_COUNT = '%maxCount%';

    protected const string MEDIA_TYPE_WEBP = 'image/webp';

    protected const string WEBP_FORM_TYPE_SIGNATURE = 'WEBP';

    protected const int WEBP_FORM_TYPE_OFFSET = 8;

    public function __construct(protected AiCommerceConfig $aiCommerceConfig)
    {
    }

    public function validate(StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer): AttachmentValidationResult
    {
        $rawAttachments = $storefrontAssistantChatRequestTransfer->getRawAttachments();

        if ($rawAttachments === []) {
            return AttachmentValidationResult::valid([]);
        }

        $maxAttachmentCount = $this->aiCommerceConfig->getStorefrontAssistantMaxAttachmentCount();

        if (count($rawAttachments) > $maxAttachmentCount) {
            return AttachmentValidationResult::rejected(
                static::MESSAGE_ATTACHMENT_TOO_MANY,
                $this->buildTooManyParameters($maxAttachmentCount),
            );
        }

        $supportedMimeTypes = $this->aiCommerceConfig->getStorefrontAssistantSupportedAttachmentMimeTypes();
        $maxSizeBytes = $this->aiCommerceConfig->getStorefrontAssistantMaxAttachmentSizeBytes();

        $attachments = [];

        foreach ($rawAttachments as $rawAttachment) {
            if (!is_array($rawAttachment)) {
                return AttachmentValidationResult::rejected(
                    static::MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE,
                    $this->buildUnsupportedTypeParameters($supportedMimeTypes),
                );
            }

            $mediaType = is_scalar($rawAttachment[AttachmentPayloadKey::MEDIA_TYPE] ?? null)
                ? (string)$rawAttachment[AttachmentPayloadKey::MEDIA_TYPE]
                : '';
            $content = is_scalar($rawAttachment[AttachmentPayloadKey::CONTENT] ?? null)
                ? (string)$rawAttachment[AttachmentPayloadKey::CONTENT]
                : '';
            $name = is_scalar($rawAttachment[AttachmentPayloadKey::NAME] ?? null)
                ? (string)$rawAttachment[AttachmentPayloadKey::NAME]
                : '';

            if (!in_array($mediaType, $supportedMimeTypes, true)) {
                return AttachmentValidationResult::rejected(
                    static::MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE,
                    $this->buildUnsupportedTypeParameters($supportedMimeTypes),
                );
            }

            $decodedContent = $this->decodeContent($content);

            if ($decodedContent === null) {
                return AttachmentValidationResult::rejected(
                    static::MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE,
                    $this->buildUnsupportedTypeParameters($supportedMimeTypes),
                );
            }

            if (strlen($decodedContent) > $maxSizeBytes) {
                return AttachmentValidationResult::rejected(
                    static::MESSAGE_ATTACHMENT_FILE_TOO_LARGE,
                    $this->buildTooLargeParameters($maxSizeBytes),
                );
            }

            if (!$this->isContentMatchingMediaType($decodedContent, $mediaType)) {
                return AttachmentValidationResult::rejected(
                    static::MESSAGE_ATTACHMENT_UNSUPPORTED_MEDIA_TYPE,
                    $this->buildUnsupportedTypeParameters($supportedMimeTypes),
                );
            }

            $attachments[] = (new AttachmentTransfer())
                ->setType($this->resolveAttachmentType($mediaType))
                ->setContentType(static::ATTACHMENT_CONTENT_TYPE_BASE64)
                ->setContent($content)
                ->setMediaType($mediaType)
                ->setFilename($this->sanitizeFilename($name));
        }

        return AttachmentValidationResult::valid($attachments);
    }

    protected function decodeContent(string $base64Content): ?string
    {
        if ($base64Content === '') {
            return null;
        }

        $decodedContent = base64_decode($base64Content, true);

        if ($decodedContent === false || $decodedContent === '') {
            return null;
        }

        return $decodedContent;
    }

    protected function isContentMatchingMediaType(string $decodedContent, string $mediaType): bool
    {
        $signatures = $this->aiCommerceConfig->getStorefrontAssistantAttachmentMimeTypeSignatures()[$mediaType] ?? null;

        if ($signatures === null) {
            return false;
        }

        foreach ($signatures as $signature) {
            if (!str_starts_with($decodedContent, $signature)) {
                continue;
            }

            if ($mediaType === static::MEDIA_TYPE_WEBP) {
                return str_starts_with(substr($decodedContent, static::WEBP_FORM_TYPE_OFFSET), static::WEBP_FORM_TYPE_SIGNATURE);
            }

            return true;
        }

        return false;
    }

    protected function sanitizeFilename(string $filename): string
    {
        $sanitizedFilename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?? '';
        $sanitizedFilename = str_replace(['\\', '/'], '', $sanitizedFilename);
        $sanitizedFilename = trim(basename($sanitizedFilename));

        return mb_substr($sanitizedFilename, 0, $this->aiCommerceConfig->getStorefrontAssistantMaxAttachmentFilenameLength());
    }

    protected function resolveAttachmentType(string $mediaType): string
    {
        if (str_starts_with($mediaType, static::IMAGE_MIME_PREFIX)) {
            return static::ATTACHMENT_TYPE_IMAGE;
        }

        return static::ATTACHMENT_TYPE_DOCUMENT;
    }

    /**
     * @param list<string> $supportedMimeTypes
     *
     * @return array<string, mixed>
     */
    protected function buildUnsupportedTypeParameters(array $supportedMimeTypes): array
    {
        return [static::GLOSSARY_PARAMETER_TYPES => implode(', ', $supportedMimeTypes)];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTooLargeParameters(int $maxSizeBytes): array
    {
        return [static::GLOSSARY_PARAMETER_MAX_BYTES => $maxSizeBytes];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTooManyParameters(int $maxAttachmentCount): array
    {
        return [static::GLOSSARY_PARAMETER_MAX_COUNT => $maxAttachmentCount];
    }
}
