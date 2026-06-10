<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\SmartCmsContentItemTransfer;
use Orm\Zed\Content\Persistence\SpyContent;

class SmartCmsContentItemMapper
{
    public function mapContentEntityToSmartCmsContentItemTransfer(
        SpyContent $contentEntity,
        SmartCmsContentItemTransfer $smartCmsContentItemTransfer,
    ): SmartCmsContentItemTransfer {
        return $smartCmsContentItemTransfer
            ->setKey($contentEntity->getKey())
            ->setName($contentEntity->getName())
            ->setContentTypeKey($contentEntity->getContentTypeKey());
    }
}
