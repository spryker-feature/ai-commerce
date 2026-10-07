<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Reader;

use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Validator\StorefrontAssistantRequestPayloadValidatorInterface;
use Symfony\Component\HttpFoundation\Request;

class StorefrontAssistantRequestPayloadReader implements StorefrontAssistantRequestPayloadReaderInterface
{
    public function __construct(
        protected StorefrontAssistantRequestPayloadValidatorInterface $storefrontAssistantRequestPayloadValidator
    ) {
    }

    /**
     * @return array<mixed>|null
     */
    public function findRequestPayload(Request $request): ?array
    {
        if (!$this->storefrontAssistantRequestPayloadValidator->isRequestPayloadValid($request)) {
            return null;
        }

        $payload = json_decode((string)$request->getContent(), true);

        return is_array($payload) ? $payload : null;
    }
}
