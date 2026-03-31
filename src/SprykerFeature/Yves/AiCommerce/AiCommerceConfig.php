<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce;

use Spryker\Yves\Kernel\AbstractBundleConfig;

class AiCommerceConfig extends AbstractBundleConfig
{
    /**
     * @api
     */
    public const string SEARCH_BY_IMAGE_REDIRECT_TYPE_SEARCH_RESULTS = 'search_results';

    /**
     * @api
     */
    public const string SEARCH_BY_IMAGE_REDIRECT_TYPE_FIRST_PRODUCT = 'first_product';

    protected const int SEARCH_BY_IMAGE_MAX_IMAGE_SIZE_BYTES = 5_242_880;

    protected const array SEARCH_BY_IMAGE_ALLOWED_IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    protected const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_ENABLED = 'ai_commerce:search_by_image:search_by_image:enabled';

    protected const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_REDIRECT_TYPE = 'ai_commerce:search_by_image:search_by_image:redirect_type';

    /**
     * @var array<string>
     */
    protected const array QUICK_ORDER_IMAGE_TO_CART_SUPPORTED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    /**
     * @var array<string>
     */
    protected const array QUICK_ORDER_IMAGE_TO_CART_SUPPORTED_IMAGE_EXTENSIONS = [
        'png',
        'jpeg',
        'jpg',
    ];

    protected const int QUICK_ORDER_IMAGE_TO_CART_MAX_PRODUCTS = 20;

    protected const int QUICK_ORDER_IMAGE_TO_CART_MAX_FILE_SIZE_IN_BYTES = 10_485_760;

    protected const int QUICK_ORDER_IMAGE_TO_CART_TEXT_SIMILARITY_THRESHOLD_PERCENT = 30;

    protected const string AI_COMMERCE_QUICK_ORDER_VISUAL_ADD_TO_CART_ENABLED = 'ai_commerce:quick_order:visual_add_to_cart:enabled';

    /**
     * Specification:
     * - Returns the list of MIME types accepted for image upload in quick order image-to-cart.
     * - Used to restrict the file input and validate uploaded files.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderImageToCartSupportedMimeTypes(): array
    {
        return static::QUICK_ORDER_IMAGE_TO_CART_SUPPORTED_MIME_TYPES;
    }

    /**
     * Specification:
     * - Returns the list of file extensions accepted for image upload in quick order image-to-cart.
     * - Used to display allowed formats to the user and validate uploaded files.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderImageToCartSupportedImageExtensions(): array
    {
        return static::QUICK_ORDER_IMAGE_TO_CART_SUPPORTED_IMAGE_EXTENSIONS;
    }

    /**
     * Specification:
     * - Returns the maximum allowed file size in bytes for an uploaded image.
     * - Files exceeding this limit are rejected during validation.
     *
     * @api
     */
    public function getQuickOrderImageToCartMaxFileSizeInBytes(): int
    {
        return static::QUICK_ORDER_IMAGE_TO_CART_MAX_FILE_SIZE_IN_BYTES;
    }

    /**
     * Specification:
     * - Returns the maximum number of products that can be recognized from a single image.
     * - Images containing more products than this limit are rejected during validation.
     *
     * @api
     */
    public function getQuickOrderImageToCartMaxProducts(): int
    {
        return static::QUICK_ORDER_IMAGE_TO_CART_MAX_PRODUCTS;
    }

    /**
     * Specification:
     * - Returns the threshold (0-100) for text similarity matching when finding catalog products.
     * - Higher values require stricter matching between AI-recognized product names and catalog entries.
     * - Determines the percentage of keywords that must overlap for a match.
     *
     * @api
     */
    public function getQuickOrderImageToCartTextSimilarityThresholdPercent(): int
    {
        return static::QUICK_ORDER_IMAGE_TO_CART_TEXT_SIMILARITY_THRESHOLD_PERCENT;
    }

    /**
     * Specification:
     * - Returns whether the quick order image-to-cart feature is enabled.
     * - Reads the enabled flag from the application configuration.
     *
     * @api
     */
    public function isQuickOrderImageToCartEnabled(): bool
    {
        return $this->getModuleConfig(static::AI_COMMERCE_QUICK_ORDER_VISUAL_ADD_TO_CART_ENABLED, false);
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for image-to-cart product recognition.
     *
     * @api
     */
    public function getQuickOrderImageToCartAiConfigurationName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the maximum allowed image size in bytes for search by image uploads.
     *
     * @api
     */
    public function getMaxImageSizeBytes(): int
    {
        return static::SEARCH_BY_IMAGE_MAX_IMAGE_SIZE_BYTES;
    }

    /**
     * Specification:
     * - Returns the list of allowed MIME types for search by image uploads.
     *
     * @api
     *
     * @return array<string>
     */
    public function getAllowedImageMimeTypes(): array
    {
        return static::SEARCH_BY_IMAGE_ALLOWED_IMAGE_MIME_TYPES;
    }

    /**
     * Specification:
     * - Returns true if the search by image feature is enabled.
     * - Reads the value from the module configuration using the search by image enabled configuration key.
     * - Defaults to false when the configuration key is not set.
     *
     * @api
     */
    public function isSearchByImageEnabled(): bool
    {
        return (bool)$this->getModuleConfig(
            static::CONFIGURATION_KEY_SEARCH_BY_IMAGE_ENABLED,
            false,
        );
    }

    /**
     * Specification:
     * - Returns the redirect type used after a successful search by image.
     * - Reads the value from the module configuration using the redirect type configuration key.
     * - Defaults to REDIRECT_TYPE_SEARCH_RESULTS when the configuration key is not set.
     *
     * @api
     */
    public function getRedirectType(): string
    {
        return (string)$this->getModuleConfig(
            static::CONFIGURATION_KEY_SEARCH_BY_IMAGE_REDIRECT_TYPE,
            static::SEARCH_BY_IMAGE_REDIRECT_TYPE_SEARCH_RESULTS,
        );
    }
}
