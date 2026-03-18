<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\AiCommerce\BackofficeAssistant\Generator;

interface ConversationReferenceGeneratorInterface
{
    /**
     * Specification:
     * - Generates a unique conversation reference string for the given user reference.
     */
    public function generate(string $userReference): string;
}
