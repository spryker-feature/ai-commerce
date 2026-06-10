<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper;

use Generated\Shared\Transfer\SmartCmsContentEntityContextTransfer;
use Generated\Shared\Transfer\SmartCmsContentPlaceholderTransfer;
use Generated\Shared\Transfer\SmartCmsContentRequestTransfer;
use Generated\Shared\Transfer\SmartCmsContentTranslationTransfer;
use Generated\Shared\Transfer\SmartCmsContentWidgetTransfer;

class SmartCmsContentRequestMapper implements SmartCmsContentRequestMapperInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function mapPayloadToSmartCmsContentRequestTransfer(array $payload): SmartCmsContentRequestTransfer
    {
        $smartCmsContentRequestTransfer = (new SmartCmsContentRequestTransfer())
            ->setUserPrompt((string)($payload['userPrompt'] ?? ''))
            ->setEntityType((string)($payload['entityType'] ?? ''))
            ->setIdEntity((int)($payload['idEntity'] ?? 0))
            ->setEntityContext($this->mapEntityContext($payload['entityContext'] ?? []));

        foreach ($this->extractArray($payload, 'placeholders') as $placeholder) {
            if (!is_array($placeholder)) {
                continue;
            }

            $smartCmsContentRequestTransfer->addPlaceholder($this->mapPlaceholder($placeholder));
        }

        foreach ($this->extractArray($payload, 'availableContentWidgets') as $contentWidget) {
            if (!is_array($contentWidget)) {
                continue;
            }

            $smartCmsContentRequestTransfer->addAvailableContentWidget($this->mapContentWidget($contentWidget));
        }

        return $smartCmsContentRequestTransfer;
    }

    /**
     * @param array<string, mixed> $contentWidget
     */
    protected function mapContentWidget(array $contentWidget): SmartCmsContentWidgetTransfer
    {
        return (new SmartCmsContentWidgetTransfer())
            ->setFunctionName((string)($contentWidget['functionName'] ?? ''))
            ->setUsageInformation((string)($contentWidget['usageInformation'] ?? ''))
            ->setTemplates($this->extractStringList($contentWidget, 'templates'));
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<int, string>
     */
    protected function extractStringList(array $source, string $key): array
    {
        $value = $source[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        $stringList = [];

        foreach ($value as $item) {
            $stringList[] = (string)$item;
        }

        return $stringList;
    }

    /**
     * @param array<string, mixed> $entityContext
     */
    protected function mapEntityContext(array $entityContext): SmartCmsContentEntityContextTransfer
    {
        return (new SmartCmsContentEntityContextTransfer())
            ->setName($this->stringOrNull($entityContext, 'name'))
            ->setTemplateName($this->stringOrNull($entityContext, 'templateName'))
            ->setUrlSlug($this->stringOrNull($entityContext, 'urlSlug'))
            ->setKey($this->stringOrNull($entityContext, 'key'))
            ->setAttributes($this->stringOrNull($entityContext, 'attributes'));
    }

    /**
     * @param array<string, mixed> $placeholder
     */
    protected function mapPlaceholder(array $placeholder): SmartCmsContentPlaceholderTransfer
    {
        $smartCmsContentPlaceholderTransfer = (new SmartCmsContentPlaceholderTransfer())
            ->setPlaceholder((string)($placeholder['placeholder'] ?? ''));

        foreach ($this->extractArray($placeholder, 'translations') as $translation) {
            if (!is_array($translation)) {
                continue;
            }

            $smartCmsContentPlaceholderTransfer->addTranslation(
                (new SmartCmsContentTranslationTransfer())
                    ->setLocaleName((string)($translation['localeName'] ?? ''))
                    ->setContent((string)($translation['content'] ?? '')),
            );
        }

        return $smartCmsContentPlaceholderTransfer;
    }

    /**
     * The payload is untrusted, so element values are not guaranteed to be arrays; callers must guard each element before mapping it.
     *
     * @param array<string, mixed> $source
     *
     * @return array<mixed>
     */
    protected function extractArray(array $source, string $key): array
    {
        $value = $source[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $source
     */
    protected function stringOrNull(array $source, string $key): ?string
    {
        return isset($source[$key]) ? (string)$source[$key] : null;
    }
}
