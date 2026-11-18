<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Deleter;

use Generated\Shared\Transfer\KnowledgeDocumentIndexResponseTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentTransfer;

interface KnowledgeDocumentDeleterInterface
{
    public function deleteDocument(KnowledgeDocumentTransfer $knowledgeDocumentTransfer): KnowledgeDocumentIndexResponseTransfer;
}
