<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AiCommerce;

interface AiCommerceServiceInterface
{
    /**
     * Specification:
     * - Generates a server-side unique conversation reference in format: userReference:timestamp:random.
     *
     * @api
     */
    public function generateConversationReference(string $userReference): string;
}
