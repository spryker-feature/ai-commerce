<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Translation;

use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;

class StorefrontAssistantTranslator implements StorefrontAssistantTranslatorInterface
{
    public function __construct(
        protected GlossaryStorageClientInterface $glossaryStorageClient,
        protected LocaleClientInterface $localeClient
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function translate(string $glossaryKey, ?string $localeName = null, array $parameters = []): string
    {
        $localeName = trim((string)$localeName);

        if ($localeName === '') {
            $localeName = $this->localeClient->getCurrentLocale();
        }

        return (string)$this->glossaryStorageClient->translate($glossaryKey, $localeName, $parameters);
    }
}
