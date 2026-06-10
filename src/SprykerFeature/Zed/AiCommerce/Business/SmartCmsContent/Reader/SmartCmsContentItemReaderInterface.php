<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Reader;

use Generated\Shared\Transfer\SmartCmsContentItemCollectionTransfer;
use Generated\Shared\Transfer\SmartCmsContentItemCriteriaTransfer;

interface SmartCmsContentItemReaderInterface
{
    /**
     * Specification:
     * - Returns existing CMS content items, optionally filtered by content type, enriched with the
     *   available templates and Twig function template resolved from the registered content editor plugins.
     */
    public function getContentItems(
        SmartCmsContentItemCriteriaTransfer $smartCmsContentItemCriteriaTransfer,
    ): SmartCmsContentItemCollectionTransfer;
}
