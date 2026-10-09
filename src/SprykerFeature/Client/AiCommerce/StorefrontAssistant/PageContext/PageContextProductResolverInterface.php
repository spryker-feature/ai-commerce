<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\PageContext;

use Generated\Shared\Transfer\StorefrontAssistantPageContextTransfer;

interface PageContextProductResolverInterface
{
    public function expandPageContext(
        StorefrontAssistantPageContextTransfer $storefrontAssistantPageContextTransfer,
        string $localeName
    ): StorefrontAssistantPageContextTransfer;
}
