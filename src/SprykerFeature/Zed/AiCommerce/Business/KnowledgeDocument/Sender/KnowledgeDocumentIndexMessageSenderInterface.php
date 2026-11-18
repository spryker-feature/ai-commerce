<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Sender;

interface KnowledgeDocumentIndexMessageSenderInterface
{
    /**
     * @param list<\Generated\Shared\Transfer\KnowledgeDocumentTransfer> $knowledgeDocumentTransfers
     */
    public function sendIndexMessages(array $knowledgeDocumentTransfers): int;
}
