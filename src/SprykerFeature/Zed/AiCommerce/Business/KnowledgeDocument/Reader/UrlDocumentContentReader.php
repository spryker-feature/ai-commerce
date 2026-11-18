<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentContentTransfer;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\HostAddressResolverInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UrlDocumentContentReader implements UrlDocumentContentReaderInterface
{
    /**
     * @var list<string>
     */
    protected const array SUPPORTED_URL_SCHEMES = ['https', 'http'];

    /**
     * @var array<string, string>
     */
    protected const array MIME_TYPES_BY_FILE_EXTENSION = [
        'txt' => 'text/plain',
        'md' => 'text/markdown',
        'markdown' => 'text/markdown',
        'csv' => 'text/csv',
        'html' => 'text/html',
        'htm' => 'text/html',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'jp2' => 'image/jp2',
        'heic' => 'image/heic',
    ];

    /**
     * @var list<int>
     */
    protected const array TEMPORARY_FAILURE_STATUS_CODES = [408, 425, 429];

    protected const int STATUS_CODE_OK = 200;

    protected const int STATUS_CODE_SERVER_ERROR_MIN = 500;

    protected const string MIME_TYPE_OCTET_STREAM = 'application/octet-stream';

    protected const string HEADER_CONTENT_TYPE = 'content-type';

    protected const string HEADER_CONTENT_LENGTH = 'content-length';

    protected const string HEADER_USER_AGENT = 'User-Agent';

    protected const string USER_AGENT = 'Spryker-AiCommerce-KnowledgeDocument/1.0';

    protected const string OPTION_MAX_REDIRECTS = 'max_redirects';

    protected const string OPTION_TIMEOUT = 'timeout';

    protected const string OPTION_MAX_DURATION = 'max_duration';

    protected const string OPTION_RESOLVE = 'resolve';

    protected const string OPTION_BUFFER = 'buffer';

    protected const string OPTION_HEADERS = 'headers';

    protected const string HTTP_METHOD_GET = 'GET';

    protected const string MIME_TYPE_PARAMETER_SEPARATOR = ';';

    protected const string DISPLAY_URL_FORMAT = '%s://%s%s%s';

    protected const string DISPLAY_URL_PORT_FORMAT = ':%d';

    protected const string DISPLAY_URL_END_CHARACTERS = '?#';

    protected const string PATTERN_URL_USER_INFO = '#^((?:[a-z][a-z0-9+.\-]*:)?//)?[^/]*@#i';

    protected const string REPLACEMENT_URL_WITHOUT_USER_INFO = '$1';

    protected const string PATTERN_URL_QUERY_AND_FRAGMENT = '#(https?://[^\s"\'?\#]*)[?\#][^\s"\']*#i';

    protected const string REPLACEMENT_URL_WITHOUT_QUERY_AND_FRAGMENT = '$1';

    protected const int IPV6_EMBEDDED_IPV4_OFFSET = 12;

    /**
     * @var list<string>
     */
    protected const array IPV6_PREFIXES_WITH_EMBEDDED_IPV4 = [
        "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff",
        "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00",
    ];

    protected const string ERROR_MESSAGE_URL_MISSING = 'The document source has no URL.';

    protected const string ERROR_MESSAGE_URL_INVALID = 'The document URL "%s" is not a valid http or https URL.';

    protected const string ERROR_MESSAGE_HOST_NOT_RESOLVED = 'The host "%s" could not be resolved.';

    protected const string ERROR_MESSAGE_ADDRESS_NOT_PUBLIC = 'The host "%s" resolves to the non-public address "%s".';

    protected const string ERROR_MESSAGE_STATUS_CODE = 'The download of "%s" failed with HTTP status %d.';

    protected const string ERROR_MESSAGE_TOO_LARGE = 'The document "%s" is larger than %d bytes.';

    protected const string ERROR_MESSAGE_TRANSPORT = 'The download of "%s" failed: %s';

    protected const string ERROR_MESSAGE_CONNECTION_FAILED = 'The download of "%s" failed on all %d checked addresses. Last error: %s';

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected HostAddressResolverInterface $hostAddressResolver,
        protected AiCommerceConfig $aiCommerceConfig,
    ) {
    }

    public function readDocumentContent(
        KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer,
    ): KnowledgeDocumentContentTransfer {
        $knowledgeDocumentContentTransfer = (new KnowledgeDocumentContentTransfer())
            ->setKnowledgeDocumentSource($knowledgeDocumentSourceTransfer)
            ->setIsTemporaryFailure(false);

        $url = (string)$knowledgeDocumentSourceTransfer->getUrl();

        if ($url === '') {
            return $this->addError($knowledgeDocumentContentTransfer, static::ERROR_MESSAGE_URL_MISSING);
        }

        $host = $this->findHost($url);

        $displayUrl = $this->createDisplayUrl($url);

        if ($host === null) {
            return $this->addError($knowledgeDocumentContentTransfer, sprintf(static::ERROR_MESSAGE_URL_INVALID, $displayUrl));
        }

        $addresses = $this->hostAddressResolver->resolveHostAddresses($host);

        if ($addresses === []) {
            return $this->addError($knowledgeDocumentContentTransfer->setIsTemporaryFailure(true), sprintf(static::ERROR_MESSAGE_HOST_NOT_RESOLVED, $host));
        }

        $nonPublicAddress = $this->findNonPublicAddress($addresses);

        if ($nonPublicAddress !== null) {
            return $this->addError($knowledgeDocumentContentTransfer, sprintf(static::ERROR_MESSAGE_ADDRESS_NOT_PUBLIC, $host, $nonPublicAddress));
        }

        try {
            return $this->download($knowledgeDocumentContentTransfer, $url, $host, $addresses, $this->getMaxFileSize($knowledgeDocumentSourceTransfer));
        } catch (TransportExceptionInterface $transportException) {
            return $this->addError(
                $knowledgeDocumentContentTransfer->setIsTemporaryFailure(true),
                sprintf(static::ERROR_MESSAGE_TRANSPORT, $displayUrl, $this->removeUrlSecrets($transportException->getMessage(), $url, $displayUrl)),
            );
        }
    }

    /**
     * @param list<string> $addresses
     */
    protected function download(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer,
        string $url,
        string $host,
        array $addresses,
        int $maxFileSize,
    ): KnowledgeDocumentContentTransfer {
        $connectionErrorMessage = '';

        foreach ($addresses as $address) {
            $response = $this->requestUrl($url, $host, $address);

            try {
                $statusCode = $response->getStatusCode();
            } catch (TransportExceptionInterface $transportException) {
                $connectionErrorMessage = $transportException->getMessage();

                continue;
            }

            return $this->readResponse($knowledgeDocumentContentTransfer, $response, $statusCode, $url, $maxFileSize);
        }

        $displayUrl = $this->createDisplayUrl($url);

        return $this->addError(
            $knowledgeDocumentContentTransfer->setIsTemporaryFailure(true),
            sprintf(static::ERROR_MESSAGE_CONNECTION_FAILED, $displayUrl, count($addresses), $this->removeUrlSecrets($connectionErrorMessage, $url, $displayUrl)),
        );
    }

    protected function requestUrl(string $url, string $host, string $address): ResponseInterface
    {
        $timeout = max(1, $this->aiCommerceConfig->getKnowledgeDocumentDownloadTimeout());

        return $this->httpClient->request(static::HTTP_METHOD_GET, $url, [
            static::OPTION_MAX_REDIRECTS => 0,
            static::OPTION_TIMEOUT => $timeout,
            static::OPTION_MAX_DURATION => $timeout,
            static::OPTION_RESOLVE => [$host => $address],
            static::OPTION_BUFFER => false,
            static::OPTION_HEADERS => [static::HEADER_USER_AGENT => static::USER_AGENT],
        ]);
    }

    protected function readResponse(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer,
        ResponseInterface $response,
        int $statusCode,
        string $url,
        int $maxFileSize,
    ): KnowledgeDocumentContentTransfer {
        if ($statusCode !== static::STATUS_CODE_OK) {
            $response->cancel();

            return $this->addError(
                $knowledgeDocumentContentTransfer->setIsTemporaryFailure($this->isTemporaryFailureStatusCode($statusCode)),
                sprintf(static::ERROR_MESSAGE_STATUS_CODE, $this->createDisplayUrl($url), $statusCode),
            );
        }

        $headers = $response->getHeaders(false);

        if ((int)($headers[static::HEADER_CONTENT_LENGTH][0] ?? 0) > $maxFileSize) {
            $response->cancel();

            return $this->addError($knowledgeDocumentContentTransfer, sprintf(static::ERROR_MESSAGE_TOO_LARGE, $this->createDisplayUrl($url), $maxFileSize));
        }

        $content = $this->readLimitedContent($response, $maxFileSize);

        if ($content === null) {
            return $this->addError($knowledgeDocumentContentTransfer, sprintf(static::ERROR_MESSAGE_TOO_LARGE, $this->createDisplayUrl($url), $maxFileSize));
        }

        return $knowledgeDocumentContentTransfer
            ->setContent($content)
            ->setMimeType($this->resolveMimeType($headers[static::HEADER_CONTENT_TYPE][0] ?? '', $url))
            ->setIsSuccessful(true);
    }

    protected function getMaxFileSize(KnowledgeDocumentSourceTransfer $knowledgeDocumentSourceTransfer): int
    {
        $maxFileSize = $this->aiCommerceConfig->getKnowledgeDocumentMaxFileSize();
        $maxContentSize = $knowledgeDocumentSourceTransfer->getMaxContentSize();

        if ($maxContentSize === null) {
            return $maxFileSize;
        }

        return max(1, min($maxFileSize, $maxContentSize));
    }

    protected function readLimitedContent(ResponseInterface $response, int $maxFileSize): ?string
    {
        $content = '';

        foreach ($this->httpClient->stream($response) as $chunk) {
            $content .= $chunk->getContent();

            if (strlen($content) > $maxFileSize) {
                $response->cancel();

                return null;
            }
        }

        return $content;
    }

    protected function findHost(string $url): ?string
    {
        $urlParts = parse_url($url);

        if ($urlParts === false || !isset($urlParts['scheme'], $urlParts['host'])) {
            return null;
        }

        if (!in_array(strtolower($urlParts['scheme']), static::SUPPORTED_URL_SCHEMES, true)) {
            return null;
        }

        return strtolower(trim($urlParts['host'], '[]'));
    }

    protected function createDisplayUrl(string $url): string
    {
        $urlParts = parse_url($url);

        if ($urlParts === false || !isset($urlParts['scheme'])) {
            return $this->removeUrlUserInfo(substr($url, 0, strcspn($url, static::DISPLAY_URL_END_CHARACTERS)));
        }

        return sprintf(
            static::DISPLAY_URL_FORMAT,
            $urlParts['scheme'],
            $urlParts['host'] ?? '',
            isset($urlParts['port']) ? sprintf(static::DISPLAY_URL_PORT_FORMAT, $urlParts['port']) : '',
            $urlParts['path'] ?? '',
        );
    }

    protected function removeUrlUserInfo(string $url): string
    {
        return preg_replace(static::PATTERN_URL_USER_INFO, static::REPLACEMENT_URL_WITHOUT_USER_INFO, $url) ?? '';
    }

    protected function removeUrlSecrets(string $message, string $url, string $displayUrl): string
    {
        return preg_replace(
            static::PATTERN_URL_QUERY_AND_FRAGMENT,
            static::REPLACEMENT_URL_WITHOUT_QUERY_AND_FRAGMENT,
            str_replace($url, $displayUrl, $message),
        ) ?? $displayUrl;
    }

    /**
     * @param list<string> $addresses
     */
    protected function findNonPublicAddress(array $addresses): ?string
    {
        foreach ($addresses as $address) {
            if (!$this->isPublicAddress($address)) {
                return $address;
            }
        }

        return null;
    }

    protected function isPublicAddress(string $address): bool
    {
        if (!$this->isPublicIpAddress($address)) {
            return false;
        }

        $embeddedIpv4Address = $this->findEmbeddedIpv4Address($address);

        return $embeddedIpv4Address === null || $this->isPublicIpAddress($embeddedIpv4Address);
    }

    protected function isPublicIpAddress(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE,
        ) !== false;
    }

    protected function findEmbeddedIpv4Address(string $address): ?string
    {
        $packedAddress = (string)inet_pton($address);

        if (!in_array(substr($packedAddress, 0, static::IPV6_EMBEDDED_IPV4_OFFSET), static::IPV6_PREFIXES_WITH_EMBEDDED_IPV4, true)) {
            return null;
        }

        return (string)inet_ntop(substr($packedAddress, static::IPV6_EMBEDDED_IPV4_OFFSET));
    }

    protected function isTemporaryFailureStatusCode(int $statusCode): bool
    {
        return $statusCode >= static::STATUS_CODE_SERVER_ERROR_MIN || in_array($statusCode, static::TEMPORARY_FAILURE_STATUS_CODES, true);
    }

    protected function resolveMimeType(string $contentType, string $url): string
    {
        $mimeType = strtolower(trim((string)strtok($contentType, static::MIME_TYPE_PARAMETER_SEPARATOR)));

        if ($mimeType !== '' && $mimeType !== static::MIME_TYPE_OCTET_STREAM) {
            return $mimeType;
        }

        $fileExtension = strtolower(pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return static::MIME_TYPES_BY_FILE_EXTENSION[$fileExtension] ?? $mimeType;
    }

    protected function addError(
        KnowledgeDocumentContentTransfer $knowledgeDocumentContentTransfer,
        string $message,
    ): KnowledgeDocumentContentTransfer {
        return $knowledgeDocumentContentTransfer
            ->setIsSuccessful(false)
            ->addError((new ErrorTransfer())->setMessage($message));
    }
}
