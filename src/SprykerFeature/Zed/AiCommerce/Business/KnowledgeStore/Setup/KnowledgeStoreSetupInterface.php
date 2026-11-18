<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeStore\Setup;

use Generated\Shared\Transfer\KnowledgeStoreSetupRequestTransfer;
use Generated\Shared\Transfer\KnowledgeStoreSetupResponseTransfer;

interface KnowledgeStoreSetupInterface
{
    public function setupKnowledgeStore(KnowledgeStoreSetupRequestTransfer $knowledgeStoreSetupRequestTransfer): KnowledgeStoreSetupResponseTransfer;
}
