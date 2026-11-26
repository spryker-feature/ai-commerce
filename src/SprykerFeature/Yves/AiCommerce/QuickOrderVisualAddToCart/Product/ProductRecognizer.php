<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product;

use Generated\Shared\Transfer\AttachmentTransfer;
use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class ProductRecognizer implements ProductRecognizerInterface
{
    protected const string GLOSSARY_KEY_AI_REQUEST_FAILED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.ai-request-failed';

    protected const string GLOSSARY_KEY_AI_RESPONSE_INVALID = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.ai-response-invalid';

    protected const string DOCUMENT_FILE_BASE_NAME = 'order-form';

    protected const string PROMPT_LOCALE_PLACEHOLDER = '%s';

    public function __construct(
        protected AiFoundationClientInterface $aiFoundationClient,
        protected LocaleClientInterface $localeClient,
        protected AiCommerceConfig $config,
    ) {
    }

    public function recognizeProducts(string $base64Content, string $mimeType, ?string $fileExtension = null): ProductRecognitionCollectionTransfer
    {
        $promptRequestTransfer = $this->buildPromptRequestTransfer($base64Content, $mimeType, $fileExtension);
        $promptResponseTransfer = $this->aiFoundationClient->prompt($promptRequestTransfer);

        if (!$promptResponseTransfer->getIsSuccessful()) {
            return (new ProductRecognitionCollectionTransfer())
                ->setIsSuccessful(false)
                ->setErrorMessage(static::GLOSSARY_KEY_AI_REQUEST_FAILED);
        }

        $productRecognitionCollectionTransfer = $promptResponseTransfer->getStructuredMessage();

        if (!$productRecognitionCollectionTransfer instanceof ProductRecognitionCollectionTransfer) {
            return (new ProductRecognitionCollectionTransfer())
                ->setIsSuccessful(false)
                ->setErrorMessage(static::GLOSSARY_KEY_AI_RESPONSE_INVALID);
        }

        return $productRecognitionCollectionTransfer->setIsSuccessful(true);
    }

    protected function buildPromptRequestTransfer(string $base64Content, string $mimeType, ?string $fileExtension): PromptRequestTransfer
    {
        $locale = $this->localeClient->getCurrentLocale();
        $isDocument = $this->isDocumentMimeType($mimeType);
        $promptContent = $isDocument ? $this->buildDocumentPromptContent($locale) : $this->buildImagePromptContent($locale);

        $attachmentTransfer = (new AttachmentTransfer())
            ->setType($isDocument ? AiFoundationConstants::ATTACHMENT_TYPE_DOCUMENT : AiFoundationConstants::ATTACHMENT_TYPE_IMAGE)
            ->setContent($base64Content)
            ->setContentType(AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64)
            ->setMediaType(strtolower($mimeType));

        if ($isDocument) {
            $attachmentTransfer->setFilename($this->buildDocumentFilename($fileExtension));
        }

        $promptMessageTransfer = (new PromptMessageTransfer())
            ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
            ->setContent($promptContent)
            ->addAttachment($attachmentTransfer);

        return (new PromptRequestTransfer())
            ->setPromptMessage($promptMessageTransfer)
            ->setStructuredMessage(new ProductRecognitionCollectionTransfer())
            ->setAiConfigurationName($this->config->getQuickOrderVisualAddToCartAiConfigurationName());
    }

    protected function buildImagePromptContent(string $locale): string
    {
        return $this->replaceLocalePlaceholder($this->config->getQuickOrderVisualAddToCartImagePromptTemplate(), $locale);
    }

    protected function buildDocumentPromptContent(string $locale): string
    {
        return $this->replaceLocalePlaceholder($this->config->getQuickOrderVisualAddToCartDocumentPromptTemplate(), $locale);
    }

    protected function replaceLocalePlaceholder(string $promptTemplate, string $locale): string
    {
        return strtr($promptTemplate, [static::PROMPT_LOCALE_PLACEHOLDER => $locale]);
    }

    protected function isDocumentMimeType(string $mimeType): bool
    {
        return in_array(strtolower($mimeType), $this->config->getQuickOrderVisualAddToCartSupportedDocumentMimeTypes(), true);
    }

    protected function buildDocumentFilename(?string $fileExtension): string
    {
        if ($fileExtension === null) {
            return static::DOCUMENT_FILE_BASE_NAME;
        }

        return sprintf('%s.%s', static::DOCUMENT_FILE_BASE_NAME, strtolower($fileExtension));
    }
}
