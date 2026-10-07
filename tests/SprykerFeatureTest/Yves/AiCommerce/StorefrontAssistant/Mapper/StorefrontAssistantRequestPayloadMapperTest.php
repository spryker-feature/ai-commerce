<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Mapper;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\AiCommerceFactory;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Mapper
 * @group StorefrontAssistantRequestPayloadMapperTest
 */
class StorefrontAssistantRequestPayloadMapperTest extends Unit
{
    protected const string CUSTOMER_REFERENCE = 'DE--TEST-1';

    protected const string LOCALE_NAME = 'en_US';

    protected const string CONVERSATION_REFERENCE = 'conversation-1';

    public function testGivenNonStringMessageAndConversationReferenceWhenMappedThenBothAreEmptyInsteadOfFailing(): void
    {
        // Arrange
        $storefrontAssistantRequestPayloadMapper = (new AiCommerceFactory())->createStorefrontAssistantRequestPayloadMapper();

        // Act
        $storefrontAssistantChatRequestTransfer = $storefrontAssistantRequestPayloadMapper->mapPayloadToStorefrontAssistantChatRequestTransfer(
            ['message' => ['x'], 'conversationReference' => 42, 'selectedAgent' => ['product_discovery']],
            static::CUSTOMER_REFERENCE,
            static::LOCALE_NAME,
        );

        // Assert
        $this->assertSame('', $storefrontAssistantChatRequestTransfer->getMessage());
        $this->assertSame('', $storefrontAssistantChatRequestTransfer->getConversationReference());
        $this->assertNull($storefrontAssistantChatRequestTransfer->getSelectedAgent());
    }

    public function testGivenAValidChatPayloadWhenMappedThenTheRequestCarriesItWithTheSanitizedPageContext(): void
    {
        // Arrange
        $storefrontAssistantRequestPayloadMapper = (new AiCommerceFactory())->createStorefrontAssistantRequestPayloadMapper();

        // Act
        $storefrontAssistantChatRequestTransfer = $storefrontAssistantRequestPayloadMapper->mapPayloadToStorefrontAssistantChatRequestTransfer(
            [
                'message' => 'Show me cameras',
                'conversationReference' => static::CONVERSATION_REFERENCE,
                'pageContext' => ['pageType' => StorefrontAssistantPageType::Category->value, 'categoryName' => ' Cameras '],
            ],
            static::CUSTOMER_REFERENCE,
            static::LOCALE_NAME,
        );

        // Assert
        $this->assertSame('Show me cameras', $storefrontAssistantChatRequestTransfer->getMessage());
        $this->assertSame(static::LOCALE_NAME, $storefrontAssistantChatRequestTransfer->getLocaleName());
        $this->assertSame('Cameras', $storefrontAssistantChatRequestTransfer->getStorefrontAssistantPageContextOrFail()->getCategoryName());
    }

    public function testGivenASingleConversationReferenceWhenDeleteCriteriaAreMappedThenItIsUsedAsTheOnlyReference(): void
    {
        // Arrange
        $storefrontAssistantRequestPayloadMapper = (new AiCommerceFactory())->createStorefrontAssistantRequestPayloadMapper();

        // Act
        $storefrontAssistantConversationCollectionDeleteCriteriaTransfer = $storefrontAssistantRequestPayloadMapper
            ->mapPayloadToConversationCollectionDeleteCriteriaTransfer(['conversationReference' => static::CONVERSATION_REFERENCE], static::CUSTOMER_REFERENCE);

        // Assert
        $this->assertSame(
            [static::CONVERSATION_REFERENCE],
            $storefrontAssistantConversationCollectionDeleteCriteriaTransfer->getStorefrontAssistantConversationDeleteConditionsOrFail()->getConversationReferences(),
        );
    }

    public function testGivenConversationReferencesWithNonStringsWhenListCriteriaAreMappedThenOnlyStringsAreKept(): void
    {
        // Arrange
        $storefrontAssistantRequestPayloadMapper = (new AiCommerceFactory())->createStorefrontAssistantRequestPayloadMapper();

        // Act
        $storefrontAssistantConversationCollectionCriteriaTransfer = $storefrontAssistantRequestPayloadMapper
            ->mapPayloadToConversationCollectionCriteriaTransfer(['conversationReferences' => [static::CONVERSATION_REFERENCE, ['x'], 7]], static::CUSTOMER_REFERENCE);

        // Assert
        $this->assertSame(
            [static::CONVERSATION_REFERENCE],
            $storefrontAssistantConversationCollectionCriteriaTransfer->getStorefrontAssistantConversationConditionsOrFail()->getConversationReferences(),
        );
    }

    public function testGivenANonStringConversationReferenceWhenMessageCriteriaAreMappedThenItIsEmpty(): void
    {
        // Arrange
        $storefrontAssistantRequestPayloadMapper = (new AiCommerceFactory())->createStorefrontAssistantRequestPayloadMapper();

        // Act
        $storefrontAssistantConversationMessageCollectionCriteriaTransfer = $storefrontAssistantRequestPayloadMapper
            ->mapPayloadToConversationMessageCollectionCriteriaTransfer(['conversationReference' => ['x']], static::CUSTOMER_REFERENCE);

        // Assert
        $this->assertSame(
            '',
            $storefrontAssistantConversationMessageCollectionCriteriaTransfer->getStorefrontAssistantConversationMessageConditionsOrFail()->getConversationReference(),
        );
    }
}
