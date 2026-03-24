<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Prompt;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;

interface BackofficeAssistantPromptRequestValidatorInterface
{
    /**
     * @return array<string> List of translatable validation error messages.
     */
    public function validate(BackofficeAssistantPromptRequestTransfer $promptRequestTransfer): array;
}
