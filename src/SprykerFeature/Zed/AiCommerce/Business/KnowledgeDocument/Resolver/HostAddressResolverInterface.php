<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver;

interface HostAddressResolverInterface
{
    /**
     * @return list<string>
     */
    public function resolveHostAddresses(string $host): array;
}
