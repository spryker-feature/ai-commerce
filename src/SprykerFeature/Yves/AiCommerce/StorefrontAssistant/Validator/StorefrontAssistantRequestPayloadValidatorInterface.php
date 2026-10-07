<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Validator;

use Symfony\Component\HttpFoundation\Request;

interface StorefrontAssistantRequestPayloadValidatorInterface
{
    public function isRequestPayloadValid(Request $request): bool;
}
