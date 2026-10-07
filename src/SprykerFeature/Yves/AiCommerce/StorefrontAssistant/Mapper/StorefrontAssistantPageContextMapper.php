<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Generated\Shared\Transfer\StorefrontAssistantPageContextTransfer;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Sanitizer\StorefrontAssistantPageContextSanitizer;

class StorefrontAssistantPageContextMapper implements StorefrontAssistantPageContextMapperInterface
{
    /**
     * @param array<string, mixed> $pageContext
     */
    public function mapPageContextToStorefrontAssistantPageContextTransfer(array $pageContext): StorefrontAssistantPageContextTransfer
    {
        return (new StorefrontAssistantPageContextTransfer())
            ->setPageType($pageContext[StorefrontAssistantPageContextSanitizer::KEY_PAGE_TYPE])
            ->setProductName($pageContext[StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_NAME])
            ->setProductSku($pageContext[StorefrontAssistantPageContextSanitizer::KEY_PRODUCT_SKU])
            ->setCategoryName($pageContext[StorefrontAssistantPageContextSanitizer::KEY_CATEGORY_NAME])
            ->setSearchQuery($pageContext[StorefrontAssistantPageContextSanitizer::KEY_SEARCH_QUERY])
            ->setFilters($pageContext[StorefrontAssistantPageContextSanitizer::KEY_FILTERS]);
    }
}
