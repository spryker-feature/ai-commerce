<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\Handler;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AttachmentTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Collector\QuickOrderItemCollector;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Encoder\UploadedFileEncoder;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Encoder\UploadedFileEncoderInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\File\Validator\UploadedFileValidator;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Finder\CatalogProductFinder;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\Constraint\VisualAddToCartFormConstraint;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\Handler\VisualAddToCartFormHandler;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Form\VisualAddToCartForm;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Matcher\ProductNameMatcher;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Message\NotFoundProductNotifier;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\ProductRecognizer;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\Validator\ProductRecognitionValidator;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\Validator\Rule\NonEmptyProductCollectionProductValidationRule;
use SprykerFeature\Yves\AiCommerce\QuickOrderVisualAddToCart\Product\Validator\Rule\ProductLimitProductValidationRule;
use SprykerFeatureTest\Yves\AiCommerce\AiCommerceYvesTester;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group QuickOrderVisualAddToCart
 * @group Form
 * @group Handler
 * @group VisualAddToCartFormHandlerTest
 */
class VisualAddToCartFormHandlerTest extends Unit
{
    protected const string FORM_NAME = 'orderFileForm';

    protected const string LOCALE_NAME = 'en_US';

    protected const string GLOSSARY_KEY_INVALID_MIME_TYPE = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.invalid-mime-type';

    protected const string GLOSSARY_KEY_FILE_TOO_LARGE = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.file-too-large';

    protected const string GLOSSARY_KEY_FILE_READ_FAILED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.file-read-failed';

    protected const string GLOSSARY_KEY_AI_REQUEST_FAILED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.ai-request-failed';

    protected const string GLOSSARY_KEY_NO_PRODUCTS_RECOGNIZED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.no-products-recognized';

    protected const string GLOSSARY_KEY_PRODUCT_LIMIT_EXCEEDED = 'ai-commerce.quick-order-visual-add-to-cart.order-file.errors.product-limit-exceeded';

    protected const int MAX_PRODUCTS_SMALLER_THAN_ALL_PAGES = 2;

    protected const int MAX_FILE_SIZE_SMALLER_THAN_FIXTURES = 1000;

    protected const string UNKNOWN_PRODUCT_NAME = 'Unknown Boiler Part';

    protected const string KEY_SUGGESTION_BY_TYPE = 'suggestionByType';

    protected const string KEY_PRODUCT_CONCRETE = 'product_concrete';

    protected const string KEY_SKU = 'sku';

    protected const string KEY_NAME = 'name';

    protected const array QUANTITIES_BY_PRODUCT_NAME_OF_ALL_PAGES = [
        'Industrial Heat Pump 60kW' => 1,
        'Temperature Sensor PT100' => 3,
        'Spring-Loaded Safety Valve DN40' => 2,
        'Circulation Pump Seal and Bearing Kit' => 1,
        'Valve Packing and Gasket Kit' => 2,
        'Expansion Tank for Heat Systems' => 1,
    ];

    protected const array SKUS_BY_PRODUCT_NAME = [
        'Industrial Heat Pump 60kW' => 'HP-IND-60KW',
        'Temperature Sensor PT100' => 'TS-PT100',
        'Spring-Loaded Safety Valve DN40' => 'SV-SL-DN40',
        'Circulation Pump Seal and Bearing Kit' => 'CPS-KIT-STD',
        'Valve Packing and Gasket Kit' => 'VP-GSK-KIT',
        'Expansion Tank for Heat Systems' => 'ET-HS-80L',
    ];

    protected const array QUANTITIES_BY_PRODUCT_NAME_WITH_UNKNOWN_PRODUCT = [
        'Industrial Heat Pump 60kW' => 1,
        self::UNKNOWN_PRODUCT_NAME => 2,
    ];

    protected const array EXPECTED_QUANTITIES_BY_SKU_WITHOUT_UNKNOWN_PRODUCT = [
        'HP-IND-60KW' => 1,
    ];

    protected const array EXPECTED_QUANTITIES_BY_SKU = [
        'HP-IND-60KW' => 1,
        'TS-PT100' => 3,
        'SV-SL-DN40' => 2,
        'CPS-KIT-STD' => 1,
        'VP-GSK-KIT' => 2,
        'ET-HS-80L' => 1,
    ];

    protected const string MIME_TYPE_PDF = 'application/pdf';

    protected const string MIME_TYPE_PNG = 'image/png';

    protected const string REJECTED_UPLOAD_FILE_NAME = 'order-form.pdf';

    protected AiCommerceYvesTester $tester;

    /**
     * The whole PDF goes to the AI as one document attachment in exactly one request; the mocked response holds the
     * line items of both pages of the multi-page demo order form (three on page 1, three on page 2).
     */
    public function testGivenMultiPagePdfWhenHandlingFormThenLineItemsOfAllPagesAreReturnedAsOneQuickOrderList(): void
    {
        // Arrange
        $promptRequestTransfers = new ArrayObject();
        $aiFoundationClientMock = $this->createAiFoundationClientMock($this->createAllPagesPromptResponse(), 1, AiFoundationConstants::ATTACHMENT_TYPE_DOCUMENT, $promptRequestTransfers);
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();

        // Act
        $quickOrderItemTransfers = $this->createVisualAddToCartFormHandler($aiFoundationClientMock, $config)
            ->handleForm($this->createVisualAddToCartForm($config), $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF));

        // Assert
        $attachmentTransfer = $this->getSentAttachmentTransfer($promptRequestTransfers);
        $this->assertSame(static::EXPECTED_QUANTITIES_BY_SKU, $this->mapQuantitiesBySku($quickOrderItemTransfers));
        $this->assertSame(
            [static::MIME_TYPE_PDF, $this->encodeFixture(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF)],
            [$attachmentTransfer->getMediaType(), $attachmentTransfer->getContent()],
        );
    }

    public function testGivenPngWhenHandlingFormThenImageIsSentAsImageAttachment(): void
    {
        // Arrange
        $promptRequestTransfers = new ArrayObject();
        $aiFoundationClientMock = $this->createAiFoundationClientMock($this->createAllPagesPromptResponse(), 1, AiFoundationConstants::ATTACHMENT_TYPE_IMAGE, $promptRequestTransfers);
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();

        // Act
        $this->createVisualAddToCartFormHandler($aiFoundationClientMock, $config)
            ->handleForm($this->createVisualAddToCartForm($config), $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_PNG));

        // Assert
        $attachmentTransfer = $this->getSentAttachmentTransfer($promptRequestTransfers);
        $this->assertSame(
            [AiFoundationConstants::ATTACHMENT_TYPE_IMAGE, static::MIME_TYPE_PNG, $this->encodeFixture(AiCommerceYvesTester::FIXTURE_PNG)],
            [$attachmentTransfer->getType(), $attachmentTransfer->getMediaType(), $attachmentTransfer->getContent()],
        );
    }

    public function testGivenTextFileWhenHandlingFormThenNoItemsAreReturnedAndAiIsNotCalled(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();
        $orderFileForm = $this->createVisualAddToCartForm($config);

        // Act
        $quickOrderItemTransfers = $this->createVisualAddToCartFormHandler($this->createAiFoundationClientMock($this->createAllPagesPromptResponse(), 0), $config)
            ->handleForm($orderFileForm, $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_TEXT));

        // Assert
        $this->assertSame([], $quickOrderItemTransfers);
        $this->assertStringContainsString(static::GLOSSARY_KEY_INVALID_MIME_TYPE, (string)$orderFileForm->getErrors(true));
    }

    public function testGivenPdfLargerThanMaxFileSizeWhenHandlingFormThenNoItemsAreReturnedAndAiIsNotCalled(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig([
            'getQuickOrderVisualAddToCartMaxFileSizeInBytes' => static::MAX_FILE_SIZE_SMALLER_THAN_FIXTURES,
        ]);
        $orderFileForm = $this->createVisualAddToCartForm($config);

        // Act
        $quickOrderItemTransfers = $this->createVisualAddToCartFormHandler($this->createAiFoundationClientMock($this->createAllPagesPromptResponse(), 0), $config)
            ->handleForm($orderFileForm, $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF));

        // Assert
        $this->assertSame([], $quickOrderItemTransfers);
        $this->assertStringContainsString(static::GLOSSARY_KEY_FILE_TOO_LARGE, (string)$orderFileForm->getErrors(true));
    }

    /**
     * PHP rejected the upload itself (here: over upload_max_filesize), so there is no temporary file to inspect.
     * FileType reports the upload error, and the constraint must not read the missing file.
     */
    public function testGivenUploadRejectedByPhpWhenHandlingFormThenNoItemsAreReturnedAndAiIsNotCalled(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();
        $orderFileForm = $this->createVisualAddToCartForm($config);
        $request = Request::create(
            '/quick-order',
            Request::METHOD_POST,
            [VisualAddToCartForm::SUBMIT_BUTTON_UPLOAD_ORDER_FILE => ''],
            [],
            [static::FORM_NAME => [VisualAddToCartForm::FIELD_ORDER_FILE => new UploadedFile('', static::REJECTED_UPLOAD_FILE_NAME, null, UPLOAD_ERR_INI_SIZE, true)]],
        );

        // Act
        $quickOrderItemTransfers = $this->createVisualAddToCartFormHandler($this->createAiFoundationClientMock($this->createAllPagesPromptResponse(), 0), $config)
            ->handleForm($orderFileForm, $request);

        // Assert
        $this->assertSame([], $quickOrderItemTransfers);
        $this->assertFalse($orderFileForm->isValid());
    }

    public function testGivenUnreadableUploadWhenHandlingFormThenNoItemsAreReturnedWithFileReadError(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();
        $orderFileForm = $this->createVisualAddToCartForm($config);
        $visualAddToCartFormHandler = $this->createVisualAddToCartFormHandler(
            $this->createAiFoundationClientMock($this->createAllPagesPromptResponse(), 0),
            $config,
            $this->createConfiguredMock(UploadedFileEncoderInterface::class, ['encodeToBase64' => null]),
        );

        // Act
        $quickOrderItemTransfers = $visualAddToCartFormHandler->handleForm($orderFileForm, $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF));

        // Assert
        $this->assertSame([], $quickOrderItemTransfers);
        $this->assertStringContainsString(static::GLOSSARY_KEY_FILE_READ_FAILED, (string)$orderFileForm->getErrors(true));
    }

    public function testGivenFailedAiRequestWhenHandlingFormThenNoItemsAreReturnedWithAiRequestError(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();
        $orderFileForm = $this->createVisualAddToCartForm($config);

        // Act
        $quickOrderItemTransfers = $this->createVisualAddToCartFormHandler($this->createAiFoundationClientMock($this->tester->createFailedPromptResponse()), $config)
            ->handleForm($orderFileForm, $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF));

        // Assert
        $this->assertSame([], $quickOrderItemTransfers);
        $this->assertStringContainsString(static::GLOSSARY_KEY_AI_REQUEST_FAILED, (string)$orderFileForm->getErrors(true));
    }

    public function testGivenAiRecognizesNoProductsWhenHandlingFormThenNoItemsAreReturnedWithNoProductsError(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();
        $orderFileForm = $this->createVisualAddToCartForm($config);
        $aiFoundationClientMock = $this->createAiFoundationClientMock($this->tester->createSuccessfulProductRecognitionPromptResponse([]));

        // Act
        $quickOrderItemTransfers = $this->createVisualAddToCartFormHandler($aiFoundationClientMock, $config)
            ->handleForm($orderFileForm, $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF));

        // Assert
        $this->assertSame([], $quickOrderItemTransfers);
        $this->assertStringContainsString(static::GLOSSARY_KEY_NO_PRODUCTS_RECOGNIZED, (string)$orderFileForm->getErrors(true));
    }

    public function testGivenMoreRecognizedProductsThanTheLimitWhenHandlingFormThenNoItemsAreReturnedWithLimitError(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig([
            'getQuickOrderVisualAddToCartMaxProducts' => static::MAX_PRODUCTS_SMALLER_THAN_ALL_PAGES,
        ]);
        $orderFileForm = $this->createVisualAddToCartForm($config);

        // Act
        $quickOrderItemTransfers = $this->createVisualAddToCartFormHandler($this->createAiFoundationClientMock($this->createAllPagesPromptResponse()), $config)
            ->handleForm($orderFileForm, $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF));

        // Assert
        $this->assertSame([], $quickOrderItemTransfers);
        $this->assertStringContainsString(static::GLOSSARY_KEY_PRODUCT_LIMIT_EXCEEDED, (string)$orderFileForm->getErrors(true));
    }

    public function testGivenRecognizedProductMissingFromCatalogWhenHandlingFormThenMatchedItemsAreReturnedAndMissingProductIsReported(): void
    {
        // Arrange
        $config = $this->tester->createQuickOrderVisualAddToCartConfig();
        $flashMessengerMock = $this->createMock(FlashMessengerInterface::class);
        $flashMessengerMock->expects($this->once())->method('addErrorMessage');
        $visualAddToCartFormHandler = $this->createVisualAddToCartFormHandler(
            $this->createAiFoundationClientMock($this->tester->createSuccessfulProductRecognitionPromptResponse(static::QUANTITIES_BY_PRODUCT_NAME_WITH_UNKNOWN_PRODUCT)),
            $config,
            new UploadedFileEncoder(),
            $flashMessengerMock,
        );

        // Act
        $quickOrderItemTransfers = $visualAddToCartFormHandler->handleForm($this->createVisualAddToCartForm($config), $this->createUploadRequest(AiCommerceYvesTester::FIXTURE_MULTI_PAGE_PDF));

        // Assert
        $this->assertSame(static::EXPECTED_QUANTITIES_BY_SKU_WITHOUT_UNKNOWN_PRODUCT, $this->mapQuantitiesBySku($quickOrderItemTransfers));
    }

    protected function createAllPagesPromptResponse(): PromptResponseTransfer
    {
        return $this->tester->createSuccessfulProductRecognitionPromptResponse(static::QUANTITIES_BY_PRODUCT_NAME_OF_ALL_PAGES);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PromptRequestTransfer>|null $promptRequestTransfers
     */
    protected function createAiFoundationClientMock(
        PromptResponseTransfer $promptResponseTransfer,
        int $expectedPromptCallCount = 1,
        string $expectedAttachmentType = AiFoundationConstants::ATTACHMENT_TYPE_DOCUMENT,
        ?ArrayObject $promptRequestTransfers = null
    ): AiFoundationClientInterface {
        $aiFoundationClientMock = $this->createMock(AiFoundationClientInterface::class);
        $aiFoundationClientMock->expects($this->exactly($expectedPromptCallCount))
            ->method('prompt')
            ->with($this->callback(static function (PromptRequestTransfer $promptRequestTransfer) use ($expectedAttachmentType, $promptRequestTransfers): bool {
                $promptRequestTransfers?->append($promptRequestTransfer);

                return $promptRequestTransfer->getPromptMessageOrFail()->getAttachments()->offsetGet(0)->getType() === $expectedAttachmentType;
            }))
            ->willReturn($promptResponseTransfer);

        return $aiFoundationClientMock;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PromptRequestTransfer> $promptRequestTransfers
     */
    protected function getSentAttachmentTransfer(ArrayObject $promptRequestTransfers): AttachmentTransfer
    {
        $promptRequestTransfer = $promptRequestTransfers->offsetGet(0);

        if (!$promptRequestTransfer instanceof PromptRequestTransfer) {
            $this->fail('No prompt request was sent to the AI client.');
        }

        return $promptRequestTransfer->getPromptMessageOrFail()->getAttachments()->offsetGet(0);
    }

    protected function encodeFixture(string $fixtureFileName): string
    {
        $uploadedFile = $this->tester->createQuickOrderVisualAddToCartUploadedFile($fixtureFileName);

        return base64_encode((string)file_get_contents($uploadedFile->getPathname()));
    }

    protected function createVisualAddToCartFormHandler(
        AiFoundationClientInterface $aiFoundationClient,
        AiCommerceConfig $config,
        ?UploadedFileEncoderInterface $uploadedFileEncoder = null,
        ?FlashMessengerInterface $flashMessenger = null,
    ): VisualAddToCartFormHandler {
        $localeClient = $this->createConfiguredMock(LocaleClientInterface::class, ['getCurrentLocale' => static::LOCALE_NAME]);

        return new VisualAddToCartFormHandler(
            $uploadedFileEncoder ?? new UploadedFileEncoder(),
            new ProductRecognizer($aiFoundationClient, $localeClient, $config),
            new ProductRecognitionValidator([
                new NonEmptyProductCollectionProductValidationRule(),
                new ProductLimitProductValidationRule($config),
            ]),
            new QuickOrderItemCollector(
                new CatalogProductFinder($this->createCatalogClientMock(), $this->createMock(ProductStorageClientInterface::class), $localeClient),
                new ProductNameMatcher($config),
            ),
            new NotFoundProductNotifier($flashMessenger ?? $this->createMock(FlashMessengerInterface::class), $this->createMock(TranslatorInterface::class)),
        );
    }

    protected function createCatalogClientMock(): CatalogClientInterface
    {
        $catalogClientMock = $this->createMock(CatalogClientInterface::class);
        $catalogClientMock->method('catalogSuggestMultiSearch')->willReturnCallback(
            static fn (array $searchStrings): array => array_map(
                static fn (string $productName): array => isset(static::SKUS_BY_PRODUCT_NAME[$productName]) ? [
                    static::KEY_SUGGESTION_BY_TYPE => [
                        static::KEY_PRODUCT_CONCRETE => [
                            [static::KEY_SKU => static::SKUS_BY_PRODUCT_NAME[$productName], static::KEY_NAME => $productName],
                        ],
                    ],
                ] : [],
                $searchStrings,
            ),
        );

        return $catalogClientMock;
    }

    protected function createVisualAddToCartForm(AiCommerceConfig $config): FormInterface
    {
        $visualAddToCartFormConstraint = new VisualAddToCartFormConstraint([
            VisualAddToCartFormConstraint::OPTION_UPLOADED_FILE_VALIDATOR => new UploadedFileValidator($config),
        ]);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory()
            ->createNamedBuilder(static::FORM_NAME)
            ->add(VisualAddToCartForm::FIELD_ORDER_FILE, FileType::class, ['constraints' => [$visualAddToCartFormConstraint]])
            ->getForm();
    }

    protected function createUploadRequest(string $fixtureFileName): Request
    {
        return Request::create(
            '/quick-order',
            Request::METHOD_POST,
            [VisualAddToCartForm::SUBMIT_BUTTON_UPLOAD_ORDER_FILE => ''],
            [],
            [static::FORM_NAME => [VisualAddToCartForm::FIELD_ORDER_FILE => $this->tester->createQuickOrderVisualAddToCartUploadedFile($fixtureFileName)]],
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\QuickOrderItemTransfer> $quickOrderItemTransfers
     *
     * @return array<string, int>
     */
    protected function mapQuantitiesBySku(array $quickOrderItemTransfers): array
    {
        $quantitiesBySku = [];

        foreach ($quickOrderItemTransfers as $quickOrderItemTransfer) {
            $quantitiesBySku[$quickOrderItemTransfer->getSkuOrFail()] = $quickOrderItemTransfer->getQuantityOrFail();
        }

        return $quantitiesBySku;
    }
}
