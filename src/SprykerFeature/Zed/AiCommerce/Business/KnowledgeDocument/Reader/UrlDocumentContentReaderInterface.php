<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader;

use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;

interface UrlDocumentContentReaderInterface
{
    public function readDocumentContent(
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer,
    ): KnowledgeDocumentContentTransfer;
}
