<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Proposer;

use Generated\Shared\Transfer\CategorySuggestionRequestTransfer;
use Generated\Shared\Transfer\CategorySuggestionResponseTransfer;

interface SmartProductManagementCategoryProposerInterface
{
    public function proposeCategorySuggestions(
        CategorySuggestionRequestTransfer $categorySuggestionRequestTransfer,
    ): CategorySuggestionResponseTransfer;
}
