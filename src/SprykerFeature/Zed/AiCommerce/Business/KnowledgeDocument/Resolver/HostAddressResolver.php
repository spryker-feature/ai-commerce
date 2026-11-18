<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver;

class HostAddressResolver implements HostAddressResolverInterface
{
    protected const string DNS_RECORD_KEY_IPV6 = 'ipv6';

    /**
     * @return list<string>
     */
    public function resolveHostAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return array_values(array_unique([
            ...(gethostbynamel($host) ?: []),
            ...$this->resolveIpv6Addresses($host),
        ]));
    }

    /**
     * @return list<string>
     */
    protected function resolveIpv6Addresses(string $host): array
    {
        set_error_handler(static fn (): bool => true);

        try {
            $dnsRecords = dns_get_record($host, DNS_AAAA);
        } finally {
            restore_error_handler();
        }

        if ($dnsRecords === false) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $dnsRecord): string => (string)($dnsRecord[static::DNS_RECORD_KEY_IPV6] ?? ''),
            $dnsRecords,
        )));
    }
}
