<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\AiCommerce\SearchByImage;

use Generated\Shared\Transfer\AttachmentTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Generated\Shared\Transfer\SearchByImagePromptResponseTransfer;
use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Generated\Shared\Transfer\SearchByImageResponseTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use Throwable;

class AiSearchByImageTermResolver implements AiSearchByImageTermResolverInterface
{
    use LoggerTrait;

    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::ATTACHMENT_TYPE_IMAGE
     */
    protected const string ATTACHMENT_TYPE_IMAGE = 'image';

    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64
     */
    protected const string ATTACHMENT_CONTENT_TYPE_BASE64 = 'base64';

    /**
     * @uses \Spryker\Shared\AiFoundation\AiFoundationConstants::MESSAGE_TYPE_USER
     */
    protected const string MESSAGE_TYPE_USER = 'user';

    protected const string GLOSSARY_KEY_SEARCH_BY_IMAGE_UNAVAILABLE = 'ai_commerce.search_by_image.error.unavailable';

    protected const string ERROR_SEARCH_BY_IMAGE_FAILED = 'Search by image term resolution failed: %s';

    public function __construct(
        protected AiFoundationClientInterface $aiFoundationClient,
        protected AiCommerceConfig $config,
    ) {
    }

    public function getSearchTermFromImage(SearchByImageRequestTransfer $searchByImageRequestTransfer): SearchByImageResponseTransfer
    {
        try {
            $promptRequestTransfer = $this->buildPromptRequest($searchByImageRequestTransfer);
            $promptResponseTransfer = $this->aiFoundationClient->prompt($promptRequestTransfer);

            if (!$promptResponseTransfer->getIsSuccessful()) {
                foreach ($promptResponseTransfer->getErrors() as $error) {
                    $this->getLogger()->error(
                        sprintf(static::ERROR_SEARCH_BY_IMAGE_FAILED, $error->getMessage()),
                    );
                }

                return (new SearchByImageResponseTransfer())
                    ->setIsSuccessful(false)
                    ->addError(
                        (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_SEARCH_BY_IMAGE_UNAVAILABLE),
                    );
            }

            /** @var \Generated\Shared\Transfer\SearchByImagePromptResponseTransfer $searchByImagePromptResponse */
            $searchByImagePromptResponse = $promptResponseTransfer->getStructuredMessageOrFail();

            return (new SearchByImageResponseTransfer())
                ->setSearchTerm($searchByImagePromptResponse->getSearchTermOrFail())
                ->setIsSuccessful(true);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                sprintf(static::ERROR_SEARCH_BY_IMAGE_FAILED, $throwable->getMessage()),
                ['exception' => $throwable],
            );

            return (new SearchByImageResponseTransfer())
                ->setIsSuccessful(false)
                ->addError(
                    (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_SEARCH_BY_IMAGE_UNAVAILABLE),
                );
        }
    }

    protected function buildPromptRequest(SearchByImageRequestTransfer $searchByImageRequestTransfer): PromptRequestTransfer
    {
        $attachmentTransfer = (new AttachmentTransfer())
            ->setType(static::ATTACHMENT_TYPE_IMAGE)
            ->setContentType(static::ATTACHMENT_CONTENT_TYPE_BASE64)
            ->setContent($searchByImageRequestTransfer->getImageContent())
            ->setMediaType($searchByImageRequestTransfer->getImageMediaType());

        $promptMessageTransfer = (new PromptMessageTransfer())
            ->setType(static::MESSAGE_TYPE_USER)
            ->setContent($this->config->getSearchByImagePromptTemplate())
            ->addAttachment($attachmentTransfer);

        return (new PromptRequestTransfer())
            ->setPromptMessage($promptMessageTransfer)
            ->setStructuredMessage(new SearchByImagePromptResponseTransfer())
            ->setAiConfigurationName($this->config->getSearchByImageAiConfigurationName());
    }
}
