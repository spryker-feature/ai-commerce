<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Generator;

use Generated\Shared\Transfer\SmartCmsContentRequestTransfer;
use Generated\Shared\Transfer\SmartCmsContentResponseTransfer;

interface SmartCmsContentGeneratorInterface
{
    /**
     * Specification:
     * - Generates or rewrites CMS glossary title and content for the requested placeholders and locales using AI.
     * - Uses the editor instruction, current content of all placeholders/locales, and read-only entity context.
     * - Returns the generated content per placeholder and locale together with a short explanation.
     *
     * @api
     */
    public function generateCmsContent(SmartCmsContentRequestTransfer $smartCmsContentRequestTransfer): SmartCmsContentResponseTransfer;
}
