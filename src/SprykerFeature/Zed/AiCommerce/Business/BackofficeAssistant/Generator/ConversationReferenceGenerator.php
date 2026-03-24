<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\BackofficeAssistant\Generator;

class ConversationReferenceGenerator implements ConversationReferenceGeneratorInterface
{
    public function generate(string $userReference): string
    {
        return sprintf('%s:%d:%s', $userReference, time(), bin2hex(random_bytes(8)));
    }
}
