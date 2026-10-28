<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\Stream;

/**
 * The names of the `data-<name>` events, the extension channel of the protocol that chat features subscribe to.
 */
interface StreamDataEventName
{
    public const string AGENT = 'agent';

    public const string FORM_FILL = 'form-fill';
}
