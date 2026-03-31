<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce;

use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;

interface AiCommerceClientInterface
{
    /**
     * Specification:
     * - Accepts an image (base64-encoded content and media type) and a locale name.
     * - Delegates to AiSearchByImageTermResolver which calls AiFoundationClient to identify the product in the image.
     * - Extracts a search term from the AI response.
     * - Returns a SearchByImageResponseTransfer with searchTerm and isSuccessful=true on success.
     * - Returns a SearchByImageResponseTransfer with isSuccessful=false and errors on AI failure.
     *
     * @api
     */
    public function getSearchTermFromImage(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer;
}
