<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Agent;

use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;

class ProductDiscoveryToolNameResolver implements ProductDiscoveryToolNameResolverInterface
{
    /**
     * @param list<string> $toolNames
     *
     * @return list<string>
     */
    public function resolveToolNames(array $toolNames, ?string $inlineCategoryTree): array
    {
        if ($inlineCategoryTree === null) {
            return $toolNames;
        }

        return array_values(array_diff($toolNames, [CategoryTreeToolPlugin::TOOL_NAME]));
    }
}
