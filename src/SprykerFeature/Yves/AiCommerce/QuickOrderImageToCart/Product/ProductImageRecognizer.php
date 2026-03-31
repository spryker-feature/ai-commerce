<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product;

use Generated\Shared\Transfer\AttachmentTransfer;
use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;

class ProductImageRecognizer implements ProductImageRecognizerInterface
{
    protected const string PROMPT_TEMPLATE = 'I want you to support me for a quick add to cart functionality by identifying from a picture what the customer want to buy and provide me only with a JSON containing a list of products and quantities. If the user asks for a product to be compatible with any other product include it as part of each product name. Important! Your response must only contain the valid JSON object without any special or additional chars. Important! The image may contain SKU instead of product name, so use SKU as product name if it is recognized. Important! Current store locale is %s — translate recognized product names into the corresponding store locale if necessary.';

    protected const string GLOSSARY_KEY_AI_REQUEST_FAILED = 'ai-commerce.quick-order-image-to-cart.image-order.errors.ai-request-failed';

    protected const string GLOSSARY_KEY_AI_RESPONSE_INVALID = 'ai-commerce.quick-order-image-to-cart.image-order.errors.ai-response-invalid';

    public function __construct(
        protected AiFoundationClientInterface $aiFoundationClient,
        protected LocaleClientInterface $localeClient,
        protected AiCommerceConfig $config,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function recognizeProducts(string $base64Image, string $mimeType): ProductRecognitionCollectionTransfer
    {
        $promptRequestTransfer = $this->buildPromptRequestTransfer($base64Image, $mimeType);
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

    protected function buildPromptRequestTransfer(string $base64Image, string $mimeType): PromptRequestTransfer
    {
        $locale = $this->localeClient->getCurrentLocale();
        $promptContent = $this->buildPromptContent($locale);

        $attachmentTransfer = (new AttachmentTransfer())
            ->setType(AiFoundationConstants::ATTACHMENT_TYPE_IMAGE)
            ->setContent($base64Image)
            ->setContentType(AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64)
            ->setMediaType($mimeType);

        $promptMessageTransfer = (new PromptMessageTransfer())
            ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
            ->setContent($promptContent)
            ->addAttachment($attachmentTransfer);

        return (new PromptRequestTransfer())
            ->setPromptMessage($promptMessageTransfer)
            ->setStructuredMessage(new ProductRecognitionCollectionTransfer())
            ->setAiConfigurationName($this->config->getQuickOrderImageToCartAiConfigurationName());
    }

    protected function buildPromptContent(string $locale): string
    {
        return sprintf(static::PROMPT_TEMPLATE, $locale);
    }
}
