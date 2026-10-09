<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent;

use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\PageContext\PageContextProductResolverInterface;

class ProductDiscoveryChatRequestExpander implements ProductDiscoveryChatRequestExpanderInterface
{
    public function __construct(
        protected LocaleClientInterface $localeClient,
        protected PageContextProductResolverInterface $pageContextProductResolver
    ) {
    }

    public function expand(
        StorefrontAssistantChatRequestTransfer $storefrontAssistantChatRequestTransfer
    ): StorefrontAssistantChatRequestTransfer {
        if ((string)$storefrontAssistantChatRequestTransfer->getLocaleName() === '') {
            $storefrontAssistantChatRequestTransfer->setLocaleName($this->localeClient->getCurrentLocale());
        }

        $storefrontAssistantPageContextTransfer = $storefrontAssistantChatRequestTransfer->getStorefrontAssistantPageContext();

        if ($storefrontAssistantPageContextTransfer === null) {
            return $storefrontAssistantChatRequestTransfer;
        }

        $this->pageContextProductResolver->expandPageContext(
            $storefrontAssistantPageContextTransfer,
            $storefrontAssistantChatRequestTransfer->getLocaleNameOrFail(),
        );

        return $storefrontAssistantChatRequestTransfer;
    }
}
