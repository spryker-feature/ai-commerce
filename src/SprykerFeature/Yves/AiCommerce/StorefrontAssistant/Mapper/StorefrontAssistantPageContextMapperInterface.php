<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantPageContextTransfer;

interface StorefrontAssistantPageContextMapperInterface
{
    /**
     * @param array<string, mixed> $pageContext
     */
    public function mapPageContextToStorefrontAssistantPageContextTransfer(array $pageContext): StorefrontAssistantPageContextTransfer;
}
