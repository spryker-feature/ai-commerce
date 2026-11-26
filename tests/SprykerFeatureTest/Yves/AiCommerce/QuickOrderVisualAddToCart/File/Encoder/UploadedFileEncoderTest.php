<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Encoder;

use Codeception\Test\Unit;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Encoder\UploadedFileEncoder;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group QuickOrderVisualAddToCart
 * @group File
 * @group Encoder
 * @group UploadedFileEncoderTest
 */
class UploadedFileEncoderTest extends Unit
{
    protected const string MISSING_FILE_PATH = '/tmp/quick-order-visual-add-to-cart-missing-upload.pdf';

    protected AiCommerceYvesTester $tester;

    public function testGivenReadablePdfWhenEncodingThenBase64OfFileContentIsReturned(): void
    {
        // Arrange
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF);

        // Act
        $base64Content = (new UploadedFileEncoder())->encodeToBase64($uploadedFile);

        // Assert
        $this->assertSame(base64_encode((string)file_get_contents($uploadedFile->getPathname())), $base64Content);
    }

    /**
     * The application error handler turns the read warning into an exception unless it is configured to only log it;
     * the handler under test is replaced here so the log-only case, where the encoder must return null, is reached.
     */
    public function testGivenUnreadableFileWhenEncodingThenNullIsReturned(): void
    {
        // Arrange
        $uploadedFile = $this->createConfiguredMock(UploadedFile::class, ['getPathname' => static::MISSING_FILE_PATH]);
        set_error_handler(static fn (): bool => true);

        // Act
        try {
            $base64Content = (new UploadedFileEncoder())->encodeToBase64($uploadedFile);
        } finally {
            restore_error_handler();
        }

        // Assert
        $this->assertNull($base64Content);
    }
}
