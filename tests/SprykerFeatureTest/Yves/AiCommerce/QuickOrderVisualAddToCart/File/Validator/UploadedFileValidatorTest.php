<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Validator;

use Codeception\Test\Unit;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Validator\UploadedFileValidator;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group QuickOrderVisualAddToCart
 * @group File
 * @group Validator
 * @group UploadedFileValidatorTest
 */
class UploadedFileValidatorTest extends Unit
{
    protected const int MAX_FILE_SIZE_SMALLER_THAN_FIXTURES = 1000;

    protected const string SPOOFED_PDF_FILE_NAME = 'order-list.pdf';

    protected AiCommerceYvesTester $tester;

    /**
     * @dataProvider provideSupportedFixtureFileNames
     */
    public function testGivenSupportedImageOrPdfFileWhenValidatingThenFormatAndMimeTypeAreValid(string $fixtureFileName): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig());
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile($fixtureFileName);

        // Act & Assert
        $this->assertTrue($uploadedFileValidator->isValidMimeType($uploadedFile));
        $this->assertTrue($uploadedFileValidator->isValidFormat($uploadedFile));
    }

    /**
     * @dataProvider provideConfiguredMimeTypes
     */
    public function testGivenFileOfAnyConfiguredImageOrDocumentMimeTypeWhenValidatingThenMimeTypeIsValid(string $mimeType): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig());
        $uploadedFile = $this->createUploadedFileMock($mimeType, null);

        // Act
        $isValidMimeType = $uploadedFileValidator->isValidMimeType($uploadedFile);

        // Assert
        $this->assertTrue($isValidMimeType);
    }

    /**
     * @dataProvider provideConfiguredExtensions
     */
    public function testGivenFileOfAnyConfiguredImageOrDocumentExtensionWhenValidatingThenFormatIsValid(string $extension): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig());
        $uploadedFile = $this->createUploadedFileMock(null, $extension);

        // Act
        $isValidFormat = $uploadedFileValidator->isValidFormat($uploadedFile);

        // Assert
        $this->assertTrue($isValidFormat);
    }

    public function testGivenPlainTextFileWhenValidatingThenFormatAndMimeTypeAreInvalid(): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig());
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile(AiCommerceYvesTester::FIXTURE_TEXT);

        // Act & Assert
        $this->assertFalse($uploadedFileValidator->isValidMimeType($uploadedFile));
        $this->assertFalse($uploadedFileValidator->isValidFormat($uploadedFile));
    }

    public function testGivenTextFileNamedAsPdfWhenValidatingThenFormatAndMimeTypeAreInvalid(): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig());
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile(AiCommerceYvesTester::FIXTURE_TEXT, static::SPOOFED_PDF_FILE_NAME);

        // Act & Assert
        $this->assertFalse($uploadedFileValidator->isValidMimeType($uploadedFile));
        $this->assertFalse($uploadedFileValidator->isValidFormat($uploadedFile));
    }

    public function testGivenDocumentTypesConfiguredAsEmptyWhenValidatingPdfThenPdfIsRejected(): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig([
            'getQuickOrderVisualAddToCartSupportedDocumentMimeTypes' => [],
            'getQuickOrderVisualAddToCartSupportedDocumentExtensions' => [],
        ]));
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF);

        // Act & Assert
        $this->assertFalse($uploadedFileValidator->isValidMimeType($uploadedFile));
        $this->assertFalse($uploadedFileValidator->isValidFormat($uploadedFile));
    }

    public function testGivenPdfLargerThanMaxFileSizeWhenValidatingThenFileSizeIsInvalid(): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig([
            'getQuickOrderVisualAddToCartMaxFileSizeInBytes' => static::MAX_FILE_SIZE_SMALLER_THAN_FIXTURES,
        ]));
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF);

        // Act
        $isValidFileSize = $uploadedFileValidator->isValidFileSize($uploadedFile);

        // Assert
        $this->assertFalse($isValidFileSize);
    }

    public function testGivenFileOfExactlyMaxFileSizeWhenValidatingThenFileSizeIsValid(): void
    {
        // Arrange
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF);
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig([
            'getQuickOrderVisualAddToCartMaxFileSizeInBytes' => $uploadedFile->getSize(),
        ]));

        // Act
        $isValidFileSize = $uploadedFileValidator->isValidFileSize($uploadedFile);

        // Assert
        $this->assertTrue($isValidFileSize);
    }

    public function testGivenFileWithUndetectableMimeTypeAndExtensionWhenValidatingThenFormatAndMimeTypeAreInvalid(): void
    {
        // Arrange
        $uploadedFileValidator = new UploadedFileValidator($this->tester->createQuickOrderVisualAddToCartConfig());
        $uploadedFile = $this->createUploadedFileMock(null, null);

        // Act & Assert
        $this->assertFalse($uploadedFileValidator->isValidMimeType($uploadedFile));
        $this->assertFalse($uploadedFileValidator->isValidFormat($uploadedFile));
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideSupportedFixtureFileNames(): array
    {
        return [
            'single-page PDF' => [AiCommerceYvesTester::FIXTURE_SINGLE_PAGE_PDF],
            'multi-page PDF' => [AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF],
            'PNG image' => [AiCommerceYvesTester::FIXTURE_PNG],
        ];
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideConfiguredMimeTypes(): iterable
    {
        $aiCommerceConfig = new AiCommerceConfig();

        foreach ($aiCommerceConfig->getQuickOrderVisualAddToCartSupportedImageMimeTypes() as $mimeType) {
            yield $mimeType => [$mimeType];
        }

        foreach ($aiCommerceConfig->getQuickOrderVisualAddToCartSupportedDocumentMimeTypes() as $mimeType) {
            yield $mimeType => [$mimeType];
        }
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideConfiguredExtensions(): iterable
    {
        $aiCommerceConfig = new AiCommerceConfig();

        foreach ($aiCommerceConfig->getQuickOrderVisualAddToCartSupportedImageExtensions() as $extension) {
            yield $extension => [$extension];
        }

        foreach ($aiCommerceConfig->getQuickOrderVisualAddToCartSupportedDocumentExtensions() as $extension) {
            yield $extension => [$extension];
        }
    }

    protected function createUploadedFileMock(?string $mimeType, ?string $extension): UploadedFile
    {
        return $this->createConfiguredMock(UploadedFile::class, [
            'getMimeType' => $mimeType,
            'guessExtension' => $extension,
        ]);
    }
}
