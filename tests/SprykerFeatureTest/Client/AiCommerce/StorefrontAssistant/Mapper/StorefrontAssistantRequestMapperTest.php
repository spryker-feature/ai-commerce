<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\AiCommerce\StorefrontAssistant\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StorefrontAssistantChatRequestTransfer;
use SprykerFeature\Client\AiCommerce\StorefrontAssistant\Mapper\StorefrontAssistantRequestMapper;
use SprykerFeature\Shared\AiCommerce\Conversation\RegeneratedPromptMarker;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Mapper
 * @group StorefrontAssistantRequestMapperTest
 */
class StorefrontAssistantRequestMapperTest extends Unit
{
    protected const string MESSAGE = 'Which camera is the cheapest?';

    protected const string AI_CONFIGURATION_NAME = 'storefront-assistant';

    protected const string CONVERSATION_REFERENCE = 'DE--1:conversation';

    public function testGivenARegenerationWhenMappingThenThePromptIsMarked(): void
    {
        // Arrange
        $marker = new RegeneratedPromptMarker();
        $mapper = new StorefrontAssistantRequestMapper($marker);
        $storefrontAssistantChatRequestTransfer = (new StorefrontAssistantChatRequestTransfer())
            ->setMessage(static::MESSAGE)
            ->setIsRegeneration(true);

        // Act
        $promptRequestTransfer = $mapper->mapStorefrontAssistantChatRequestToPromptRequest(
            $storefrontAssistantChatRequestTransfer,
            static::AI_CONFIGURATION_NAME,
            static::CONVERSATION_REFERENCE,
        );

        // Assert
        $this->assertSame($marker->addMarker(static::MESSAGE), $promptRequestTransfer->getPromptMessageOrFail()->getContent());
    }

    public function testGivenAnOrdinaryMessageWhenMappingThenThePromptIsNotMarked(): void
    {
        // Arrange
        $mapper = new StorefrontAssistantRequestMapper(new RegeneratedPromptMarker());
        $storefrontAssistantChatRequestTransfer = (new StorefrontAssistantChatRequestTransfer())
            ->setMessage(static::MESSAGE)
            ->setIsRegeneration(false);

        // Act
        $promptRequestTransfer = $mapper->mapStorefrontAssistantChatRequestToPromptRequest(
            $storefrontAssistantChatRequestTransfer,
            static::AI_CONFIGURATION_NAME,
            static::CONVERSATION_REFERENCE,
        );

        // Assert
        $this->assertSame(static::MESSAGE, $promptRequestTransfer->getPromptMessageOrFail()->getContent());
    }
}
