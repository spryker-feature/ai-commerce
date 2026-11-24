<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\AiCommerce\KnowledgeDocument;

class KnowledgeDocumentUrlSanitizer implements KnowledgeDocumentUrlSanitizerInterface
{
    protected const string SCHEME_HTTP = 'http';

    protected const string SCHEME_HTTPS = 'https';

    /**
     * @var list<string>
     */
    protected const array SUPPORTED_SCHEMES = [
        self::SCHEME_HTTP,
        self::SCHEME_HTTPS,
    ];

    protected const int MAX_URL_LENGTH = 2048;

    /**
     * @var array<string, string>
     */
    protected const array PERCENT_ENCODED_CHARACTERS = [
        ' ' => '%20',
        '(' => '%28',
        ')' => '%29',
        '<' => '%3C',
        '>' => '%3E',
    ];

    protected const string PATTERN_CONTROL_FORMAT_SEPARATOR_OR_BACKSLASH_CHARACTER = '/[\p{Cc}\p{Cf}\p{Z}\\\\]/u';

    protected const string URL_PART_SCHEME = 'scheme';

    protected const string URL_PART_HOST = 'host';

    protected const string URL_PART_USER = 'user';

    protected const string URL_PART_PASSWORD = 'pass';

    public function sanitizeUrl(?string $url): ?string
    {
        $url = strtr(trim((string)$url, ' '), static::PERCENT_ENCODED_CHARACTERS);

        return $this->isUrlValid($url) ? $url : null;
    }

    protected function isUrlValid(string $url): bool
    {
        if ($url === '' || strlen($url) > static::MAX_URL_LENGTH || preg_match(static::PATTERN_CONTROL_FORMAT_SEPARATOR_OR_BACKSLASH_CHARACTER, $url) !== 0) {
            return false;
        }

        $urlParts = parse_url($url);

        return is_array($urlParts) && $this->hasValidUrlParts($urlParts);
    }

    /**
     * @param array<string, int|string> $urlParts
     */
    protected function hasValidUrlParts(array $urlParts): bool
    {
        return in_array(strtolower((string)($urlParts[static::URL_PART_SCHEME] ?? '')), static::SUPPORTED_SCHEMES, true)
            && trim((string)($urlParts[static::URL_PART_HOST] ?? '')) !== ''
            && !isset($urlParts[static::URL_PART_USER])
            && !isset($urlParts[static::URL_PART_PASSWORD]);
    }
}
