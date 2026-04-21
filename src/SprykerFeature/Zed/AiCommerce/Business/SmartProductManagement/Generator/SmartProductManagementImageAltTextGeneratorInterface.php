<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Generator;

use Generated\Shared\Transfer\ImageAltTextRequestTransfer;
use Generated\Shared\Transfer\ImageAltTextResponseTransfer;

interface SmartProductManagementImageAltTextGeneratorInterface
{
    public function generateImageAltText(
        ImageAltTextRequestTransfer $imageAltTextRequestTransfer,
    ): ImageAltTextResponseTransfer;
}
