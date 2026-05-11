<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Downloader;

use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;

class ImageDownloader implements ImageDownloaderInterface
{
    public const int IMAGE_FETCH_TIMEOUT_SECONDS = 10;

    public const int IMAGE_FETCH_MAX_BYTES = 10485760;

    /**
     * @var array<int, string>
     */
    protected const array IMAGE_TYPE_MEDIA_TYPE_MAP = [
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_GIF => 'image/gif',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    protected const string HTTP_HEADER_ACCEPT = "Accept: image/*\r\n";

    protected const string HTTP_HEADER_USER_AGENT = "User-Agent: Spryker-AiCommerce/1.0\r\n";

    public function __construct(protected readonly AiCommerceConfig $aiCommerceConfig)
    {
    }

    /**
     * @return array{bytes: string, mediaType: string}|null
     */
    public function download(string $imageUrl): ?array
    {
        if (!$this->isUrlSafe($imageUrl)) {
            return null;
        }

        $bytes = $this->fetchBytes($imageUrl);
        if ($bytes === null) {
            return null;
        }

        $mediaType = $this->detectAllowedMediaType($bytes);
        if ($mediaType === null) {
            return null;
        }

        return ['bytes' => $bytes, 'mediaType' => $mediaType];
    }

    protected function isUrlSafe(string $imageUrl): bool
    {
        $parts = parse_url($imageUrl);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (!in_array(strtolower($parts['scheme']), $this->aiCommerceConfig->getAllowedImageUrlSchemes(), true)) {
            return false;
        }

        return $this->isHostPublic($parts['host']);
    }

    protected function isHostPublic(string $host): bool
    {
        $resolvedIps = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if ($resolvedIps === []) {
            return false;
        }

        foreach ($resolvedIps as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    protected function fetchBytes(string $imageUrl): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => static::IMAGE_FETCH_TIMEOUT_SECONDS,
                'follow_location' => 0,
                'header' => static::HTTP_HEADER_ACCEPT . static::HTTP_HEADER_USER_AGENT,
                'ignore_errors' => false,
            ],
        ]);

        $maxBytes = static::IMAGE_FETCH_MAX_BYTES;

        set_error_handler(static fn (): bool => true);

        try {
            $handle = fopen($imageUrl, 'rb', false, $context);
        } finally {
            restore_error_handler();
        }

        if ($handle === false) {
            return null;
        }

        // Read one extra byte so we can detect responses that exceed the limit.
        $bytes = stream_get_contents($handle, $maxBytes + 1);
        fclose($handle);

        if ($bytes === false || strlen($bytes) > $maxBytes) {
            return null;
        }

        return $bytes;
    }

    protected function detectAllowedMediaType(string $bytes): ?string
    {
        $imageInfo = getimagesizefromstring($bytes);
        if ($imageInfo === false) {
            return null;
        }

        $mediaType = static::IMAGE_TYPE_MEDIA_TYPE_MAP[$imageInfo[2]] ?? null;
        if ($mediaType === null) {
            return null;
        }

        if (!in_array($mediaType, $this->aiCommerceConfig->getImageFetchAllowedMediaTypes(), true)) {
            return null;
        }

        return $mediaType;
    }
}
