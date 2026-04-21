<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Reader;

interface SmartProductManagementCategoryReaderInterface
{
    /**
     * @return array<string, int>
     */
    public function getCategoryIdsIndexedByName(): array;
}
