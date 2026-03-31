<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce;

use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Shared\Kernel\StrategyResolver;
use Spryker\Shared\Kernel\StrategyResolverInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Collector\QuickOrderItemCollector;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Collector\QuickOrderItemCollectorInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Finder\CatalogProductFinder;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Finder\CatalogProductFinderInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\Constraint\ImageOrderFormConstraint;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\Handler\ImageOrderFormHandler;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\Handler\ImageOrderFormHandlerInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Form\ImageOrderForm;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Encoder\UploadedImageEncoder;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Encoder\UploadedImageEncoderInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Validator\UploadedImageValidator;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Image\Validator\UploadedImageValidatorInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Matcher\ProductNameMatcher;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Matcher\ProductNameMatcherInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Message\NotFoundProductNotifier;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Message\NotFoundProductNotifierInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\ProductImageRecognizer;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\ProductImageRecognizerInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\ProductRecognitionValidator;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\ProductRecognitionValidatorInterface;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\Rule\NonEmptyProductCollectionProductValidationRule;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\Rule\ProductLimitProductValidationRule;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\Rule\ProductValidationRuleInterface;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Executor\SearchByImageExecutor;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Executor\SearchByImageExecutorInterface;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Form\DataTransformer\UploadedImageTransformer;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Form\SearchByImageForm;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\FirstProductRedirectResolver;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\RedirectResolverInterface;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\SearchResultsRedirectResolver;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method \SprykerFeature\Yves\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Client\AiCommerce\AiCommerceClientInterface getClient()
 */
class AiCommerceFactory extends AbstractFactory
{
    public function createImageOrderForm(): FormInterface
    {
        return $this->getFormFactory()->create(ImageOrderForm::class);
    }

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->getProvidedDependency(ApplicationConstants::FORM_FACTORY);
    }

    public function createImageOrderFormHandler(): ImageOrderFormHandlerInterface
    {
        return new ImageOrderFormHandler(
            $this->createUploadedImageEncoder(),
            $this->createProductImageRecognizer(),
            $this->createProductRecognitionValidator(),
            $this->createQuickOrderItemCollector(),
            $this->createNotFoundProductNotifier(),
        );
    }

    public function createUploadedImageEncoder(): UploadedImageEncoderInterface
    {
        return new UploadedImageEncoder();
    }

    public function createProductImageRecognizer(): ProductImageRecognizerInterface
    {
        return new ProductImageRecognizer(
            $this->getAiFoundationClient(),
            $this->getLocaleClient(),
            $this->getConfig(),
        );
    }

    public function createQuickOrderItemCollector(): QuickOrderItemCollectorInterface
    {
        return new QuickOrderItemCollector(
            $this->createCatalogProductFinder(),
            $this->createProductNameMatcher(),
        );
    }

    public function createCatalogProductFinder(): CatalogProductFinderInterface
    {
        return new CatalogProductFinder(
            $this->getCatalogClient(),
        );
    }

    public function createProductNameMatcher(): ProductNameMatcherInterface
    {
        return new ProductNameMatcher(
            $this->getConfig(),
        );
    }

    public function createNotFoundProductNotifier(): NotFoundProductNotifierInterface
    {
        return new NotFoundProductNotifier(
            $this->getFlashMessengerService(),
            $this->getTranslatorService(),
        );
    }

    public function createImageOrderFormConstraint(): ImageOrderFormConstraint
    {
        return new ImageOrderFormConstraint(
            [
                ImageOrderFormConstraint::OPTION_UPLOADED_IMAGE_VALIDATOR => $this->createUploadedImageValidator(),
            ],
        );
    }

    public function createUploadedImageValidator(): UploadedImageValidatorInterface
    {
        return new UploadedImageValidator(
            $this->getConfig(),
        );
    }

    public function createProductRecognitionValidator(): ProductRecognitionValidatorInterface
    {
        return new ProductRecognitionValidator(
            $this->getProductValidationRules(),
        );
    }

    public function createNonEmptyProductCollectionProductValidationRule(): ProductValidationRuleInterface
    {
        return new NonEmptyProductCollectionProductValidationRule();
    }

    public function createProductLimitProductValidationRule(): ProductValidationRuleInterface
    {
        return new ProductLimitProductValidationRule(
            $this->getConfig(),
        );
    }

    public function getSearchByImageForm(): FormInterface
    {
        return $this->getFormFactory()->create(SearchByImageForm::class);
    }

    public function createUploadedImageTransformer(): UploadedImageTransformer
    {
        return new UploadedImageTransformer(SearchByImageForm::FIELD_IMAGE);
    }

    public function createSearchByImageExecutor(): SearchByImageExecutorInterface
    {
        return new SearchByImageExecutor(
            $this->getClient(),
            $this->createRedirectStrategyResolver(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\SprykerFeature\Yves\AiCommerce\SearchByImage\Redirect\RedirectResolverInterface>
     */
    public function createRedirectStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver(
            [
                AiCommerceConfig::SEARCH_BY_IMAGE_REDIRECT_TYPE_SEARCH_RESULTS => $this->createSearchResultsRedirectResolver(),
                AiCommerceConfig::SEARCH_BY_IMAGE_REDIRECT_TYPE_FIRST_PRODUCT => $this->createFirstProductRedirectResolver(),
            ],
            AiCommerceConfig::SEARCH_BY_IMAGE_REDIRECT_TYPE_SEARCH_RESULTS,
        );
    }

    public function createSearchResultsRedirectResolver(): RedirectResolverInterface
    {
        return new SearchResultsRedirectResolver($this->getRouter());
    }

    public function createFirstProductRedirectResolver(): RedirectResolverInterface
    {
        return new FirstProductRedirectResolver(
            $this->getCatalogClient(),
            $this->createSearchResultsRedirectResolver(),
        );
    }

    /**
     * @return array<\SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Product\Validator\Rule\ProductValidationRuleInterface>
     */
    public function getProductValidationRules(): array
    {
        return [
            $this->createNonEmptyProductCollectionProductValidationRule(),
            $this->createProductLimitProductValidationRule(),
        ];
    }

    public function getAiFoundationClient(): AiFoundationClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION);
    }

    public function getCatalogClient(): CatalogClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_CATALOG);
    }

    public function getLocaleClient(): LocaleClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_LOCALE);
    }

    public function getFlashMessengerService(): FlashMessengerInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::SERVICE_FLASH_MESSENGER);
    }

    public function getTranslatorService(): TranslatorInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::SERVICE_TRANSLATOR);
    }

    public function getRouter(): UrlGeneratorInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::SERVICE_ROUTER);
    }

    public function getGlossaryStorageClient(): GlossaryStorageClientInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::CLIENT_GLOSSARY_STORAGE);
    }
}
