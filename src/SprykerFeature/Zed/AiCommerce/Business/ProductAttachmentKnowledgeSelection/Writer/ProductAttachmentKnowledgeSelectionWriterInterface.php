<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\ProductAttachmentKnowledgeSelection\Writer;

use Generated\Shared\Transfer\ProductAttachmentCollectionTransfer;

interface ProductAttachmentKnowledgeSelectionWriterInterface
{
    public function saveProductAttachmentKnowledgeSelections(
        ProductAttachmentCollectionTransfer $productAttachmentCollectionTransfer,
    ): ProductAttachmentCollectionTransfer;
}
