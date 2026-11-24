<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\KnowledgeDocument;

use Codeception\Test\Unit;
use SprykerFeatureTest\Client\AiCommerce\AiCommerceClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group KnowledgeDocument
 * @group KnowledgeDocumentUrlSanitizerTest
 */
class KnowledgeDocumentUrlSanitizerTest extends Unit
{
    protected const int MAX_URL_LENGTH = 2048;

    protected const string URL_PREFIX = 'https://spryker.s3.eu-central-1.amazonaws.com/';

    protected AiCommerceClientTester $tester;

    /**
     * @dataProvider provideUrls
     */
    public function testGivenUrlWhenSanitizedThenOnlyAValidHttpOrHttpsUrlIsReturned(?string $url, ?string $expectedUrl): void
    {
        // Arrange
        $knowledgeDocumentUrlSanitizer = $this->tester->getFactory()->createKnowledgeDocumentUrlSanitizer();

        // Act
        $sanitizedUrl = $knowledgeDocumentUrlSanitizer->sanitizeUrl($url);

        // Assert
        $this->assertSame($expectedUrl, $sanitizedUrl);
    }

    /**
     * @return array<string, array{string|null, string|null}>
     */
    public static function provideUrls(): array
    {
        $maxLengthUrl = static::URL_PREFIX . str_repeat('a', static::MAX_URL_LENGTH - strlen(static::URL_PREFIX));

        return [
            'https url' => ['https://spryker.s3.eu-central-1.amazonaws.com/manual.pdf', 'https://spryker.s3.eu-central-1.amazonaws.com/manual.pdf'],
            'http url with query' => ['http://example.com/manual.pdf?version=2#page=3', 'http://example.com/manual.pdf?version=2#page=3'],
            'upper case scheme' => ['HTTPS://example.com/manual.pdf', 'HTTPS://example.com/manual.pdf'],
            'surrounding spaces are trimmed' => ['  https://example.com/manual.pdf ', 'https://example.com/manual.pdf'],
            'space, brackets and angle brackets are percent-encoded' => ['https://example.com/user manual (v2) <draft>.pdf', 'https://example.com/user%20manual%20%28v2%29%20%3Cdraft%3E.pdf'],
            'url of the maximum length' => [$maxLengthUrl, $maxLengthUrl],
            'url above the maximum length' => [$maxLengthUrl . 'a', null],
            'null' => [null, null],
            'empty string' => ['', null],
            'javascript scheme' => ['javascript:alert(1)', null],
            'javascript scheme with slashes' => ['javascript://example.com/%0Aalert(1)', null],
            'data scheme' => ['data:text/html;base64,PHNjcmlwdD4=', null],
            's3 scheme' => ['s3://spryker/manual.pdf', null],
            'ftp scheme' => ['ftp://example.com/manual.pdf', null],
            'relative path' => ['/manual.pdf', null],
            'protocol relative url' => ['//example.com/manual.pdf', null],
            'no host' => ['https:///manual.pdf', null],
            'user info' => ['https://user@example.com/manual.pdf', null],
            'user and password' => ['https://user:secret@example.com/manual.pdf', null],
            'tab character' => ["https://example.com/man\tual.pdf", null],
            'new line character' => ["https://example.com/manual.pdf\njavascript:alert(1)", null],
            'null byte' => ["https://example.com/manual.pdf\0", null],
            'non-breaking space' => ["https://example.com/man\u{00A0}ual.pdf", null],
            'backslash' => ['https:\\\\example.com\\manual.pdf', null],
        ];
    }
}
