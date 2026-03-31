<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\Handler;

use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Collector\QuickOrderItemCollectorInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\ImageOrderForm;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Encoder\UploadedImageEncoderInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Message\NotFoundProductNotifierInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\ProductImageRecognizerInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\ProductRecognitionValidatorInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ImageOrderFormHandler implements ImageOrderFormHandlerInterface
{
    protected const string GLOSSARY_KEY_IMAGE_READ_FAILED = 'ai-commerce.quick-order-image-to-cart.image-order.errors.ai-request-failed';

    public function __construct(
        protected UploadedImageEncoderInterface $uploadedImageEncoder,
        protected ProductImageRecognizerInterface $productImageRecognizer,
        protected ProductRecognitionValidatorInterface $productRecognitionValidator,
        protected QuickOrderItemCollectorInterface $quickOrderItemCollector,
        protected NotFoundProductNotifierInterface $notFoundProductNotifier,
    ) {
    }

    /**
     * @return array<\Generated\Shared\Transfer\QuickOrderItemTransfer>
     */
    public function handleForm(FormInterface $form, Request $request): array
    {
        if ($request->get(ImageOrderForm::SUBMIT_BUTTON_UPLOAD_IMAGE) === null) {
            return [];
        }

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return [];
        }

        $uploadedImage = $this->extractUploadedImage($form);
        if (!$uploadedImage) {
            return [];
        }

        return $this->processUploadedImage($uploadedImage, $form);
    }

    protected function extractUploadedImage(FormInterface $form): ?UploadedFile
    {
        $formData = $form->getData();
        $uploadedImage = $formData[ImageOrderForm::FIELD_UPLOAD_IMAGE_ORDER] ?? null;

        return $uploadedImage instanceof UploadedFile ? $uploadedImage : null;
    }

    /**
     * @return array<\Generated\Shared\Transfer\QuickOrderItemTransfer>
     */
    protected function processUploadedImage(UploadedFile $uploadedImage, FormInterface $form): array
    {
        $base64Image = $this->uploadedImageEncoder->encodeToBase64($uploadedImage);

        if ($base64Image === null) {
            $form->get(ImageOrderForm::FIELD_UPLOAD_IMAGE_ORDER)
                ->addError(new FormError(static::GLOSSARY_KEY_IMAGE_READ_FAILED, static::GLOSSARY_KEY_IMAGE_READ_FAILED));

            return [];
        }

        $productRecognitionCollectionTransfer = $this->productImageRecognizer->recognizeProducts($base64Image, (string)$uploadedImage->getMimeType());

        if (!$productRecognitionCollectionTransfer->getIsSuccessful()) {
            $errorGlossaryKey = $productRecognitionCollectionTransfer->getErrorMessage() ?? static::GLOSSARY_KEY_IMAGE_READ_FAILED;

            $form->get(ImageOrderForm::FIELD_UPLOAD_IMAGE_ORDER)->addError(new FormError(
                $errorGlossaryKey,
                $errorGlossaryKey,
            ));

            return [];
        }

        $productValidationTransfer = $this->productRecognitionValidator->validate($productRecognitionCollectionTransfer);

        if (!$productValidationTransfer->getIsValid()) {
            $imageField = $form->get(ImageOrderForm::FIELD_UPLOAD_IMAGE_ORDER);

            foreach ($productValidationTransfer->getValidationErrors() as $error) {
                $imageField->addError(new FormError(
                    $error->getGlossaryKeyOrFail(),
                    $error->getGlossaryKeyOrFail(),
                    $error->getParameters(),
                ));
            }

            return [];
        }

        $quickOrderPageResponseTransfer = $this->quickOrderItemCollector->collectQuickOrderItemsByProductRecognitions($productRecognitionCollectionTransfer);

        if ($quickOrderPageResponseTransfer->getNotFoundProductNames()) {
            $this->notFoundProductNotifier->addErrorNotifications($quickOrderPageResponseTransfer->getNotFoundProductNames());
        }

        return $quickOrderPageResponseTransfer->getQuickOrderItems()->getArrayCopy();
    }
}
