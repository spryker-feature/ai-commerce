<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Improver;

use Generated\Shared\Transfer\ContentImprovementRequestTransfer;
use Generated\Shared\Transfer\ContentImprovementResponseTransfer;

interface SmartProductManagementContentImproverInterface
{
    public function improveContent(
        ContentImprovementRequestTransfer $contentImprovementRequestTransfer,
    ): ContentImprovementResponseTransfer;
}
