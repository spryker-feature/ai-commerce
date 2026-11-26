<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\AiCommerce;

use Spryker\Yves\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;

/**
 * @method \SprykerFeature\Shared\AiCommerce\AiCommerceConfig getSharedConfig()
 */
class AiCommerceConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Defines the redirect type that navigates to search results page after search by image.
     *
     * @api
     */
    public const string SEARCH_BY_IMAGE_REDIRECT_TYPE_SEARCH_RESULTS = 'search_results';

    /**
     * Specification:
     * - Defines the redirect type that navigates to the first matched product page after search by image.
     *
     * @api
     */
    public const string SEARCH_BY_IMAGE_REDIRECT_TYPE_FIRST_PRODUCT = 'first_product';

    protected const int SEARCH_BY_IMAGE_MAX_IMAGE_SIZE_BYTES = 5_242_880;

    protected const array SEARCH_BY_IMAGE_ALLOWED_IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    protected const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_ENABLED = 'ai_commerce:search_by_image:search_by_image:enabled';

    protected const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_REDIRECT_TYPE = 'ai_commerce:search_by_image:search_by_image:redirect_type';

    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_PRODUCT_PAGE_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:product_page_prompts';

    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_CATEGORY_PAGE_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:category_page_prompts';

    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_SEARCH_PAGE_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:search_page_prompts';

    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_DEFAULT_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:default_prompts';

    protected const string CONFIGURATION_KEY_STOREFRONT_ASSISTANT_PRODUCT_RESULTS_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:product_results_prompts';

    /**
     * @var non-empty-string
     */
    protected const string STOREFRONT_ASSISTANT_PROMPT_GROUP_PRODUCT_RESULTS = 'product_results';

    protected const array QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    protected const array QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_IMAGE_EXTENSIONS = [
        'png',
        'jpeg',
        'jpg',
    ];

    protected const array QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_DOCUMENT_MIME_TYPES = [
        'application/pdf',
    ];

    protected const array QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_DOCUMENT_EXTENSIONS = [
        'pdf',
    ];

    protected const int QUICK_ORDER_VISUAL_ADD_TO_CART_MAX_PRODUCTS = 20;

    protected const int QUICK_ORDER_VISUAL_ADD_TO_CART_MAX_FILE_SIZE_IN_BYTES = 10_485_760;

    protected const int QUICK_ORDER_VISUAL_ADD_TO_CART_TEXT_SIMILARITY_THRESHOLD_PERCENT = 30;

    protected const string AI_COMMERCE_QUICK_ORDER_VISUAL_ADD_TO_CART_ENABLED = 'ai_commerce:quick_order:visual_add_to_cart:enabled';

    protected const string CONFIGURATION_KEY_QUICK_ORDER_DOCUMENT_RECOGNITION_PROMPT = 'ai_commerce:quick_order:system_prompts:document_recognition_prompt';

    protected const string QUICK_ORDER_VISUAL_ADD_TO_CART_IMAGE_PROMPT_TEMPLATE = 'I want you to support me for a quick add to cart functionality by identifying from a picture what the customer want to buy and provide me only with a JSON containing a list of products and quantities. If the user asks for a product to be compatible with any other product include it as part of each product name. Important! Your response must only contain the valid JSON object without any special or additional chars. Important! The image may contain SKU instead of product name, so use SKU as product name if it is recognized. Important! Current store locale is %s — translate recognized product names into the corresponding store locale if necessary.';

    protected const string QUICK_ORDER_VISUAL_ADD_TO_CART_DOCUMENT_PROMPT_TEMPLATE = 'I want you to support me for a quick add to cart functionality by reading the attached purchase order, order form or order list and provide me only with a JSON containing a list of products and quantities. The document may have several pages and may be scanned. Read every page and put the order lines of all pages into one list. Include only ordered line items: skip headers, addresses, subtotals, taxes, totals, shipping costs and notes. Use the product description of each line as the product name, and use the item number or SKU only when a line has no description. Use the ordered quantity of each line as an integer, or 1 when a line has no quantity. If a line or a cell lists several SKUs, item numbers or products, for example separated by commas, semicolons or slashes, return one entry for each of them. Copy SKUs and item numbers exactly as written and never translate them. Important! Your response must only contain the valid JSON object without any special or additional chars. Important! Current store locale is %s — translate recognized product names into the corresponding store locale if necessary.';

    /**
     * Specification:
     * - Returns the list of image MIME types accepted for upload in Visual add to cart.
     * - Used to restrict the file input and validate uploaded files.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderVisualAddToCartSupportedImageMimeTypes(): array
    {
        return static::QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_IMAGE_MIME_TYPES;
    }

    /**
     * Specification:
     * - Returns the list of image file extensions accepted for upload in Visual add to cart.
     * - Used to display allowed formats to the user and validate uploaded files.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderVisualAddToCartSupportedImageExtensions(): array
    {
        return static::QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_IMAGE_EXTENSIONS;
    }

    /**
     * Specification:
     * - Returns the list of document MIME types accepted for upload in Visual add to cart, in addition to the image MIME types.
     * - A file of one of these MIME types is sent to the AI as a document attachment, so every page of a multi-page file is read.
     * - Return an empty list to accept images only. Do so when the selected AI configuration uses `AiFoundationConstants::PROVIDER_OLLAMA`: its message mapper drops document attachments without an error.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderVisualAddToCartSupportedDocumentMimeTypes(): array
    {
        return static::QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_DOCUMENT_MIME_TYPES;
    }

    /**
     * Specification:
     * - Returns the list of document file extensions accepted for upload in Visual add to cart, in addition to the image extensions.
     * - Used to display allowed formats to the user and validate uploaded files.
     * - Return an empty list to accept images only, together with `getQuickOrderVisualAddToCartSupportedDocumentMimeTypes()`.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderVisualAddToCartSupportedDocumentExtensions(): array
    {
        return static::QUICK_ORDER_VISUAL_ADD_TO_CART_SUPPORTED_DOCUMENT_EXTENSIONS;
    }

    /**
     * Specification:
     * - Returns every MIME type accepted for upload in Visual add to cart: the image MIME types followed by the document MIME types.
     * - Used to restrict the file input and validate uploaded files.
     * - Override the image or document getter to change the list. This getter only combines them.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderVisualAddToCartSupportedFileMimeTypes(): array
    {
        return array_merge(
            $this->getQuickOrderVisualAddToCartSupportedImageMimeTypes(),
            $this->getQuickOrderVisualAddToCartSupportedDocumentMimeTypes(),
        );
    }

    /**
     * Specification:
     * - Returns every file extension accepted for upload in Visual add to cart: the image extensions followed by the document extensions.
     * - Used to display allowed formats to the user and validate uploaded files.
     * - Override the image or document getter to change the list. This getter only combines them.
     *
     * @api
     *
     * @return array<string>
     */
    public function getQuickOrderVisualAddToCartSupportedFileExtensions(): array
    {
        return array_merge(
            $this->getQuickOrderVisualAddToCartSupportedImageExtensions(),
            $this->getQuickOrderVisualAddToCartSupportedDocumentExtensions(),
        );
    }

    /**
     * Specification:
     * - Returns the maximum allowed file size in bytes for an uploaded image or document.
     * - Files exceeding this limit are rejected during validation.
     *
     * @api
     */
    public function getQuickOrderVisualAddToCartMaxFileSizeInBytes(): int
    {
        return static::QUICK_ORDER_VISUAL_ADD_TO_CART_MAX_FILE_SIZE_IN_BYTES;
    }

    /**
     * Specification:
     * - Returns the maximum number of products that can be recognized from a single uploaded file.
     * - Files containing more products than this limit are rejected during validation.
     *
     * @api
     */
    public function getQuickOrderVisualAddToCartMaxProducts(): int
    {
        return static::QUICK_ORDER_VISUAL_ADD_TO_CART_MAX_PRODUCTS;
    }

    /**
     * Specification:
     * - Returns the threshold (0-100) for text similarity matching when finding catalog products.
     * - Higher values require stricter matching between AI-recognized product names and catalog entries.
     * - Determines the percentage of keywords that must overlap for a match.
     *
     * @api
     */
    public function getQuickOrderVisualAddToCartTextSimilarityThresholdPercent(): int
    {
        return static::QUICK_ORDER_VISUAL_ADD_TO_CART_TEXT_SIMILARITY_THRESHOLD_PERCENT;
    }

    /**
     * Specification:
     * - Returns whether the Visual add to cart feature on the Quick Order page is enabled.
     * - Reads the enabled flag from the application configuration.
     *
     * @api
     */
    public function isQuickOrderVisualAddToCartEnabled(): bool
    {
        return $this->getModuleConfig(static::AI_COMMERCE_QUICK_ORDER_VISUAL_ADD_TO_CART_ENABLED, false);
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for Visual add to cart product recognition.
     *
     * @api
     */
    public function getQuickOrderVisualAddToCartAiConfigurationName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the prompt used to recognize products from an uploaded image on the Quick Order page.
     * - Resolves the value from Configuration Management; falls back to the module default when unset or blank.
     * - Contains a single `%s` placeholder for the current store locale, substituted by the caller.
     *
     * @api
     */
    public function getQuickOrderVisualAddToCartImagePromptTemplate(): string
    {
        $systemPrompt = (string)$this->getModuleConfig(
            AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_IMAGE_RECOGNITION_PROMPT,
            static::QUICK_ORDER_VISUAL_ADD_TO_CART_IMAGE_PROMPT_TEMPLATE,
        );
        if (trim($systemPrompt) === '') {
            return static::QUICK_ORDER_VISUAL_ADD_TO_CART_IMAGE_PROMPT_TEMPLATE;
        }

        return $systemPrompt;
    }

    /**
     * Specification:
     * - Returns the prompt used to recognize products from an uploaded document (for example a PDF order form) on the Quick Order page.
     * - Resolves the value from Configuration Management.
     * - Falls back to the module default when the value is unset or blank.
     * - Contains a single `%s` placeholder for the current store locale, substituted by the caller.
     *
     * @api
     */
    public function getQuickOrderVisualAddToCartDocumentPromptTemplate(): string
    {
        $systemPrompt = (string)$this->getModuleConfig(
            static::CONFIGURATION_KEY_QUICK_ORDER_DOCUMENT_RECOGNITION_PROMPT,
            static::QUICK_ORDER_VISUAL_ADD_TO_CART_DOCUMENT_PROMPT_TEMPLATE,
        );

        if (trim($systemPrompt) === '') {
            return static::QUICK_ORDER_VISUAL_ADD_TO_CART_DOCUMENT_PROMPT_TEMPLATE;
        }

        return $systemPrompt;
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

    /**
     * Specification:
     * - Returns true when the Storefront Assistant feature is enabled.
     * - Reads the value from Configuration Management on every call so a Back Office change takes effect without a deployment.
     * - Defaults to false when the configuration key is not set.
     *
     * @api
     */
    public function isStorefrontAssistantEnabled(): bool
    {
        return (bool)filter_var(
            $this->getModuleConfig(
                $this->getSharedConfig()->getStorefrontAssistantEnabledKey(),
                $this->getSharedConfig()->isStorefrontAssistantEnabledByDefault(),
            ),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Specification:
     * - Returns the MIME types a customer may attach to a Storefront Assistant message.
     * - Single source of truth shared with the Client validator so the browser hint and the server enforcement never diverge.
     *
     * @api
     *
     * @return list<string>
     */
    public function getStorefrontAssistantSupportedAttachmentMimeTypes(): array
    {
        return $this->getSharedConfig()->getStorefrontAssistantSupportedAttachmentMimeTypes();
    }

    /**
     * Specification:
     * - Returns the maximum decoded byte size a single Storefront Assistant attachment may reach.
     * - Single source of truth shared with the Client validator so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentSizeBytes(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxAttachmentSizeBytes();
    }

    /**
     * Specification:
     * - Returns the maximum number of attachments a single Storefront Assistant message may carry.
     * - Single source of truth shared with the Client validator so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxAttachmentCount(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxAttachmentCount();
    }

    /**
     * Specification:
     * - Returns the maximum number of characters a single Storefront Assistant message may reach.
     * - Derived from the conversation history context window so the limit follows a project that resizes it.
     * - Never returns less than the floor that keeps ordinary customer questions acceptable.
     * - Single source of truth shared with the Client validator so the browser hint and the server enforcement never diverge.
     *
     * @api
     */
    public function getStorefrontAssistantMaxMessageLengthCharacters(): int
    {
        return $this->getSharedConfig()->getStorefrontAssistantMaxMessageLengthCharacters();
    }

    /**
     * Specification:
     * - Returns the field names of each project page type that the Storefront Assistant page context accepts, keyed by the page type.
     * - The `chat-page-context` molecule detects the page type and reads the fields from the page; a rule in its `additionalRules` names them.
     * - Only the listed fields are kept, as trimmed strings of at most 120 characters, in `StorefrontAssistantPageContextTransfer::customFields`.
     * - A page type of the core (product, category, search) is never treated as a project page type.
     * - Returns an empty list by default: the core knows no project page types.
     *
     * @api
     *
     * @return array<string, list<string>>
     */
    public function getStorefrontAssistantCustomPageContextFields(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the hosts whose https URLs the Storefront Assistant shows as links in the sources of an answer.
     * - A source URL on the origin of the shop is always shown as a link; a source URL of any other host is shown as plain text.
     * - Each entry is a host such as `media.example.com` or an https origin such as `https://media.example.com`; an http origin makes the sources feature inactive.
     * - Returns an empty list by default: only source URLs on the origin of the shop are links.
     *
     * @api
     *
     * @return list<string>
     */
    public function getKnowledgeSourceLinkOrigins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the suggested prompts configured for each prompt group in the given locale, keyed by the page type
     *   it applies to, plus the `product_results` follow-up group offered under product cards the assistant renders.
     * - Expects each configuration value as a JSON object keyed by the locale name with a list of prompts as each value,
     *   for example `{"en_US": ["What is new?", "What is on sale?"], "de_DE": ["Was ist neu?"]}`.
     * - Returns an empty list for a group whose value is not set, is not such a JSON object, or has no list for the given locale.
     * - Returns only the text entries of the list of the given locale.
     * - A project adds the group of a project page type in `getSuggestedPromptConfigurationKeysByGroup()`.
     * - Reads each value from Configuration Management on every call so a Back Office change takes effect without a deployment.
     *
     * @api
     *
     * @return array<string, list<string>>
     */
    public function getStorefrontAssistantSuggestedPrompts(string $localeName): array
    {
        $suggestedPrompts = [];

        foreach ($this->getSuggestedPromptConfigurationKeysByGroup() as $group => $configurationKey) {
            $suggestedPrompts[$group] = $this->resolveLocalizedSuggestedPrompts(
                $this->getModuleConfig($configurationKey, ''),
                $localeName,
            );
        }

        return $suggestedPrompts;
    }

    /**
     * @return list<string>
     */
    protected function resolveLocalizedSuggestedPrompts(mixed $suggestedPromptValue, string $localeName): array
    {
        $localizedSuggestedPrompts = is_string($suggestedPromptValue)
            ? json_decode($suggestedPromptValue, true)
            : $suggestedPromptValue;

        if (!is_array($localizedSuggestedPrompts) || !is_array($localizedSuggestedPrompts[$localeName] ?? null)) {
            return [];
        }

        return array_values(array_filter($localizedSuggestedPrompts[$localeName], 'is_string'));
    }

    /**
     * @return array<string, string>
     */
    protected function getSuggestedPromptConfigurationKeysByGroup(): array
    {
        return [
            StorefrontAssistantPageType::Product->value => static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_PRODUCT_PAGE_PROMPTS,
            StorefrontAssistantPageType::Category->value => static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_CATEGORY_PAGE_PROMPTS,
            StorefrontAssistantPageType::Search->value => static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_SEARCH_PAGE_PROMPTS,
            StorefrontAssistantPageType::Default->value => static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_DEFAULT_PROMPTS,
            static::STOREFRONT_ASSISTANT_PROMPT_GROUP_PRODUCT_RESULTS => static::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_PRODUCT_RESULTS_PROMPTS,
        ];
    }
}
