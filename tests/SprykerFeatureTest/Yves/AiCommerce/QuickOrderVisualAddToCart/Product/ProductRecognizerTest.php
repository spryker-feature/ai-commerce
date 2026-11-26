<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\QuickOrderVisualAddToCart\Product;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\ProductRecognizer;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group QuickOrderVisualAddToCart
 * @group Product
 * @group ProductRecognizerTest
 */
class ProductRecognizerTest extends Unit
{
    protected const string LOCALE_NAME = 'en_US';

    protected const string BASE64_CONTENT = 'YmFzZTY0LWNvbnRlbnQ=';

    protected const string MIME_TYPE_PDF = 'application/pdf';

    protected const string MIME_TYPE_PNG = 'image/png';

    protected const string FILE_EXTENSION_PDF = 'pdf';

    protected const string FILE_EXTENSION_PNG = 'png';

    protected const string EXPECTED_DOCUMENT_FILENAME = 'order-form.pdf';

    protected const string GLOSSARY_KEY_AI_REQUEST_FAILED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.ai-request-failed';

    protected const string GLOSSARY_KEY_AI_RESPONSE_INVALID = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.ai-response-invalid';

    protected const string CONFIGURATION_KEY_DOCUMENT_RECOGNITION_PROMPT = 'ai_commerce:quick_order:system_prompts:document_recognition_prompt';

    protected const string DOCUMENT_PROMPT_WITH_PERCENT_SIGN = 'Read 100% of the order lines. Store locale: %s';

    protected const string EXPECTED_DOCUMENT_PROMPT_WITH_PERCENT_SIGN = 'Read 100% of the order lines. Store locale: en_US';

    protected const string BLANK_DOCUMENT_PROMPT = '   ';

    protected const array RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME = [
        'Industrial Heat Pump 60kW' => 1,
        'Expansion Tank for Heat Systems' => 1,
    ];

    protected AiCommerceYvesTester $tester;

    public function testGivenPdfWhenRecognizingProductsThenPdfIsSentAsNamedDocumentAttachment(): void
    {
        // Arrange
        $promptRequestTransfers = new ArrayObject();
        $productRecognizer = $this->createProductRecognizer($promptRequestTransfers, $this->tester->createSuccessfulProductRecognitionPromptResponse(static::RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME));

        // Act
        $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PDF, static::FILE_EXTENSION_PDF);

        // Assert
        $attachmentTransfer = $this->getSentPromptMessageTransfer($promptRequestTransfers)->getAttachments()->offsetGet(0);
        $this->assertSame(
            [AiFoundationConstants::ATTACHMENT_TYPE_DOCUMENT, static::MIME_TYPE_PDF, static::EXPECTED_DOCUMENT_FILENAME, static::BASE64_CONTENT],
            [$attachmentTransfer->getType(), $attachmentTransfer->getMediaType(), $attachmentTransfer->getFilename(), $attachmentTransfer->getContent()],
        );
    }

    public function testGivenPdfWhenRecognizingProductsThenDocumentPromptWithCurrentLocaleIsSent(): void
    {
        // Arrange
        $promptRequestTransfers = new ArrayObject();
        $productRecognizer = $this->createProductRecognizer($promptRequestTransfers, $this->tester->createSuccessfulProductRecognitionPromptResponse(static::RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME));

        // Act
        $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PDF, static::FILE_EXTENSION_PDF);

        // Assert
        $expectedPrompt = sprintf($this->tester->createQuickOrderVisualAddToCartConfig()->getQuickOrderVisualAddToCartDocumentPromptTemplate(), static::LOCALE_NAME);
        $this->assertSame($expectedPrompt, $this->getSentPromptMessageTransfer($promptRequestTransfers)->getContent());
    }

    public function testGivenConfiguredDocumentPromptWithPercentSignWhenRecognizingPdfThenConfiguredPromptWithLocaleIsSent(): void
    {
        // Arrange
        $promptRequestTransfers = new ArrayObject();
        $productRecognizer = $this->createProductRecognizer(
            $promptRequestTransfers,
            $this->tester->createSuccessfulProductRecognitionPromptResponse(static::RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME),
            $this->createDocumentPromptConfigOverrides(static::DOCUMENT_PROMPT_WITH_PERCENT_SIGN),
        );

        // Act
        $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PDF, static::FILE_EXTENSION_PDF);

        // Assert
        $this->assertSame(static::EXPECTED_DOCUMENT_PROMPT_WITH_PERCENT_SIGN, $this->getSentPromptMessageTransfer($promptRequestTransfers)->getContent());
    }

    public function testGivenBlankConfiguredDocumentPromptWhenRecognizingPdfThenDefaultDocumentPromptIsSent(): void
    {
        // Arrange
        $promptRequestTransfers = new ArrayObject();
        $productRecognizer = $this->createProductRecognizer(
            $promptRequestTransfers,
            $this->tester->createSuccessfulProductRecognitionPromptResponse(static::RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME),
            $this->createDocumentPromptConfigOverrides(static::BLANK_DOCUMENT_PROMPT),
        );

        // Act
        $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PDF, static::FILE_EXTENSION_PDF);

        // Assert
        $expectedPrompt = sprintf($this->tester->createQuickOrderVisualAddToCartConfig()->getQuickOrderVisualAddToCartDocumentPromptTemplate(), static::LOCALE_NAME);
        $this->assertSame($expectedPrompt, $this->getSentPromptMessageTransfer($promptRequestTransfers)->getContent());
    }

    public function testGivenPngWhenRecognizingProductsThenImageAttachmentWithImagePromptIsSent(): void
    {
        // Arrange
        $promptRequestTransfers = new ArrayObject();
        $productRecognizer = $this->createProductRecognizer($promptRequestTransfers, $this->tester->createSuccessfulProductRecognitionPromptResponse(static::RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME));

        // Act
        $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PNG, static::FILE_EXTENSION_PNG);

        // Assert
        $promptMessageTransfer = $this->getSentPromptMessageTransfer($promptRequestTransfers);
        $attachmentTransfer = $promptMessageTransfer->getAttachments()->offsetGet(0);
        $expectedPrompt = sprintf($this->tester->createQuickOrderVisualAddToCartConfig()->getQuickOrderVisualAddToCartImagePromptTemplate(), static::LOCALE_NAME);
        $this->assertSame(
            [AiFoundationConstants::ATTACHMENT_TYPE_IMAGE, static::MIME_TYPE_PNG, AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64, static::BASE64_CONTENT, null],
            [$attachmentTransfer->getType(), $attachmentTransfer->getMediaType(), $attachmentTransfer->getContentType(), $attachmentTransfer->getContent(), $attachmentTransfer->getFilename()],
        );
        $this->assertSame($expectedPrompt, $promptMessageTransfer->getContent());
    }

    public function testGivenSuccessfulAiResponseWhenRecognizingPdfThenAllRecognizedProductsAreReturned(): void
    {
        // Arrange
        $productRecognizer = $this->createProductRecognizer(new ArrayObject(), $this->tester->createSuccessfulProductRecognitionPromptResponse(static::RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME));

        // Act
        $productRecognitionCollectionTransfer = $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PDF, static::FILE_EXTENSION_PDF);

        // Assert
        $this->assertTrue($productRecognitionCollectionTransfer->getIsSuccessful());
        $this->assertCount(count(static::RECOGNIZED_QUANTITIES_BY_PRODUCT_NAME), $productRecognitionCollectionTransfer->getProductRecognitions());
    }

    public function testGivenFailedAiRequestWhenRecognizingPdfThenUnsuccessfulResultWithErrorKeyIsReturned(): void
    {
        // Arrange
        $productRecognizer = $this->createProductRecognizer(new ArrayObject(), $this->tester->createFailedPromptResponse());

        // Act
        $productRecognitionCollectionTransfer = $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PDF, static::FILE_EXTENSION_PDF);

        // Assert
        $this->assertFalse($productRecognitionCollectionTransfer->getIsSuccessful());
        $this->assertSame(static::GLOSSARY_KEY_AI_REQUEST_FAILED, $productRecognitionCollectionTransfer->getErrorMessage());
    }

    public function testGivenSuccessfulAiResponseWithoutStructuredProductsWhenRecognizingPdfThenUnsuccessfulResultWithInvalidResponseKeyIsReturned(): void
    {
        // Arrange
        $productRecognizer = $this->createProductRecognizer(new ArrayObject(), (new PromptResponseTransfer())->setIsSuccessful(true));

        // Act
        $productRecognitionCollectionTransfer = $productRecognizer->recognizeProducts(static::BASE64_CONTENT, static::MIME_TYPE_PDF, static::FILE_EXTENSION_PDF);

        // Assert
        $this->assertFalse($productRecognitionCollectionTransfer->getIsSuccessful());
        $this->assertSame(static::GLOSSARY_KEY_AI_RESPONSE_INVALID, $productRecognitionCollectionTransfer->getErrorMessage());
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PromptRequestTransfer> $promptRequestTransfers
     * @param array<string, mixed> $configMethodOverrides
     */
    protected function createProductRecognizer(
        ArrayObject $promptRequestTransfers,
        PromptResponseTransfer $promptResponseTransfer,
        array $configMethodOverrides = [],
    ): ProductRecognizer {
        $aiFoundationClientMock = $this->createMock(AiFoundationClientInterface::class);
        $aiFoundationClientMock->method('prompt')->willReturnCallback(
            static function (PromptRequestTransfer $promptRequestTransfer) use ($promptRequestTransfers, $promptResponseTransfer): PromptResponseTransfer {
                $promptRequestTransfers->append($promptRequestTransfer);

                return $promptResponseTransfer;
            },
        );

        return new ProductRecognizer(
            $aiFoundationClientMock,
            $this->createConfiguredMock(LocaleClientInterface::class, ['getCurrentLocale' => static::LOCALE_NAME]),
            $this->tester->createQuickOrderVisualAddToCartConfig($configMethodOverrides),
        );
    }

    /**
     * @return array<string, callable>
     */
    protected function createDocumentPromptConfigOverrides(string $documentPrompt): array
    {
        return [
            'getModuleConfig' => fn (string $key, mixed $default = null): mixed => $key === static::CONFIGURATION_KEY_DOCUMENT_RECOGNITION_PROMPT ? $documentPrompt : $default,
        ];
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PromptRequestTransfer> $promptRequestTransfers
     */
    protected function getSentPromptMessageTransfer(ArrayObject $promptRequestTransfers): PromptMessageTransfer
    {
        $promptRequestTransfer = $promptRequestTransfers->offsetGet(0);

        if (!$promptRequestTransfer instanceof PromptRequestTransfer) {
            $this->fail('No prompt request was sent to the AI client.');
        }

        return $promptRequestTransfer->getPromptMessageOrFail();
    }
}
