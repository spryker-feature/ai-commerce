<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Translator;

use Generated\Shared\Transfer\AiTranslationCollectionRequestTransfer;
use Generated\Shared\Transfer\AiTranslationCollectionResponseTransfer;

interface SmartProductManagementCollectionTranslatorInterface
{
    /**
     * Translates a text to multiple target locales in a single AI request.
     */
    public function translateCollection(
        AiTranslationCollectionRequestTransfer $aiTranslationCollectionRequestTransfer,
    ): AiTranslationCollectionResponseTransfer;
}
