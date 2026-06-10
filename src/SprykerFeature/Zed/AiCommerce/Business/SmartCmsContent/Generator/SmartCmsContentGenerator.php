<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Generator;

use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Generated\Shared\Transfer\SmartCmsContentPlaceholderTransfer;
use Generated\Shared\Transfer\SmartCmsContentRequestTransfer;
use Generated\Shared\Transfer\SmartCmsContentResponseTransfer;
use Generated\Shared\Transfer\SmartCmsContentStructuredPlaceholderTransfer;
use Generated\Shared\Transfer\SmartCmsContentStructuredTransfer;
use Generated\Shared\Transfer\SmartCmsContentTranslationTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Expander\SmartCmsContentItemHtmlExpanderInterface;
use SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Executor\SmartProductManagementPromptExecutorInterface;

class SmartCmsContentGenerator implements SmartCmsContentGeneratorInterface
{
    protected const string OPERATION_NAME = 'cms content generation';

    public function __construct(
        protected readonly AiCommerceConfig $aiCommerceConfig,
        protected readonly SmartProductManagementPromptExecutorInterface $promptExecutor,
        protected readonly SmartCmsContentItemHtmlExpanderInterface $smartCmsContentItemHtmlExpander,
    ) {
    }

    public function generateCmsContent(SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer): SmartCmsContentResponseTransfer
    {
        $promptRequestTransfer = $this->buildPromptRequest($smartCmsContentRequestTransfer);

        $promptResponseTransfer = $this->promptExecutor->executePrompt(
            $promptRequestTransfer,
            static::OPERATION_NAME,
        );

        $smartCmsContentResponseTransfer = $this->mapPromptResponseToSmartCmsContentResponse($promptResponseTransfer);

        if (!$smartCmsContentResponseTransfer->getIsSuccessful()) {
            return $smartCmsContentResponseTransfer;
        }

        // Render bare content item Twig calls as the rich editor widgets the glossary toolbar produces on insert.
        return $this->smartCmsContentItemHtmlExpander->expandPlaceholderContent(
            $smartCmsContentResponseTransfer,
            (string)$smartCmsContentRequestTransfer->getEntityType(),
        );
    }

    protected function buildPromptRequest(SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer): PromptRequestTransfer
    {
        $promptContent = $this->buildPromptContent($smartCmsContentRequestTransfer);

        $promptMessageTransfer = (new PromptMessageTransfer())->setContent($promptContent);

        foreach ($smartCmsContentRequestTransfer->getAttachments() as $attachmentTransfer) {
            $promptMessageTransfer->addAttachment($attachmentTransfer);
        }

        $promptRequestTransfer = (new PromptRequestTransfer())
            ->setPromptMessage($promptMessageTransfer)
            ->setStructuredMessage(new SmartCmsContentStructuredTransfer())
            ->setMaxRetries($this->aiCommerceConfig->getPromptMaxRetries())
            ->setToolSetNames($this->aiCommerceConfig->getSmartCmsToolSetNames());

        $aiConfigurationName = $this->aiCommerceConfig->getSmartCmsAiConfigurationName();
        if ($aiConfigurationName !== null) {
            $promptRequestTransfer->setAiConfigurationName($aiConfigurationName);
        }

        return $promptRequestTransfer;
    }

    protected function buildPromptContent(SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer): string
    {
        // The configurable system prompt is injected by AiFoundation via the AI configuration.
        // Here we only pass the request data the model needs to act on.
        $context = [
            'instruction' => $smartCmsContentRequestTransfer->getUserPrompt(),
            'entityType' => $smartCmsContentRequestTransfer->getEntityType(),
            'entityContext' => $smartCmsContentRequestTransfer->getEntityContext()?->modifiedToArray(),
            'currentPlaceholders' => $this->mapPlaceholdersToArray($smartCmsContentRequestTransfer),
            'availableContentWidgets' => $this->mapAvailableContentWidgetsToArray($smartCmsContentRequestTransfer),
        ];

        return sprintf(
            "Request data (JSON):\n%s",
            (string)json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapPlaceholdersToArray(SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer): array
    {
        $placeholders = [];

        foreach ($smartCmsContentRequestTransfer->getPlaceholders() as $smartCmsContentPlaceholderTransfer) {
            $translations = [];

            foreach ($smartCmsContentPlaceholderTransfer->getTranslations() as $smartCmsContentTranslationTransfer) {
                $translations[] = [
                    'localeName' => $smartCmsContentTranslationTransfer->getLocaleName(),
                    'content' => $smartCmsContentTranslationTransfer->getContent(),
                ];
            }

            $placeholders[] = [
                'placeholder' => $smartCmsContentPlaceholderTransfer->getPlaceholder(),
                'translations' => $translations,
            ];
        }

        return $placeholders;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapAvailableContentWidgetsToArray(SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer): array
    {
        $contentWidgets = [];

        foreach ($smartCmsContentRequestTransfer->getAvailableContentWidgets() as $smartCmsContentWidgetTransfer) {
            $contentWidgets[] = [
                'functionName' => $smartCmsContentWidgetTransfer->getFunctionName(),
                'usageInformation' => $smartCmsContentWidgetTransfer->getUsageInformation(),
                'templates' => $smartCmsContentWidgetTransfer->getTemplates(),
            ];
        }

        return $contentWidgets;
    }

    protected function mapPromptResponseToSmartCmsContentResponse(PromptResponseTransfer $promptResponseTransfer): SmartCmsContentResponseTransfer
    {
        $smartCmsContentResponseTransfer = (new SmartCmsContentResponseTransfer())
            ->setIsSuccessful($promptResponseTransfer->getIsSuccessful());

        foreach ($promptResponseTransfer->getErrors() as $errorTransfer) {
            $smartCmsContentResponseTransfer->addError($errorTransfer);
        }

        if (!$promptResponseTransfer->getIsSuccessful()) {
            return $smartCmsContentResponseTransfer;
        }

        $structuredMessage = $promptResponseTransfer->getStructuredMessage();
        if (!$structuredMessage instanceof SmartCmsContentStructuredTransfer) {
            return $smartCmsContentResponseTransfer;
        }

        $smartCmsContentResponseTransfer->setExplanation($structuredMessage->getExplanation());

        foreach ($structuredMessage->getPlaceholders() as $smartCmsContentStructuredPlaceholderTransfer) {
            $smartCmsContentResponseTransfer->addPlaceholder(
                $this->mapStructuredPlaceholderToPlaceholder($smartCmsContentStructuredPlaceholderTransfer),
            );
        }

        return $smartCmsContentResponseTransfer;
    }

    protected function mapStructuredPlaceholderToPlaceholder(
        SmartCmsContentStructuredPlaceholderTransfer $smartCmsContentStructuredPlaceholderTransfer,
    ): SmartCmsContentPlaceholderTransfer {
        $smartCmsContentPlaceholderTransfer = (new SmartCmsContentPlaceholderTransfer())
            ->setPlaceholder($smartCmsContentStructuredPlaceholderTransfer->getPlaceholder());

        foreach ($smartCmsContentStructuredPlaceholderTransfer->getTranslations() as $smartCmsContentStructuredTranslationTransfer) {
            $smartCmsContentPlaceholderTransfer->addTranslation(
                (new SmartCmsContentTranslationTransfer())
                    ->setLocaleName($smartCmsContentStructuredTranslationTransfer->getLocaleName())
                    ->setContent($smartCmsContentStructuredTranslationTransfer->getContent()),
            );
        }

        return $smartCmsContentPlaceholderTransfer;
    }
}
