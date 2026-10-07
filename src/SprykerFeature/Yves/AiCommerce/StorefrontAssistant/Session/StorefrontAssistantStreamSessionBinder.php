<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Session;

use Spryker\Client\Session\SessionClientInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StorefrontAssistantStreamSessionBinder implements StorefrontAssistantStreamSessionBinderInterface
{
    public function __construct(
        protected SessionClientInterface $sessionClient,
        protected SessionInterface $streamSession
    ) {
    }

    /**
     * @param array<string, mixed> $sessionAttributes
     */
    public function bindSessionAttributes(array $sessionAttributes): void
    {
        // The streamed callback runs after the real session is saved and closed, so clients read a detached in-memory copy instead.
        $this->streamSession->replace($sessionAttributes);

        $this->sessionClient->setContainer($this->streamSession);
    }
}
