<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\AiCommerce\StorefrontAssistant\Reader;

use Codeception\Stub;
use Codeception\Test\Unit;
use SprykerFeature\Shared\AiCommerce\StorefrontAssistant\StorefrontAssistantPageType;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig;
use SprykerFeature\Yves\AiCommerce\AiCommerceFactory;
use SprykerFeature\Yves\AiCommerce\StorefrontAssistant\Reader\StorefrontAssistantSuggestedPromptReaderInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AiCommerce
 * @group StorefrontAssistant
 * @group Reader
 * @group StorefrontAssistantSuggestedPromptSectionsTest
 * Add your own group annotations below this line
 */
class StorefrontAssistantSuggestedPromptSectionsTest extends Unit
{
    protected const string CONFIGURATION_KEY_DEFAULT_PROMPTS = 'ai_commerce:storefront_assistant:suggested_prompts:default_prompts';

    protected const string KEY_TITLE = 'title';

    protected const string KEY_PROMPTS = 'prompts';

    public function testGivenTitledSectionsWhenSectionsAreReadThenEachSectionKeepsItsTitleAndPrompts(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_DEFAULT_PROMPTS => 'Delve into the topic: What types of sensors are there?|Which gauges do you offer? || Get recommendations: Best value sensor',
        ]);

        // Act
        $sections = $storefrontAssistantSuggestedPromptReader->getSuggestedPromptSections();

        // Assert
        $this->assertSame([
            [static::KEY_TITLE => 'Delve into the topic', static::KEY_PROMPTS => ['What types of sensors are there?', 'Which gauges do you offer?']],
            [static::KEY_TITLE => 'Get recommendations', static::KEY_PROMPTS => ['Best value sensor']],
        ], $sections[StorefrontAssistantPageType::Default->value]);
    }

    public function testGivenValueWithoutSectionSeparatorWhenSectionsAreReadThenOneUntitledSectionKeepsTheColon(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_DEFAULT_PROMPTS => 'Compare: tablets or laptops?|What do you recommend?',
        ]);

        // Act
        $sections = $storefrontAssistantSuggestedPromptReader->getSuggestedPromptSections();

        // Assert
        $this->assertSame([
            [static::KEY_TITLE => '', static::KEY_PROMPTS => ['Compare: tablets or laptops?', 'What do you recommend?']],
        ], $sections[StorefrontAssistantPageType::Default->value]);
    }

    public function testGivenSectionWithoutPromptsWhenSectionsAreReadThenTheSectionIsDroppedAndEachSectionIsCapped(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_DEFAULT_PROMPTS => 'Empty: || Many: One|Two|Three|Four|Five',
        ]);

        // Act
        $sections = $storefrontAssistantSuggestedPromptReader->getSuggestedPromptSections();

        // Assert
        $this->assertSame([
            [static::KEY_TITLE => 'Many', static::KEY_PROMPTS => ['One', 'Two', 'Three', 'Four']],
        ], $sections[StorefrontAssistantPageType::Default->value]);
    }

    public function testGivenTitledSectionsWhenFlatPromptsAreReadThenTitlesAreLeftOutAndTheGroupIsCapped(): void
    {
        // Arrange
        $storefrontAssistantSuggestedPromptReader = $this->createReaderReturning([
            static::CONFIGURATION_KEY_DEFAULT_PROMPTS => 'Learn: One|Two|Three || Explore: Four|Five',
        ]);

        // Act
        $suggestedPrompts = $storefrontAssistantSuggestedPromptReader->getSuggestedPrompts();

        // Assert
        $this->assertSame(['One', 'Two', 'Three', 'Four'], $suggestedPrompts[StorefrontAssistantPageType::Default->value]);
    }

    /**
     * @param array<string, string> $configurationValues
     */
    protected function createReaderReturning(array $configurationValues): StorefrontAssistantSuggestedPromptReaderInterface
    {
        /**
         * @var \SprykerFeature\Yves\AiCommerce\AiCommerceConfig $aiCommerceConfig
         */
        $aiCommerceConfig = Stub::construct(AiCommerceConfig::class, [], [
            'getModuleConfig' => function (string $key, mixed $default = null) use ($configurationValues): mixed {
                return $configurationValues[$key] ?? $default;
            },
        ]);

        $aiCommerceFactory = new AiCommerceFactory();
        $aiCommerceFactory->setConfig($aiCommerceConfig);

        return $aiCommerceFactory->createStorefrontAssistantSuggestedPromptReader();
    }
}
