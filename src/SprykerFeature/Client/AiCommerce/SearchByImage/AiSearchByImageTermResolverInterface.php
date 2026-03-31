<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce\SearchByImage;

use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;

interface AiSearchByImageTermResolverInterface
{
    public function getSearchTermFromImage(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer;
}
