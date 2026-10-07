<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Sanitizer;

interface StorefrontAssistantPageContextSanitizerInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function sanitizePageContext(mixed $pageContext): ?array;
}
