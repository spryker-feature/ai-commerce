<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Shared\AiCommerce\StorefrontAssistant;

enum StorefrontAssistantPageType: string
{
    case Product = 'product';
    case Category = 'category';
    case Search = 'search';
    case Default = 'default';

    /**
     * @return list<self>
     */
    public static function reportableCases(): array
    {
        return [static::Product, static::Category, static::Search];
    }
}
