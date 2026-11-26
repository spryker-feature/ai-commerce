<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\Handler;

use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Collector\QuickOrderItemCollectorInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Encoder\UploadedFileEncoderInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\VisualAddToCartForm;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Message\NotFoundProductNotifierInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\ProductRecognizerInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\Validator\ProductRecognitionValidatorInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class VisualAddToCartFormHandler implements VisualAddToCartFormHandlerInterface
{
    protected const string GLOSSARY_KEY_FILE_READ_FAILED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.file-read-failed';

    protected const string GLOSSARY_KEY_AI_REQUEST_FAILED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.ai-request-failed';

    public function __construct(
        protected UploadedFileEncoderInterface $uploadedFileEncoder,
        protected ProductRecognizerInterface $productRecognizer,
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
        if ($request->get(VisualAddToCartForm::SUBMIT_BUTTON_UPLOAD_ORDER_FILE) === null) {
            return [];
        }

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return [];
        }

        $uploadedFile = $this->extractUploadedFile($form);
        if (!$uploadedFile) {
            return [];
        }

        return $this->processUploadedFile($uploadedFile, $form);
    }

    protected function extractUploadedFile(FormInterface $form): ?UploadedFile
    {
        $formData = $form->getData();
        $uploadedFile = $formData[VisualAddToCartForm::FIELD_ORDER_FILE] ?? null;

        return $uploadedFile instanceof UploadedFile ? $uploadedFile : null;
    }

    /**
     * @return array<\Generated\Shared\Transfer\QuickOrderItemTransfer>
     */
    protected function processUploadedFile(UploadedFile $uploadedFile, FormInterface $form): array
    {
        $base64Content = $this->uploadedFileEncoder->encodeToBase64($uploadedFile);

        if ($base64Content === null) {
            $form->get(VisualAddToCartForm::FIELD_ORDER_FILE)
                ->addError(new FormError(static::GLOSSARY_KEY_FILE_READ_FAILED, static::GLOSSARY_KEY_FILE_READ_FAILED));

            return [];
        }

        $productRecognitionCollectionTransfer = $this->productRecognizer->recognizeProducts(
            $base64Content,
            (string)$uploadedFile->getMimeType(),
            $uploadedFile->guessExtension(),
        );

        if (!$productRecognitionCollectionTransfer->getIsSuccessful()) {
            $errorGlossaryKey = $productRecognitionCollectionTransfer->getErrorMessage() ?? static::GLOSSARY_KEY_AI_REQUEST_FAILED;

            $form->get(VisualAddToCartForm::FIELD_ORDER_FILE)->addError(new FormError(
                $errorGlossaryKey,
                $errorGlossaryKey,
            ));

            return [];
        }

        $productValidationTransfer = $this->productRecognitionValidator->validate($productRecognitionCollectionTransfer);

        if (!$productValidationTransfer->getIsValid()) {
            $fileField = $form->get(VisualAddToCartForm::FIELD_ORDER_FILE);

            foreach ($productValidationTransfer->getValidationErrors() as $error) {
                $fileField->addError(new FormError(
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
