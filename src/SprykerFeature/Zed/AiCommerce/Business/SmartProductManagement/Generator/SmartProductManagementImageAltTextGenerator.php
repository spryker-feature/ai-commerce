<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Generator;

use Generated\Shared\Transfer\AttachmentTransfer;
use Generated\Shared\Transfer\ImageAltTextRequestTransfer;
use Generated\Shared\Transfer\ImageAltTextResponseTransfer;
use Generated\Shared\Transfer\ImageAltTextStructuredTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor\SmartProductManagementPromptExecutorInterface;

class SmartProductManagementImageAltTextGenerator implements SmartProductManagementImageAltTextGeneratorInterface
{
    protected const string OPERATION_NAME = 'image alt text generation';

    public function __construct(
        protected readonly AiCommerceConfig $aiCommerceConfig,
        protected readonly SmartProductManagementPromptExecutorInterface $promptExecutor,
    ) {
    }

    public function generateImageAltText(
        ImageAltTextRequestTransfer $imageAltTextRequestTransfer,
    ): ImageAltTextResponseTransfer {
        $promptRequestTransfer = $this->buildPromptRequest($imageAltTextRequestTransfer);

        $promptResponseTransfer = $this->promptExecutor->executePrompt(
            $promptRequestTransfer,
            static::OPERATION_NAME,
        );

        return $this->mapPromptResponseToImageAltTextResponse(
            $promptResponseTransfer,
            new ImageAltTextResponseTransfer(),
        );
    }

    protected function buildPromptRequest(ImageAltTextRequestTransfer $imageAltTextRequestTransfer): PromptRequestTransfer
    {
        $promptContent = $this->aiCommerceConfig->getImageAltTextPrompt(
            $imageAltTextRequestTransfer->getTargetLocaleOrFail(),
        );

        $structuredSchema = new ImageAltTextStructuredTransfer();

        $promptRequestTransfer = (new PromptRequestTransfer())
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setContent($promptContent)
                    ->addAttachment(
                        (new AttachmentTransfer())
                            ->setType(AiFoundationConstants::ATTACHMENT_TYPE_IMAGE)
                            ->setContentType(AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_URL)
                            ->setContent($imageAltTextRequestTransfer->getImageUrlOrFail()),
                    ),
            )
            ->setStructuredMessage($structuredSchema)
            ->setMaxRetries($this->aiCommerceConfig->getPromptMaxRetries());

        $aiConfigurationName = $this->aiCommerceConfig->getImageAltTextAiConfigurationName();
        if ($aiConfigurationName !== null) {
            $promptRequestTransfer->setAiConfigurationName($aiConfigurationName);
        }

        return $promptRequestTransfer;
    }

    protected function mapPromptResponseToImageAltTextResponse(
        PromptResponseTransfer $promptResponseTransfer,
        ImageAltTextResponseTransfer $imageAltTextResponseTransfer,
    ): ImageAltTextResponseTransfer {
        $imageAltTextResponseTransfer->setIsSuccessful($promptResponseTransfer->getIsSuccessful());

        foreach ($promptResponseTransfer->getErrors() as $errorTransfer) {
            $imageAltTextResponseTransfer->addError($errorTransfer);
        }

        if (!$promptResponseTransfer->getIsSuccessful()) {
            return $imageAltTextResponseTransfer;
        }

        $structuredMessage = $promptResponseTransfer->getStructuredMessage();
        if ($structuredMessage instanceof ImageAltTextStructuredTransfer) {
            $imageAltTextResponseTransfer->setAltText($structuredMessage->getAltText());
        }

        return $imageAltTextResponseTransfer;
    }
}
