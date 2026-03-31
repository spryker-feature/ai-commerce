<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceFactory getFactory()
 */
class AiCommerceClient extends AbstractClient implements AiCommerceClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSearchTermFromImage(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer
    {
        return $this->getFactory()->createAiSearchByImageTermResolver()->getSearchTermFromImage($searchByImageRequestTransfer);
    }
}
