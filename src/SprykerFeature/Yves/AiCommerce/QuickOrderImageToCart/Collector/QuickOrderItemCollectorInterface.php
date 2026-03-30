<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Collector;

use Generated\Shared\Transfer\ProductRecognitionCollectionTransfer;
use Generated\Shared\Transfer\QuickOrderPageResponseTransfer;

interface QuickOrderItemCollectorInterface
{
    public function collectQuickOrderItemsByProductRecognitions(
        ProductRecognitionCollectionTransfer $productRecognitionCollectionTransfer,
    ): QuickOrderPageResponseTransfer;
}
