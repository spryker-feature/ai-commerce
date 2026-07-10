<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\PromptResponseTransfer;
use Generated\Shared\Transfer\SearchByImagePromptResponseTransfer;
use Generated\Shared\Transfer\SearchByImageRequestTransfer;
use Spryker\Client\AiFoundation\AiFoundationClientInterface;
use Spryker\Client\Kernel\Container;
use SprykerFeature\Client\AiCommerce\AiCommerceClient;
use SprykerFeature\Client\AiCommerce\AiCommerceClientInterface;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig;
use SprykerFeature\Client\AiCommerce\AiCommerceDependencyProvider;
use SprykerFeature\Client\AiCommerce\AiCommerceFactory;

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
        $container = new Container();
        $container->set(AiCommerceDependencyProvider::CLIENT_AI_FOUNDATION, $aiFoundationClient);

        $factory = new AiCommerceFactory();
        $factory->setConfig($this->createStubbedAiCommerceConfig());
        $factory->setContainer($container);

        $client = new AiCommerceClient();
        $client->setFactory($factory);

        return $client;
    }

    protected function createStubbedAiCommerceConfig(): AiCommerceConfig
    {
        return Stub::make(AiCommerceConfig::class, [
            'getModuleConfig' => fn (string $key, mixed $default = null): mixed => $default,
        ]);
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
