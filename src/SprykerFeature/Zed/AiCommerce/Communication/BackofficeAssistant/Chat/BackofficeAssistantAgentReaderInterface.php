<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Communication\BackofficeAssistant\Chat;

interface BackofficeAssistantAgentReaderInterface
{
    /**
     * @return list<array<string, string>>
     */
    public function getAvailableAgents(): array;
}
