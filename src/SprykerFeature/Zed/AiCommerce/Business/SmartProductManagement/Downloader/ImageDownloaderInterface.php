<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\SmartProductManagement\Downloader;

interface ImageDownloaderInterface
{
    /**
     * Specification:
     * - Validates the URL scheme against the allow-list.
     * - Resolves the host and rejects private, loopback and link-local IP addresses to prevent SSRF.
     * - Downloads the image with a configured timeout, capping the response at the configured maximum size.
     * - Verifies that the downloaded bytes are an image of an allowed media type.
     * - Returns the raw bytes and detected media type on success, or null on any validation or download failure.
     *
     * @return array{bytes: string, mediaType: string}|null
     */
    public function download(string $imageUrl): ?array;
}
