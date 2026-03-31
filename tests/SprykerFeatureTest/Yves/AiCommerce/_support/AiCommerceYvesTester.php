<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce;

use Codeception\Actor;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Generated\Shared\Transfer\SearchByImagePromptResponseTransfer;
use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceDependencyProvider;

/**
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class AiCommerceYvesTester extends Actor
{
    use _generated\AiCommerceYvesTesterActions;

    protected const string FAKE_IMAGE_DATA = 'fake-image-data';

    protected const string IMAGE_MEDIA_TYPE = 'image/jpeg';

    protected const string DEFAULT_LOCALE_NAME = 'en_US';

    public function createAiCommerceClient(AiFoundationClientInterface $aiFoundationClient): AiCommerceClientInterface
    {
        $this->setDependency(AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION, $aiFoundationClient);

        return $this->getLocator()->aiCommerce()->client();
    }

    public function createSearchByImageRequest(string $localeName = self::DEFAULT_LOCALE_NAME): SearchByImageRequestTransfer
    {
        return (new SearchByImageRequestTransfer())
            ->setImageContent(base64_encode(static::FAKE_IMAGE_DATA))
            ->setImageMediaType(static::IMAGE_MEDIA_TYPE)
            ->setLocaleName($localeName);
    }

    public function createSuccessfulSearchByImagePromptResponse(string $searchTerm): PromptResponseTransfer
    {
        return (new PromptResponseTransfer())
            ->setIsSuccessful(true)
            ->setStructuredMessage((new SearchByImagePromptResponseTransfer())->setSearchTerm($searchTerm));
    }

    public function createFailedPromptResponse(): PromptResponseTransfer
    {
        return (new PromptResponseTransfer())->setIsSuccessful(false);
    }
}
