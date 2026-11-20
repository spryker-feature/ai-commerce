<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\AiCommerce\Communication\Plugin\ProductAttachment;

use Codeception\Test\Unit;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentFormExpanderPlugin;
use SprykerFeatureTest\Zed\AiCommerce\AiCommerceCommunicationTester;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Forms;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AiCommerce
 * @group Communication
 * @group Plugin
 * @group ProductAttachment
 * @group KnowledgeSelectionProductAttachmentFormExpanderPluginTest
 * Add your own group annotations below this line
 */
class KnowledgeSelectionProductAttachmentFormExpanderPluginTest extends Unit
{
    protected const string FIELD_IS_ASSISTANT_KNOWLEDGE = 'is_assistant_knowledge';

    protected const string LABEL_IS_ASSISTANT_KNOWLEDGE = 'Use for assistant knowledge';

    protected const string TRANSLATION_DIRECTORY = __DIR__ . '/../../../../../../../data/translation/Zed/';

    /**
     * @var list<string>
     */
    protected const array TRANSLATION_FILES = ['en_US.csv', 'de_DE.csv'];

    protected AiCommerceCommunicationTester $tester;

    public function testGivenRowFormBuilderWhenExpandThenOptionalAssistantKnowledgeCheckboxIsAdded(): void
    {
        // Arrange
        $formBuilder = Forms::createFormFactory()->createBuilder();

        // Act
        $form = (new KnowledgeSelectionProductAttachmentFormExpanderPlugin())->expand($formBuilder, [])->getForm();

        // Assert
        $fieldConfig = $form->get(static::FIELD_IS_ASSISTANT_KNOWLEDGE)->getConfig();
        $this->assertInstanceOf(CheckboxType::class, $fieldConfig->getType()->getInnerType());
        $this->assertFalse($fieldConfig->getOption('required'));
        $this->assertSame(static::LABEL_IS_ASSISTANT_KNOWLEDGE, $fieldConfig->getOption('label'));
    }

    public function testGivenZedTranslationFilesWhenReadThenEveryFileTranslatesTheCheckboxLabel(): void
    {
        // Arrange
        $translationKeysByFile = [];

        // Act
        foreach (static::TRANSLATION_FILES as $translationFile) {
            $translationKeysByFile[$translationFile] = $this->readTranslationKeys(static::TRANSLATION_DIRECTORY . $translationFile);
        }

        // Assert
        foreach ($translationKeysByFile as $translationKeys) {
            $this->assertContains(static::LABEL_IS_ASSISTANT_KNOWLEDGE, $translationKeys);
        }
    }

    /**
     * @return list<string>
     */
    protected function readTranslationKeys(string $filePath): array
    {
        $translationKeys = [];
        $fileHandle = fopen($filePath, 'r');

        while (($row = fgetcsv($fileHandle, null, ',', '"', '\\')) !== false) {
            $translationKeys[] = (string)$row[0];
        }

        fclose($fileHandle);

        return $translationKeys;
    }
}
