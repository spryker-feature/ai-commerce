<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Translator;

use Generated\Shared\Transfer\AiTranslationCollectionRequestTransfer;
use Generated\Shared\Transfer\AiTranslationCollectionResponseTransfer;
use Generated\Shared\Transfer\AiTranslationCollectionStructuredTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor\SmartProductManagementPromptExecutorInterface;

class SmartProductManagementCollectionTranslator implements SmartProductManagementCollectionTranslatorInterface
{
    protected const string OPERATION_NAME = 'translation-collection';

    protected const string DEFAULT_SOURCE_LOCALE = '(detect the source language automatically)';

    public function __construct(
        protected readonly AiCommerceConfig $aiCommerceConfig,
        protected readonly SmartProductManagementPromptExecutorInterface $promptExecutor,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function translateCollection(
        AiTranslationCollectionRequestTransfer $aiTranslationCollectionRequestTransfer,
    ): AiTranslationCollectionResponseTransfer {
        $promptRequestTransfer = $this->buildPromptRequest($aiTranslationCollectionRequestTransfer);

        $promptResponseTransfer = $this->promptExecutor->executePrompt(
            $promptRequestTransfer,
            static::OPERATION_NAME,
        );

        return $this->mapPromptResponseToCollectionResponse($promptResponseTransfer);
    }

    protected function buildPromptRequest(AiTranslationCollectionRequestTransfer $requestCollectionTransfer): PromptRequestTransfer
    {
        $promptContent = $this->buildPrompt($requestCollectionTransfer);
        $structuredSchema = new AiTranslationCollectionStructuredTransfer();

        $promptRequestTransfer = (new PromptRequestTransfer())
            ->setPromptMessage(
                (new PromptMessageTransfer())->setContent($promptContent),
            )
            ->setStructuredMessage($structuredSchema)
            ->setMaxRetries($this->aiCommerceConfig->getPromptMaxRetries());

        $aiConfigurationName = $this->aiCommerceConfig->getTranslationAiConfigurationName();
        if ($aiConfigurationName !== null) {
            $promptRequestTransfer->setAiConfigurationName($aiConfigurationName);
        }

        return $promptRequestTransfer;
    }

    protected function buildPrompt(AiTranslationCollectionRequestTransfer $requestCollectionTransfer): string
    {
        $targetLocales = implode(', ', $requestCollectionTransfer->getTargetLocales());

        return sprintf(
            $this->aiCommerceConfig->getAiTranslationCollectionPromptTemplate(),
            $requestCollectionTransfer->getTextOrFail(),
            $requestCollectionTransfer->getSourceLocale() ?? static::DEFAULT_SOURCE_LOCALE,
            $targetLocales,
        );
    }

    protected function mapPromptResponseToCollectionResponse(PromptResponseTransfer $promptResponseTransfer): AiTranslationCollectionResponseTransfer
    {
        $responseCollectionTransfer = (new AiTranslationCollectionResponseTransfer())
            ->setIsSuccessful($promptResponseTransfer->getIsSuccessful());

        foreach ($promptResponseTransfer->getErrors() as $errorTransfer) {
            $responseCollectionTransfer->addError($errorTransfer);
        }

        if (!$promptResponseTransfer->getIsSuccessful()) {
            return $responseCollectionTransfer;
        }

        $structuredMessage = $promptResponseTransfer->getStructuredMessage();
        if (!$structuredMessage instanceof AiTranslationCollectionStructuredTransfer) {
            return $responseCollectionTransfer;
        }

        $translations = [];

        foreach ($structuredMessage->getTranslations() as $translationItemTransfer) {
            $translations[$translationItemTransfer->getLocaleOrFail()] = $translationItemTransfer->getTranslationOrFail();
        }

        $responseCollectionTransfer->setTranslations($translations);

        return $responseCollectionTransfer;
    }
}
