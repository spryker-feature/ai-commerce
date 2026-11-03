<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper;

use Spryker\Client\Store\StoreClientInterface;

class StorefrontAssistantStoreUrlMapper implements StorefrontAssistantStoreUrlMapperInterface
{
    protected const string PATH_SEPARATOR = '/';

    protected const string PROTOCOL_RELATIVE_URL_PREFIX = '//';

    protected const string STORE_PREFIX_PATTERN = '/%s';

    /**
     * @var list<string>
     */
    protected const array STORE_PREFIX_TERMINATORS = ['', '/', '?', '#'];

    public function __construct(
        protected StoreClientInterface $storeClient,
        protected bool $isStoreRoutingEnabled
    ) {
    }

    public function mapUrlToStoreUrl(string $url): string
    {
        if (!$this->isStoreRoutingEnabled || !$this->isRootRelativePath($url)) {
            return $url;
        }

        $storeName = (string)$this->storeClient->getCurrentStore()->getName();

        if ($storeName === '') {
            return $url;
        }

        $storePrefix = sprintf(static::STORE_PREFIX_PATTERN, $storeName);

        if ($this->hasStorePrefix($url, $storePrefix)) {
            return $url;
        }

        return sprintf('%s%s', $storePrefix, $url);
    }

    protected function isRootRelativePath(string $url): bool
    {
        return str_starts_with($url, static::PATH_SEPARATOR) && !str_starts_with($url, static::PROTOCOL_RELATIVE_URL_PREFIX);
    }

    protected function hasStorePrefix(string $url, string $storePrefix): bool
    {
        return str_starts_with($url, $storePrefix)
            && in_array(substr($url, strlen($storePrefix), 1), static::STORE_PREFIX_TERMINATORS, true);
    }
}
