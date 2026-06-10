<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\SmartCmsContent\Mapper;

use Generated\Shared\Transfer\SmartCmsContentRequestTransfer;

interface SmartCmsContentRequestMapperInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function mapPayloadToSmartCmsContentRequestTransfer(array $payload): SmartCmsContentRequestTransfer;
}
