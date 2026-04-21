<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Improver;

use Generated\Shared\Transfer\ContentImprovementRequestTransfer;
use Generated\Shared\Transfer\ContentImprovementResponseTransfer;
use Generated\Shared\Transfer\ContentImprovementStructuredTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor\SmartProductManagementPromptExecutorInterface;

class SmartProductManagementContentImprover implements SmartProductManagementContentImproverInterface
{
    protected const string OPERATION_NAME = 'content improvement';

    public function __construct(
        protected readonly AiCommerceConfig $aiCommerceConfig,
        protected readonly SmartProductManagementPromptExecutorInterface $promptExecutor,
    ) {
    }

    public function improveContent(
        ContentImprovementRequestTransfer $contentImprovementRequestTransfer,
    ): ContentImprovementResponseTransfer {
        $promptRequestTransfer = $this->buildPromptRequest($contentImprovementRequestTransfer);

        $promptResponseTransfer = $this->promptExecutor->executePrompt(
            $promptRequestTransfer,
            static::OPERATION_NAME,
        );

        return $this->mapPromptResponseToContentImprovementResponse(
            $promptResponseTransfer,
            $contentImprovementRequestTransfer,
        );
    }

    protected function buildPromptRequest(ContentImprovementRequestTransfer $contentImprovementRequestTransfer): PromptRequestTransfer
    {
        $promptContent = $this->buildContentImprovementPrompt($contentImprovementRequestTransfer);
        $structuredSchema = new ContentImprovementStructuredTransfer();

        $promptRequestTransfer = (new PromptRequestTransfer())
            ->setPromptMessage(
                (new PromptMessageTransfer())->setContent($promptContent),
            )
            ->setStructuredMessage($structuredSchema)
            ->setMaxRetries($this->aiCommerceConfig->getPromptMaxRetries());

        $aiConfigurationName = $this->aiCommerceConfig->getContentImproverAiConfigurationName();
        if ($aiConfigurationName !== null) {
            $promptRequestTransfer->setAiConfigurationName($aiConfigurationName);
        }

        return $promptRequestTransfer;
    }

    protected function buildContentImprovementPrompt(ContentImprovementRequestTransfer $contentImprovementRequestTransfer): string
    {
        return sprintf(
            $this->aiCommerceConfig->getContentImproverPromptTemplate(),
            $contentImprovementRequestTransfer->getTextOrFail(),
        );
    }

    protected function mapPromptResponseToContentImprovementResponse(
        PromptResponseTransfer $promptResponseTransfer,
        ContentImprovementRequestTransfer $contentImprovementRequestTransfer,
    ): ContentImprovementResponseTransfer {
        $contentImprovementResponseTransfer = (new ContentImprovementResponseTransfer())
            ->setOriginalText($contentImprovementRequestTransfer->getTextOrFail())
            ->setIsSuccessful($promptResponseTransfer->getIsSuccessful());

        foreach ($promptResponseTransfer->getErrors() as $errorTransfer) {
            $contentImprovementResponseTransfer->addError($errorTransfer);
        }

        if (!$promptResponseTransfer->getIsSuccessful()) {
            return $contentImprovementResponseTransfer;
        }

        $structuredMessage = $promptResponseTransfer->getStructuredMessage();
        if ($structuredMessage instanceof ContentImprovementStructuredTransfer) {
            $contentImprovementResponseTransfer->setImprovedText($structuredMessage->getImprovedText());
        }

        return $contentImprovementResponseTransfer;
    }
}
