<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\AiCommerce;

use Spryker\Service\Kernel\AbstractService;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Service\AiCommerce\AiCommerceServiceFactory getFactory()
 */
class AiCommerceService extends AbstractService implements AiCommerceServiceInterface
{
    public function generateConversationReference(string $userReference): string
    {
        return $this->getFactory()
            ->createConversationReferenceGenerator()
            ->generate($userReference);
    }
}
