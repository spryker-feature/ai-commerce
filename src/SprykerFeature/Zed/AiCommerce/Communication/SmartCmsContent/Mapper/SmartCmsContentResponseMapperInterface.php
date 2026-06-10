<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper;

use Generated\Shared\Transfer\SmartCmsContentResponseTransfer;

interface SmartCmsContentResponseMapperInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapSmartCmsContentResponseTransferToPlaceholderArray(
        SmartCmsContentResponseTransfer $smartCmsContentResponseTransfer,
    ): array;
}
