<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartCmsContent\Expander;

use Generated\Shared\Transfer\SmartCmsContentResponseTransfer;

interface SmartCmsContentItemHtmlExpanderInterface
{
    /**
     * Specification:
     * - Converts bare content item Twig expressions (e.g. `{{ content_banner('key', 'template') }}`) in each
     *   generated placeholder translation into the rich editor widget markup the glossary editor produces on insert.
     * - Leaves content widget calls and plain HTML untouched.
     * - The conversion is per `SmartCmsContentResponse.placeholders` translation content, branching on `$entityType`.
     */
    public function expandPlaceholderContent(
        SmartCmsContentResponseTransfer $smartCmsContentResponseTransfer,
        string $entityType,
    ): SmartCmsContentResponseTransfer;
}
