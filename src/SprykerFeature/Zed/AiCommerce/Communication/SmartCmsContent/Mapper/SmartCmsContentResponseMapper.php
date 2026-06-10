<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper;

use Generated\Shared\Transfer\SmartCmsContentResponseTransfer;

class SmartCmsContentResponseMapper implements SmartCmsContentResponseMapperInterface
{
    protected const string KEY_PLACEHOLDER = 'placeholder';

    protected const string KEY_TRANSLATIONS = 'translations';

    protected const string KEY_LOCALE_NAME = 'localeName';

    protected const string KEY_CONTENT = 'content';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapSmartCmsContentResponseTransferToPlaceholderArray(
        SmartCmsContentResponseTransfer $smartCmsContentResponseTransfer,
    ): array {
        $placeholders = [];

        foreach ($smartCmsContentResponseTransfer->getPlaceholders() as $smartCmsContentPlaceholderTransfer) {
            $translations = [];

            foreach ($smartCmsContentPlaceholderTransfer->getTranslations() as $smartCmsContentTranslationTransfer) {
                $translations[] = [
                    static::KEY_LOCALE_NAME => $smartCmsContentTranslationTransfer->getLocaleName(),
                    static::KEY_CONTENT => $smartCmsContentTranslationTransfer->getContent(),
                ];
            }

            $placeholders[] = [
                static::KEY_PLACEHOLDER => $smartCmsContentPlaceholderTransfer->getPlaceholder(),
                static::KEY_TRANSLATIONS => $translations,
            ];
        }

        return $placeholders;
    }
}
