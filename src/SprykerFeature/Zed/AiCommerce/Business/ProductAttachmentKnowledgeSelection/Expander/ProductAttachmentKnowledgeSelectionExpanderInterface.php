<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Expander;

use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;

interface ProductAttachmentKnowledgeSelectionExpanderInterface
{
    public function expandProductAttachmentCollection(
        ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer,
    ): ProductAttachmentCollectionTransfer;
}
