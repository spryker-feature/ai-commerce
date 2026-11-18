<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Business\KnowledgeDocument;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\KnowledgeDocumentSourceTransfer;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Reader\UrlDocumentContentReader;
use SprykerFeature\Zed\AiCommerce\Business\KnowledgeDocument\Resolver\HostAddressResolverInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Business
 * @group KnowledgeDocument
 * @group UrlDocumentContentReaderTest
 * Add your own group annotations below this line
 */
class UrlDocumentContentReaderTest extends Unit
{
    protected const string MIME_TYPE_TEXT_PLAIN = 'text/plain';

    protected const string MIME_TYPE_TEXT_MARKDOWN = 'text/markdown';

    protected const string PUBLIC_ADDRESS = '93.184.216.34';

    protected const string PUBLIC_ADDRESS_SECOND = '93.184.216.35';

    protected const string SIGNED_DOCUMENT_URL = 'https://docs.example.com/manuals/manual.md?X-Amz-Signature=secret-token#page=2';

    protected const string URL_SECRET = 'secret-token';

    protected const string ERROR_CONNECTION_REFUSED = 'Connection refused.';

    protected const string DOCUMENT_URL = 'https://docs.example.com/manuals/manual.md';

    protected const string DOCUMENT_CONTENT = '# Manual';

    protected const int MAX_FILE_SIZE = 64;

    protected const string DOCUMENT_HOST = 'docs.example.com';

    protected const int MAX_CONTENT_SIZE = 8;

    protected int $requestCount = 0;

    /**
     * @var array<string, mixed>
     */
    protected array $requestOptions = [];

    /**
     * @var list<array<string, string>>
     */
    protected array $requestedResolves = [];

    protected function _before(): void
    {
        parent::_before();

        $this->requestCount = 0;
        $this->requestOptions = [];
        $this->requestedResolves = [];
    }

    public function testGivenPublicHostWhenReadingContentThenCheckedAddressIsPinnedAndNoRedirectIsFollowed(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse(static::DOCUMENT_CONTENT));

        // Act
        $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertSame([static::DOCUMENT_HOST => static::PUBLIC_ADDRESS], $this->requestOptions['resolve']);
        $this->assertSame(0, $this->requestOptions['max_redirects']);
    }

    public function testGivenContentLargerThanSourceMaxContentSizeWhenReadingContentThenFailureIsReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse(str_repeat('a', static::MAX_CONTENT_SIZE + 1)));

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent(
            $this->createSourceTransfer(static::DOCUMENT_URL)->setMaxContentSize(static::MAX_CONTENT_SIZE),
        );

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
    }

    public function testGivenPublicHostWhenReadingContentThenContentAndMimeTypeAreReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(
            new MockResponse(static::DOCUMENT_CONTENT, ['response_headers' => ['content-type' => 'text/markdown; charset=utf-8']]),
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertTrue($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertSame(static::DOCUMENT_CONTENT, $knowledgeDocumentContentTransfer->getContent());
        $this->assertSame(static::MIME_TYPE_TEXT_MARKDOWN, $knowledgeDocumentContentTransfer->getMimeType());
    }

    public function testGivenAnyPublicHostWhenReadingContentThenContentIsReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(
            new MockResponse('Plain text', ['response_headers' => ['content-type' => 'application/octet-stream']]),
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent(
            $this->createSourceTransfer('https://cdn.example.org/notes.txt'),
        );

        // Assert
        $this->assertTrue($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertSame(static::MIME_TYPE_TEXT_PLAIN, $knowledgeDocumentContentTransfer->getMimeType());
    }

    /**
     * @dataProvider provideFileNamesWithMimeTypes
     */
    public function testGivenNoServerMimeTypeWhenReadingContentThenMimeTypeIsResolvedFromFileExtension(string $fileName, string $expectedMimeType): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(
            new MockResponse('binary', ['response_headers' => ['content-type' => 'application/octet-stream']]),
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent(
            $this->createSourceTransfer(sprintf('https://cdn.example.org/%s', $fileName)),
        );

        // Assert
        $this->assertTrue($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertSame($expectedMimeType, $knowledgeDocumentContentTransfer->getMimeType());
    }

    /**
     * @return array<string, list<string>>
     */
    public function provideFileNamesWithMimeTypes(): array
    {
        return [
            'PDF manual' => ['manual.pdf', 'application/pdf'],
            'Word document' => ['guide.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'JPEG photo in upper case' => ['label.JPG', 'image/jpeg'],
            'PNG diagram' => ['diagram.png', 'image/png'],
        ];
    }

    /**
     * @dataProvider provideInvalidUrls
     */
    public function testGivenInvalidUrlWhenReadingContentThenPermanentFailureIsReturned(string $url): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse(static::DOCUMENT_CONTENT));

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer($url));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
        $this->assertCount(1, $knowledgeDocumentContentTransfer->getErrors());
        $this->assertSame(0, $this->requestCount);
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideInvalidUrls(): array
    {
        return [
            'file scheme' => ['file:///etc/passwd'],
            'empty URL' => [''],
        ];
    }

    /**
     * @dataProvider provideNonPublicAddresses
     */
    public function testGivenNonPublicAddressWhenReadingContentThenFailureIsReturned(string $address): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse(static::DOCUMENT_CONTENT), [$address]);

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
        $this->assertSame(0, $this->requestCount);
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideNonPublicAddresses(): array
    {
        return [
            'private' => ['10.0.0.12'],
            'loopback' => ['127.0.0.1'],
            'link-local metadata' => ['169.254.169.254'],
            'shared address space' => ['100.64.0.1'],
            'IPv6 loopback' => ['::1'],
            'IPv4-mapped IPv6 private' => ['::ffff:10.0.0.1'],
            'IPv4-mapped IPv6 loopback' => ['::ffff:127.0.0.1'],
            'IPv4-mapped IPv6 link-local metadata' => ['::ffff:169.254.169.254'],
            'NAT64 private' => ['64:ff9b::10.0.0.1'],
            'NAT64 loopback in hexadecimal notation' => ['64:ff9b::7f00:1'],
            'NAT64 link-local metadata' => ['64:ff9b::a9fe:a9fe'],
        ];
    }

    public function testGivenNat64AddressOfPublicIpv4AddressWhenReadingContentThenContentIsReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse(static::DOCUMENT_CONTENT), ['64:ff9b::5db8:d822']);

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertTrue($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertSame(1, $this->requestCount);
    }

    public function testGivenConnectionFailureOnFirstAddressWhenReadingContentThenNextCheckedAddressIsUsed(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReaderWithResponses(
            [new MockResponse('', ['error' => static::ERROR_CONNECTION_REFUSED]), new MockResponse(static::DOCUMENT_CONTENT)],
            [static::PUBLIC_ADDRESS, static::PUBLIC_ADDRESS_SECOND],
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertTrue($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertSame(static::DOCUMENT_CONTENT, $knowledgeDocumentContentTransfer->getContent());
        $this->assertSame(
            [[static::DOCUMENT_HOST => static::PUBLIC_ADDRESS], [static::DOCUMENT_HOST => static::PUBLIC_ADDRESS_SECOND]],
            $this->requestedResolves,
        );
        $this->assertSame(0, $this->requestOptions['max_redirects']);
    }

    public function testGivenConnectionFailureOnAllAddressesWhenReadingContentThenTemporaryFailureIsReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReaderWithResponses(
            [new MockResponse('', ['error' => static::ERROR_CONNECTION_REFUSED]), new MockResponse('', ['error' => static::ERROR_CONNECTION_REFUSED])],
            [static::PUBLIC_ADDRESS, static::PUBLIC_ADDRESS_SECOND],
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertTrue($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
        $this->assertSame(2, $this->requestCount);
        $this->assertStringContainsString(static::ERROR_CONNECTION_REFUSED, $knowledgeDocumentContentTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenFailureStatusCodeOnFirstAddressWhenReadingContentThenNextAddressIsNotUsed(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReaderWithResponses(
            [new MockResponse('', ['http_code' => 404]), new MockResponse(static::DOCUMENT_CONTENT)],
            [static::PUBLIC_ADDRESS, static::PUBLIC_ADDRESS_SECOND],
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
        $this->assertSame(1, $this->requestCount);
    }

    public function testGivenContentLargerThanLimitOnFirstAddressWhenReadingContentThenNextAddressIsNotUsed(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReaderWithResponses(
            [new MockResponse(str_repeat('a', static::MAX_FILE_SIZE + 1)), new MockResponse(static::DOCUMENT_CONTENT)],
            [static::PUBLIC_ADDRESS, static::PUBLIC_ADDRESS_SECOND],
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertSame(1, $this->requestCount);
    }

    /**
     * @dataProvider provideFailuresOfSignedUrl
     *
     * @param list<string> $addresses
     */
    public function testGivenSignedUrlWhenReadingContentFailsThenErrorMessageHasNoQueryOrFragment(
        MockResponse $mockResponse,
        string $url,
        array $addresses
    ): void {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader($mockResponse, $addresses);

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer($url));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());

        $errorMessage = (string)$knowledgeDocumentContentTransfer->getErrors()->offsetGet(0)->getMessage();
        $this->assertStringNotContainsString(static::URL_SECRET, $errorMessage);
        $this->assertStringNotContainsString('page=2', $errorMessage);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideFailuresOfSignedUrl(): array
    {
        return [
            'failure status code' => [new MockResponse('', ['http_code' => 403]), static::SIGNED_DOCUMENT_URL, [static::PUBLIC_ADDRESS]],
            'content larger than limit' => [new MockResponse(str_repeat('a', static::MAX_FILE_SIZE + 1)), static::SIGNED_DOCUMENT_URL, [static::PUBLIC_ADDRESS]],
            'content length header larger than limit' => [
                new MockResponse(str_repeat('a', static::MAX_FILE_SIZE + 1), ['response_headers' => ['content-length' => (string)(static::MAX_FILE_SIZE + 1)]]),
                static::SIGNED_DOCUMENT_URL,
                [static::PUBLIC_ADDRESS],
            ],
            'connection error with the URL in the message' => [
                new MockResponse('', ['error' => sprintf('Idle timeout reached for "%s".', static::SIGNED_DOCUMENT_URL)]),
                static::SIGNED_DOCUMENT_URL,
                [static::PUBLIC_ADDRESS],
            ],
            'unsupported URL scheme' => [new MockResponse(static::DOCUMENT_CONTENT), 'ftp://docs.example.com/manual.md?X-Amz-Signature=secret-token#page=2', [static::PUBLIC_ADDRESS]],
            'unparsable URL with user info' => [new MockResponse(static::DOCUMENT_CONTENT), 'http://user:secret-token@docs.example.com:99999/manual.md#page=2', [static::PUBLIC_ADDRESS]],
            'unparsable URL with at sign in password' => [new MockResponse(static::DOCUMENT_CONTENT), 'http://user:pass@secret-token@docs.example.com:99999/manual.md', [static::PUBLIC_ADDRESS]],
            'scheme-relative URL with user info' => [new MockResponse(static::DOCUMENT_CONTENT), '//user:secret-token@docs.example.com/manual.md?page=2', [static::PUBLIC_ADDRESS]],
        ];
    }

    public function testGivenSignedUrlWhenDownloadFailsThenErrorMessageHasSchemeHostAndPath(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse('', ['http_code' => 403]));

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent(
            $this->createSourceTransfer('https://user:password@docs.example.com:8443/manuals/manual.md?X-Amz-Signature=secret-token#page=2'),
        );

        // Assert
        $errorMessage = (string)$knowledgeDocumentContentTransfer->getErrors()->offsetGet(0)->getMessage();
        $this->assertStringContainsString('"https://docs.example.com:8443/manuals/manual.md"', $errorMessage);
        $this->assertStringNotContainsString('password', $errorMessage);
    }

    public function testGivenContentLargerThanLimitWhenReadingContentThenFailureIsReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(
            new MockResponse((function (): iterable {
                yield str_repeat('a', static::MAX_FILE_SIZE);
                yield 'b';
            })()),
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
    }

    public function testGivenContentLengthHeaderLargerThanLimitWhenReadingContentThenFailureIsReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(
            new MockResponse(str_repeat('a', static::MAX_FILE_SIZE + 1), ['response_headers' => ['content-length' => (string)(static::MAX_FILE_SIZE + 1)]]),
        );

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
    }

    public function testGivenTimeoutWhenReadingContentThenTemporaryFailureIsReturned(): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse('', ['error' => 'Idle timeout reached.']));

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertTrue($knowledgeDocumentContentTransfer->getIsTemporaryFailure());
    }

    /**
     * @dataProvider provideFailureStatusCodes
     */
    public function testGivenFailureStatusCodeWhenReadingContentThenStatusCodeIsMappedToFailureType(int $statusCode, bool $isTemporaryFailure): void
    {
        // Arrange
        $urlDocumentContentReader = $this->createUrlDocumentContentReader(new MockResponse('', ['http_code' => $statusCode]));

        // Act
        $knowledgeDocumentContentTransfer = $urlDocumentContentReader->readDocumentContent($this->createSourceTransfer(static::DOCUMENT_URL));

        // Assert
        $this->assertFalse($knowledgeDocumentContentTransfer->getIsSuccessful());
        $this->assertSame($isTemporaryFailure, $knowledgeDocumentContentTransfer->getIsTemporaryFailure());
    }

    /**
     * @return array<string, array<int|bool>>
     */
    public function provideFailureStatusCodes(): array
    {
        return [
            'not found' => [404, false],
            'forbidden' => [403, false],
            'redirect is not followed' => [302, false],
            'too many requests' => [429, true],
            'service unavailable' => [503, true],
        ];
    }

    /**
     * @param list<string> $addresses
     */
    protected function createUrlDocumentContentReader(MockResponse $mockResponse, array $addresses = [self::PUBLIC_ADDRESS]): UrlDocumentContentReader
    {
        return $this->createUrlDocumentContentReaderWithResponses([$mockResponse], $addresses);
    }

    /**
     * @param list<\Symfony\Component\HttpClient\Response\MockResponse> $mockResponses
     * @param list<string> $addresses
     */
    protected function createUrlDocumentContentReaderWithResponses(array $mockResponses, array $addresses): UrlDocumentContentReader
    {
        $mockHttpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($mockResponses): ResponseInterface {
            $this->requestCount++;
            $this->requestOptions = $options;
            $this->requestedResolves[] = $options['resolve'];

            return $mockResponses[min($this->requestCount, count($mockResponses)) - 1];
        });

        $hostAddressResolverMock = $this->createMock(HostAddressResolverInterface::class);
        $hostAddressResolverMock->method('resolveHostAddresses')->willReturn($addresses);

        $aiCommerceConfigMock = $this->createMock(AiCommerceConfig::class);
        $aiCommerceConfigMock->method('getKnowledgeDocumentMaxFileSize')->willReturn(static::MAX_FILE_SIZE);
        $aiCommerceConfigMock->method('getKnowledgeDocumentDownloadTimeout')->willReturn(5);

        return new UrlDocumentContentReader($mockHttpClient, $hostAddressResolverMock, $aiCommerceConfigMock);
    }

    protected function createSourceTransfer(string $url): KnowledgeDocumentSourceTransfer
    {
        return (new KnowledgeDocumentSourceTransfer())
            ->setSourceType('test_source')
            ->setSourceIdentifier('1')
            ->setUrl($url);
    }
}
