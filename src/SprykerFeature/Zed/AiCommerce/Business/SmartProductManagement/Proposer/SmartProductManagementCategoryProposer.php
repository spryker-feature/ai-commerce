<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Proposer;

use Generated\Shared\Transfer\CategorySuggestionRequestTransfer;
use Generated\Shared\Transfer\CategorySuggestionResponseTransfer;
use Generated\Shared\Transfer\CategorySuggestionStructuredTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor\SmartProductManagementPromptExecutorInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Reader\SmartProductManagementCategoryReaderInterface;

class SmartProductManagementCategoryProposer implements SmartProductManagementCategoryProposerInterface
{
    protected const string OPERATION_NAME = 'category suggestion';

    public function __construct(
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
        protected readonly SmartProductManagementCategoryReaderInterface $categoryReader,
        protected readonly AiCommerceConfig $aiCommerceConfig,
        protected readonly SmartProductManagementPromptExecutorInterface $promptExecutor,
    ) {
    }

    public function proposeCategorySuggestions(
        CategorySuggestionRequestTransfer $categorySuggestionRequestTransfer,
    ): CategorySuggestionResponseTransfer {
        $categorySuggestionResponseTransfer = new CategorySuggestionResponseTransfer();

        $categories = $this->categoryReader->getCategoryIdsIndexedByName();
        if (!count($categories)) {
            return $categorySuggestionResponseTransfer->setIsSuccessful(true);
        }

        $promptRequestTransfer = $this->buildPromptRequest($categorySuggestionRequestTransfer, $categories);

        $promptResponseTransfer = $this->promptExecutor->executePrompt(
            $promptRequestTransfer,
            static::OPERATION_NAME,
        );

        return $this->mapPromptResponseToCategorySuggestionResponse(
            $promptResponseTransfer,
            $categorySuggestionResponseTransfer,
        );
    }

    /**
     * @param array<string, int> $categories
     */
    protected function buildPromptRequest(
        CategorySuggestionRequestTransfer $categorySuggestionRequestTransfer,
        array $categories,
    ): PromptRequestTransfer {
        $promptContent = $this->generatePrompt(
            $categorySuggestionRequestTransfer->getProductNameOrFail(),
            $categorySuggestionRequestTransfer->getProductDescriptionOrFail(),
            $categories,
        );

        $structuredSchema = new CategorySuggestionStructuredTransfer();

        $promptRequestTransfer = (new PromptRequestTransfer())
            ->setPromptMessage(
                (new PromptMessageTransfer())->setContent($promptContent),
            )
            ->setStructuredMessage($structuredSchema)
            ->setMaxRetries($this->aiCommerceConfig->getPromptMaxRetries());

        $aiConfigurationName = $this->aiCommerceConfig->getCategorySuggestionAiConfigurationName();
        if ($aiConfigurationName !== null) {
            $promptRequestTransfer->setAiConfigurationName($aiConfigurationName);
        }

        return $promptRequestTransfer;
    }

    protected function mapPromptResponseToCategorySuggestionResponse(
        PromptResponseTransfer $promptResponseTransfer,
        CategorySuggestionResponseTransfer $categorySuggestionResponseTransfer,
    ): CategorySuggestionResponseTransfer {
        $categorySuggestionResponseTransfer->setIsSuccessful($promptResponseTransfer->getIsSuccessful());

        foreach ($promptResponseTransfer->getErrors() as $errorTransfer) {
            $categorySuggestionResponseTransfer->addError($errorTransfer);
        }

        if (!$promptResponseTransfer->getIsSuccessful()) {
            return $categorySuggestionResponseTransfer;
        }

        $structuredMessage = $promptResponseTransfer->getStructuredMessage();
        if ($structuredMessage instanceof CategorySuggestionStructuredTransfer) {
            foreach ($structuredMessage->getCategories() as $categorySuggestionItemTransfer) {
                $categorySuggestionResponseTransfer->addSuggestion($categorySuggestionItemTransfer);
            }
        }

        return $categorySuggestionResponseTransfer;
    }

    /**
     * @param array<string, int> $categories
     */
    protected function generatePrompt(string $productName, string $description, array $categories): string
    {
        $categoriesJson = $this->utilEncodingService->encodeJson($categories);

        return sprintf(
            $this->aiCommerceConfig->getProductCategorySuggestionPromptTemplate(),
            $productName,
            $description,
            $categoriesJson,
        );
    }
}
