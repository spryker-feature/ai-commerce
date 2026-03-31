<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\SearchByImage\Executor;

use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;

interface SearchByImageExecutorInterface
{
    public function execute(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer;
}
